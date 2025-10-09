<?php

namespace App\Http\Controllers;

use App\Models\DeviceRepair;
use App\Models\User;
use App\Models\Role;
use App\Notifications\DeviceServiceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeviceRepairController extends Controller
{
    /**
     * Display a listing of the device repairs.
     */
    public function index(Request $request)
    {
        $query = DeviceRepair::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('device_model', 'like', "%{$search}%")
                  ->orWhere('device_serial_number', 'like', "%{$search}%")
                  ->orWhere('tracking_code', 'like', "%{$search}%");
            });
        }

        $deviceRepairs = $query->paginate(15);

        $statusCounts = [
            'received' => DeviceRepair::where('status', 'received')->count(),
            'processing' => DeviceRepair::where('status', 'processing')->count(),
            'ready' => DeviceRepair::where('status', 'ready')->count(),
            'delivered' => DeviceRepair::where('status', 'delivered')->count(),
        ];

        return view('manager.device-repairs.index', compact('deviceRepairs', 'statusCounts'));
    }

    /**
     * Show the form for creating a new device repair.
     */
    public function create()
    {
        return view('manager.device-repairs.create');
    }

    /**
     * Store a newly created device repair in storage.
     */
    public function store(Request $request)
    {
        // First, check if user exists by phone number
        $existingUserByPhone = User::where('phone', $request->phone_number)->first();
        
        // Conditionally validate email based on whether user exists
        $emailRule = 'required|email|max:255';
        if (!$existingUserByPhone) {
            // Only check uniqueness if user doesn't exist by phone
            $emailRule .= '|unique:users,email';
        }
        
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => $emailRule,
            'phone_number' => 'required|string|max:20',
            'device_model' => 'required|string|max:255',
            'device_serial_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'status' => ['required', Rule::in(['received', 'processing', 'ready', 'delivered'])]
        ]);

        $deviceRepair = null;
        
        try {
            DB::transaction(function () use ($validated, &$deviceRepair) {
            // Extract country code and phone number from the full international number
            $phoneNumber = $validated['phone_number'];
            $countryCode = '+20'; // Default to Egypt
            $phoneNumberOnly = $phoneNumber;
            
            // Try to extract country code from phone number (intlTelInput format)
            if (preg_match('/^\+(\d{1,4})/', $phoneNumber, $matches)) {
                $countryCode = '+' . $matches[1];
                $phoneNumberOnly = substr($phoneNumber, strlen($matches[0]));
            }
            
            // Create or find user first
            $fullPhoneNumber = $phoneNumber;
            
            // Check if user exists by phone
            $existingUser = User::where('phone', $fullPhoneNumber)->first();
            
            if ($existingUser) {
                // User exists by phone
                // Update email if it's null or empty, or if it matches the submitted email
                if (empty($existingUser->email) || $existingUser->email === $validated['client_email']) {
                    $existingUser->email = $validated['client_email'];
                    $existingUser->name = $validated['client_name']; // Update name as well
                    $existingUser->save();
                } elseif ($existingUser->email !== $validated['client_email']) {
                    // Different email already exists for this phone
                    throw new \Exception('A user with this phone number already exists with a different email address.');
                }
                $user = $existingUser;
            } else {
                // Create new user
                $user = User::create([
                    'name' => $validated['client_name'],
                    'email' => $validated['client_email'],
                    'phone' => $fullPhoneNumber,
                    'password' => bcrypt('temp_password_' . uniqid())
                ]);
            }

            // Assign customer role if user is newly created
            if ($user->wasRecentlyCreated && $user->roles()->count() === 0) {
                $customerRole = Role::where('name', 'customer')->first();
                if ($customerRole) {
                    $user->roles()->attach($customerRole);
                }
            }

            // Create device repair and link to user
            $deviceRepair = $user->deviceRepairs()->create([
                'device_model' => $validated['device_model'],
                'device_serial_number' => $validated['device_serial_number'],
                'notes' => $validated['notes'],
                'status' => $validated['status'],
                'tracking_code' => DeviceRepair::generateTrackingCode(),
                'submitted_at' => now(),
                'status_updated_at' => now()
            ]);
            });

            // Send email notification after transaction
            if ($deviceRepair) {
                $deviceRepair->load('user');
                // Notification will check if email is valid before sending
                $deviceRepair->user->notify(new DeviceServiceNotification($deviceRepair, 'created'));
            }

            return redirect()->route('device-repairs.index')
                ->with('success', 'Device repair record created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['client_email' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified device repair.
     */
    public function show(DeviceRepair $deviceRepair)
    {
        $deviceRepair->load('user');
        return view('manager.device-repairs.show', compact('deviceRepair'));
    }

    /**
     * Show the form for editing the specified device repair.
     */
    public function edit(DeviceRepair $deviceRepair)
    {
        return view('manager.device-repairs.edit', compact('deviceRepair'));
    }

    /**
     * Update the specified device repair in storage.
     */
    public function update(Request $request, DeviceRepair $deviceRepair)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'country_code' => 'required|string|max:5',
            'device_model' => 'required|string|max:255',
            'device_serial_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'status' => ['required', Rule::in(['received', 'processing', 'ready', 'delivered'])]
        ]);

        $deviceRepair->update($validated);

        return redirect()->route('device-repairs.index')
            ->with('success', 'Device repair record updated successfully.');
    }

    /**
     * Update the status of the specified device repair.
     */
    public function updateStatus(Request $request, DeviceRepair $deviceRepair)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['received', 'processing', 'ready', 'delivered'])]
            ]);

            $oldStatus = $deviceRepair->status;
            $result = $deviceRepair->updateStatus($validated['status']);
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update status. Please try again.'
                ], 500);
            }

            // Refresh the model to get updated data
            $deviceRepair->refresh();

            // Send email notification if status actually changed
            if ($oldStatus !== $deviceRepair->status) {
                $deviceRepair->load('user');
                // Notification will check if email is valid before sending
                $deviceRepair->user->notify(new DeviceServiceNotification($deviceRepair, 'status_changed'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'new_status' => $deviceRepair->status_display,
                'status_class' => $deviceRepair->status_badge_class,
                'status' => $deviceRepair->status
            ]);
        } catch (\Exception $e) {
            \Log::error('Status update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified device repair from storage.
     */
    public function destroy(DeviceRepair $deviceRepair)
    {
        $deviceRepair->delete();

        return redirect()->route('device-repairs.index')
            ->with('success', 'Device repair record deleted successfully.');
    }

    /**
     * Get statistics for dashboard.
     */
    public function getStats()
    {
        $stats = [
            'total_repairs' => DeviceRepair::count(),
            'active_repairs' => DeviceRepair::active()->count(),
            'delivered_today' => DeviceRepair::where('status', 'delivered')
                ->whereDate('status_updated_at', today())
                ->count(),
            'processing_repairs' => DeviceRepair::where('status', 'processing')->count()
        ];

        return response()->json($stats);
    }

    /**
     * Check if user exists by phone number.
     */
    public function checkUser(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20'
        ]);

        $phoneNumber = $request->phone_number;
        $user = User::where('phone', $phoneNumber)->first();

        if ($user) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ]
            ]);
        }

        return response()->json(['user' => null]);
    }
}
