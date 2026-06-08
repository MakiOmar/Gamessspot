<?php

namespace App\Http\Controllers;

use App\Models\DeviceRepair;
use App\Models\DeviceModel;
use App\Models\User;
use App\Notifications\DeviceServiceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Rawilk\Settings\Facades\Settings;

class PublicDeviceController extends Controller
{
    /**
     * Show the device submission form.
     */
    public function showSubmissionForm()
    {
        $deviceModels = DeviceModel::active()->orderBy('brand')->orderBy('name')->get();
        $appLogo = Settings::get('app.logo');

        return view('public.device-submission', compact('deviceModels', 'appLogo'));
    }

    /**
     * Submit a new device for repair.
     */
    public function submitDevice(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'device_model_id' => 'required|exists:device_models,id',
            'device_serial_number' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        // Extract country code from phone number (intlTelInput format)
        $phoneNumber = $validated['phone_number'];
        $countryCode = '+20'; // Default to Egypt
        
        // Try to extract country code from phone number
        if (preg_match('/^\+(\d{1,4})/', $phoneNumber, $matches)) {
            $countryCode = '+' . $matches[1];
            $phoneNumber = substr($phoneNumber, strlen($matches[0]));
        }

        $deviceRepair = null;
        $isDuplicateSubmission = false;
        $normalizedSerial = DeviceRepair::normalizeSerial($validated['device_serial_number']);
        $lockKey = 'device-repair:public:' . md5(implode('|', [
            $validated['phone_number'],
            $validated['device_model_id'],
            $normalizedSerial,
        ]));

        try {
            Cache::lock($lockKey, 10)->block(5, function () use ($validated, $phoneNumber, $countryCode, $normalizedSerial, &$deviceRepair, &$isDuplicateSubmission) {
            DB::transaction(function () use ($validated, $phoneNumber, $countryCode, $normalizedSerial, &$deviceRepair, &$isDuplicateSubmission) {
            $fullPhoneNumber = $countryCode . $phoneNumber;

            $existingUser = User::where('phone', $fullPhoneNumber)->first();

            if ($existingUser) {
                if ($existingUser->email !== $validated['client_email']) {
                    throw new \Exception('A user with this phone number already exists with a different email address.');
                }
                $user = $existingUser;
            } else {
                $user = User::create([
                    'name' => $validated['client_name'],
                    'email' => $validated['client_email'],
                    'phone' => $fullPhoneNumber,
                    'password' => bcrypt('temp_password_' . uniqid())
                ]);
            }

            if ($user->wasRecentlyCreated && $user->roles()->count() === 0) {
                $customerRole = \App\Models\Role::where('name', 'customer')->first();
                if ($customerRole) {
                    $user->roles()->attach($customerRole);
                }
            }

            $recentDuplicate = DeviceRepair::findRecentDuplicate(
                $user->id,
                $validated['device_model_id'],
                $normalizedSerial
            );

            if ($recentDuplicate) {
                $deviceRepair = $recentDuplicate;
                $isDuplicateSubmission = true;
                return;
            }

            $activeDuplicate = DeviceRepair::findActiveDuplicate(
                $user->id,
                $validated['device_model_id'],
                $normalizedSerial
            );

            if ($activeDuplicate) {
                throw new \Exception(
                    'An active service request already exists for this device (Tracking: ' . $activeDuplicate->tracking_code . ').'
                );
            }

            $deviceRepair = $user->deviceRepairs()->create([
                'device_model_id' => $validated['device_model_id'],
                'device_serial_number' => $normalizedSerial,
                'notes' => $validated['notes'],
                'tracking_code' => DeviceRepair::generateTrackingCode(),
                'submitted_at' => now(),
                'status_updated_at' => now()
            ]);
            });
            });

            if ($deviceRepair && !$isDuplicateSubmission) {
                $deviceRepair->load(['user', 'deviceModel']);
                try {
                    $deviceRepair->user->notify(new DeviceServiceNotification($deviceRepair, 'created'));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send device repair notification email: ' . $e->getMessage());
                }
            }

            $successMessage = $isDuplicateSubmission
                ? 'Your device submission was already received. Your tracking code is: ' . $deviceRepair->tracking_code
                : 'Your device has been submitted successfully! Your tracking code is: ' . $deviceRepair->tracking_code;

            return redirect()->route('device.tracking', ['code' => $deviceRepair->tracking_code])
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            return back()->withErrors(['client_email' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show the device tracking page.
     */
    public function trackDevice(Request $request)
    {
        $trackingCode = $request->query('code');
        $deviceRepair = null;
        $appLogo = Settings::get('app.logo');

        if ($trackingCode) {
            $deviceRepair = DeviceRepair::with(['user', 'deviceModel'])
                ->where('tracking_code', $trackingCode)
                ->first();
        }

        return view('public.device-tracking', compact('deviceRepair', 'trackingCode', 'appLogo'));
    }

    /**
     * Search for device by phone number.
     */
    public function searchByPhone(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:20'
        ]);

        // Extract country code from phone number (intlTelInput format)
        $phoneNumber = $validated['phone_number'];
        $countryCode = '+20'; // Default to Egypt
        
        // Try to extract country code from phone number
        if (preg_match('/^\+(\d{1,4})/', $phoneNumber, $matches)) {
            $countryCode = '+' . $matches[1];
            $phoneNumber = substr($phoneNumber, strlen($matches[0]));
        }

        $deviceRepairs = DeviceRepair::with(['user', 'deviceModel'])
            ->whereHas('user', function($query) use ($phoneNumber, $countryCode) {
                $query->where('phone', $countryCode . $phoneNumber)
                      ->orWhere('phone', $phoneNumber);
            })
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        if ($deviceRepairs->isEmpty()) {
            return redirect()->back()
                ->with('error', 'No active services found for this phone number.');
        }

        return view('public.device-tracking', [
            'deviceRepairs' => $deviceRepairs,
            'searchPhone' => $phoneNumber,
            'searchCountryCode' => $countryCode,
            'trackingCode' => null,
            'deviceRepair' => null,
            'appLogo' => Settings::get('app.logo'),
        ]);
    }

    /**
     * Get country codes for the dropdown.
     */
    public function getCountryCodes()
    {
        $countryCodes = [
            ['code' => '+20', 'name' => 'Egypt (+20)'],
            ['code' => '+1', 'name' => 'United States (+1)'],
            ['code' => '+44', 'name' => 'United Kingdom (+44)'],
            ['code' => '+33', 'name' => 'France (+33)'],
            ['code' => '+49', 'name' => 'Germany (+49)'],
            ['code' => '+39', 'name' => 'Italy (+39)'],
            ['code' => '+34', 'name' => 'Spain (+34)'],
            ['code' => '+31', 'name' => 'Netherlands (+31)'],
            ['code' => '+32', 'name' => 'Belgium (+32)'],
            ['code' => '+41', 'name' => 'Switzerland (+41)'],
            ['code' => '+43', 'name' => 'Austria (+43)'],
            ['code' => '+45', 'name' => 'Denmark (+45)'],
            ['code' => '+46', 'name' => 'Sweden (+46)'],
            ['code' => '+47', 'name' => 'Norway (+47)'],
            ['code' => '+358', 'name' => 'Finland (+358)'],
            ['code' => '+48', 'name' => 'Poland (+48)'],
            ['code' => '+420', 'name' => 'Czech Republic (+420)'],
            ['code' => '+421', 'name' => 'Slovakia (+421)'],
            ['code' => '+36', 'name' => 'Hungary (+36)'],
            ['code' => '+40', 'name' => 'Romania (+40)'],
            ['code' => '+359', 'name' => 'Bulgaria (+359)'],
            ['code' => '+385', 'name' => 'Croatia (+385)'],
            ['code' => '+386', 'name' => 'Slovenia (+386)'],
            ['code' => '+372', 'name' => 'Estonia (+372)'],
            ['code' => '+371', 'name' => 'Latvia (+371)'],
            ['code' => '+370', 'name' => 'Lithuania (+370)'],
            ['code' => '+966', 'name' => 'Saudi Arabia (+966)'],
            ['code' => '+971', 'name' => 'UAE (+971)'],
            ['code' => '+974', 'name' => 'Qatar (+974)'],
            ['code' => '+965', 'name' => 'Kuwait (+965)'],
            ['code' => '+973', 'name' => 'Bahrain (+973)'],
            ['code' => '+968', 'name' => 'Oman (+968)'],
            ['code' => '+962', 'name' => 'Jordan (+962)'],
            ['code' => '+961', 'name' => 'Lebanon (+961)'],
            ['code' => '+963', 'name' => 'Syria (+963)'],
            ['code' => '+964', 'name' => 'Iraq (+964)'],
            ['code' => '+90', 'name' => 'Turkey (+90)'],
            ['code' => '+98', 'name' => 'Iran (+98)'],
            ['code' => '+92', 'name' => 'Pakistan (+92)'],
            ['code' => '+91', 'name' => 'India (+91)'],
            ['code' => '+86', 'name' => 'China (+86)'],
            ['code' => '+81', 'name' => 'Japan (+81)'],
            ['code' => '+82', 'name' => 'South Korea (+82)'],
            ['code' => '+65', 'name' => 'Singapore (+65)'],
            ['code' => '+60', 'name' => 'Malaysia (+60)'],
            ['code' => '+66', 'name' => 'Thailand (+66)'],
            ['code' => '+84', 'name' => 'Vietnam (+84)'],
            ['code' => '+63', 'name' => 'Philippines (+63)'],
            ['code' => '+62', 'name' => 'Indonesia (+62)'],
            ['code' => '+61', 'name' => 'Australia (+61)'],
            ['code' => '+64', 'name' => 'New Zealand (+64)'],
            ['code' => '+55', 'name' => 'Brazil (+55)'],
            ['code' => '+54', 'name' => 'Argentina (+54)'],
            ['code' => '+56', 'name' => 'Chile (+56)'],
            ['code' => '+57', 'name' => 'Colombia (+57)'],
            ['code' => '+58', 'name' => 'Venezuela (+58)'],
            ['code' => '+51', 'name' => 'Peru (+51)'],
            ['code' => '+52', 'name' => 'Mexico (+52)'],
            ['code' => '+1', 'name' => 'Canada (+1)'],
        ];

        return response()->json($countryCodes);
    }
}
