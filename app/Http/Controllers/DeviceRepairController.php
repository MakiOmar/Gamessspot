<?php

namespace App\Http\Controllers;

use App\Models\DeviceRepair;
use App\Models\DeviceModel;
use App\Models\User;
use App\Models\Role;
use App\Notifications\DeviceServiceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeviceRepairController extends Controller
{
    /**
     * Display a listing of the device repairs.
     */
    public function index(Request $request)
    {
        $query = DeviceRepair::with(['user', 'deviceModel', 'storeProfile', 'submittedBy'])
            ->orderBy('created_at', 'desc');

        // For non-admin users, filter by their store profile
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin')) {
            $query->where('store_profile_id', $currentUser->store_profile_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by store profile (only applies if admin and explicitly filtering)
        if ($request->filled('store_profile_id')) {
            $query->where('store_profile_id', $request->store_profile_id);
        }

        // Filter by date range if both start and end dates are provided
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                // Filter repairs created on the exact date (whole day)
                $query->whereDate('created_at', $startDate);
            } else {
                // Filter between the date range
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // Search in related user's name
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('phone', 'like', "%{$search}%");
                })
                // Search in device_repairs table columns
                ->orWhere('device_serial_number', 'like', "%{$search}%")
                ->orWhere('tracking_code', 'like', "%{$search}%")
                // Search in related device model
                ->orWhereHas('deviceModel', function ($modelQuery) use ($search) {
                    $modelQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('brand', 'like', "%{$search}%");
                });
            });
        }

        $deviceRepairs = $query->paginate(15)->appends($request->all());

        // Filter status counts based on user role
        $statusCountsQuery = DeviceRepair::query();
        if (!$currentUser->hasRole('admin')) {
            $statusCountsQuery->where('store_profile_id', $currentUser->store_profile_id);
        }

        $statusCounts = [
            'received' => (clone $statusCountsQuery)->where('status', 'received')->count(),
            'processing' => (clone $statusCountsQuery)->where('status', 'processing')->count(),
            'ready' => (clone $statusCountsQuery)->where('status', 'ready')->count(),
            'delivered' => (clone $statusCountsQuery)->where('status', 'delivered')->count(),
        ];

        $storeProfiles = \App\Models\StoresProfile::all();

        return view('manager.device-repairs.index', compact('deviceRepairs', 'statusCounts', 'storeProfiles'));
    }

    /**
     * Show the form for creating a new device repair.
     */
    public function create()
    {
        $deviceModels = DeviceModel::active()->orderBy('brand')->orderBy('name')->get();
        $storeProfiles = \App\Models\StoresProfile::all();
        return view('manager.device-repairs.create', compact('deviceModels', 'storeProfiles'));
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
            'device_model_id' => 'required|exists:device_models,id',
            'device_serial_number' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'status' => ['required', Rule::in(['received', 'processing', 'ready', 'delivered'])],
            'store_profile_id' => 'required|exists:stores_profile,id'
        ]);

        // For non-admin users, enforce their own store profile
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin')) {
            $validated['store_profile_id'] = $currentUser->store_profile_id;
        }

        $deviceRepair = null;
        $isDuplicateSubmission = false;
        $normalizedSerial = DeviceRepair::normalizeSerial($validated['device_serial_number']);
        $lockKey = 'device-repair:create:' . md5(implode('|', [
            $validated['phone_number'],
            $validated['device_model_id'],
            $normalizedSerial,
            $validated['store_profile_id'],
        ]));

        try {
            Cache::lock($lockKey, 10)->block(5, function () use ($validated, $normalizedSerial, &$deviceRepair, &$isDuplicateSubmission) {
            DB::transaction(function () use ($validated, $normalizedSerial, &$deviceRepair, &$isDuplicateSubmission) {
            $phoneNumber = $validated['phone_number'];
            $fullPhoneNumber = $phoneNumber;

            $existingUser = User::where('phone', $fullPhoneNumber)->first();

            if ($existingUser) {
                if (empty($existingUser->email) || $existingUser->email === $validated['client_email']) {
                    $existingUser->email = $validated['client_email'];
                    $existingUser->name = $validated['client_name'];
                    $existingUser->save();
                } elseif ($existingUser->email !== $validated['client_email']) {
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
                $customerRole = Role::where('name', 'customer')->first();
                if ($customerRole) {
                    $user->roles()->attach($customerRole);
                }
            }

            $recentDuplicate = DeviceRepair::findRecentDuplicate(
                $user->id,
                $validated['device_model_id'],
                $normalizedSerial,
                $validated['store_profile_id']
            );

            if ($recentDuplicate) {
                $deviceRepair = $recentDuplicate;
                $isDuplicateSubmission = true;
                return;
            }

            $activeDuplicate = DeviceRepair::findActiveDuplicate(
                $user->id,
                $validated['device_model_id'],
                $normalizedSerial,
                $validated['store_profile_id']
            );

            if ($activeDuplicate) {
                throw new \Exception(
                    'An active repair already exists for this device (Tracking: ' . $activeDuplicate->tracking_code . ').'
                );
            }

            $deviceRepair = $user->deviceRepairs()->create([
                'device_model_id' => $validated['device_model_id'],
                'device_serial_number' => $normalizedSerial,
                'notes' => $validated['notes'],
                'status' => $validated['status'],
                'tracking_code' => DeviceRepair::generateTrackingCode(),
                'submitted_by_user_id' => auth()->id(),
                'store_profile_id' => $validated['store_profile_id'],
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
                ? 'Device repair record already exists (Tracking: ' . $deviceRepair->tracking_code . ').'
                : 'Device repair record created successfully.';

            return redirect()->route('device-repairs.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            return back()->withErrors(['client_email' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified device repair.
     */
    public function show(DeviceRepair $deviceRepair)
    {
        // Non-admin users can only view repairs from their own store profile
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin') && $deviceRepair->store_profile_id !== $currentUser->store_profile_id) {
            abort(403, 'Unauthorized action. You can only view device repairs from your own store profile.');
        }

        $deviceRepair->load(['user', 'deviceModel']);
        return view('manager.device-repairs.show', compact('deviceRepair'));
    }

    /**
     * Show the form for editing the specified device repair.
     */
    public function edit(DeviceRepair $deviceRepair)
    {
        // Non-admin users can only edit repairs from their own store profile
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin') && $deviceRepair->store_profile_id !== $currentUser->store_profile_id) {
            abort(403, 'Unauthorized action. You can only edit device repairs from your own store profile.');
        }

        $deviceModels = DeviceModel::active()->orderBy('brand')->orderBy('name')->get();
        return view('manager.device-repairs.edit', compact('deviceRepair', 'deviceModels'));
    }

    /**
     * Update the specified device repair in storage.
     */
    public function update(Request $request, DeviceRepair $deviceRepair)
    {
        // Non-admin users can only update repairs from their own store profile
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin') && $deviceRepair->store_profile_id !== $currentUser->store_profile_id) {
            abort(403, 'Unauthorized action. You can only update device repairs from your own store profile.');
        }

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'country_code' => 'required|string|max:5',
            'device_model_id' => 'required|exists:device_models,id',
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
        // Non-admin users can only update status for repairs from their own store profile
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin') && $deviceRepair->store_profile_id !== $currentUser->store_profile_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only update device repairs from your own store profile.'
            ], 403);
        }

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
                $deviceRepair->load(['user', 'deviceModel']);
                try {
                    $deviceRepair->user->notify(new DeviceServiceNotification($deviceRepair, 'status_changed'));
                } catch (\Exception $e) {
                    // Log email failure but don't fail the entire operation
                    \Log::warning('Failed to send status change notification email: ' . $e->getMessage());
                }
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
        // Only admins can delete device repairs
        $this->authorize('delete-device-repairs');

        // Additional check: even admins should have this validation (optional security layer)
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin') && $deviceRepair->store_profile_id !== $currentUser->store_profile_id) {
            abort(403, 'Unauthorized action. You can only delete device repairs from your own store profile.');
        }

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
