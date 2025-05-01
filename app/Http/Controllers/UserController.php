<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\StoresProfile;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $action = $request->input('action');

        if ($action === 'deactivate') {
            $user->is_active = 0;
            $message = "User account deactivated successfully.";
        } elseif ($action === 'activate') {
            $user->is_active = 1;
            $message = "User account activated successfully.";
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid action.']);
        }

        $user->save();

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function users($role = 'any')
    {
        if ('any' === $role) {
            $users = User::with('storeProfile')->paginate(10);
        } else {
            $users = User::whereHas(
                'roles',
                function ($query) use ($role) {
                    $query->where('roles.id', intval($role));
                }
            )->with('storeProfile')->paginate(10);
        }

        $storeProfiles = StoresProfile::all(); // Fetch all store profiles

        $roles = Role::all(); // Fetch all available roles

        return view('manager.users', compact('users', 'storeProfiles', 'roles'));
    }
    // Method to list users with role 1 or 2
    public function index()
    {
        return $this->users();
    }
    public function sales()
    {
        return $this->users(2);
    }
    public function accountants()
    {
        return $this->users(3);
    }
    public function admins()
    {
        return $this->users(1);
    }

    public function accountManagers()
    {
        return $this->users(4);
    }

    public function customers()
    {
        return $this->users(5);
    }

    public function search(Request $request, $role = null)
    {
        $query = $request->input('search');

        $users = User::query();

        if ($role !== null) {
            $users->whereHas('roles', function ($q) use ($role) {
                $q->where('roles.id', intval($role));
            });
        }

        $users->where(function ($q) use ($query) {
            $q->where('users.name', 'like', "%{$query}%")
                ->orWhere('users.phone', 'like', "%{$query}%")
                ->orWhereHas('storeProfile', function ($q) use ($query) {
                    $q->where('stores_profile.name', 'like', "%{$query}%");
                });
        });

        $users = $users->with('storeProfile')->paginate(15)->appends($request->all());

        $showing = "<div class=\"mb-2 mb-md-0 mobile-results-count\">Showing {$users->firstItem()} to {$users->lastItem()} of {$users->total()} results</div>";

        return response()->json([
            'rows' => view('manager.partials.user_table_rows', compact('users'))->render(),
            'pagination' => '<div id="search-pagination">' . $showing . $users->links('vendor.pagination.bootstrap-5')->render() . '</div>',
        ]);
    }

    public function edit($id)
    {
        $user = User::with(array( 'storeProfile', 'roles' ))->findOrFail($id);

        if (request()->expectsJson()) {
            // Convert the user object to an array
            $userArray = $user->toArray();
            // Return the user data as a JSON response
            return response()->json($userArray);
        }

        // For normal non-AJAX request, return the edit view
        $storeProfiles = storesProfile::all()->toArray();
        $userRoles     = Role::all()->toArray();
        return view('manager.edit-user', compact('user', 'storeProfiles', 'userRoles'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Delete the user
        $user->delete();
        Cache::forget('total_user_count'); // Clear the cache
        // Return a success response
        return response()->json(array( 'message' => 'User deleted successfully!' ));
    }

    // Method to update the user in the database
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Validate request data
        $validated = $request->validate(
            array(
                'name'             => 'required|string|max:255',
                'email'            => 'required|email|max:255|unique:users,email,' . $user->id, // Ensure email is unique except for the current user
                'phone'            => 'nullable|string|max:20', // Phone can be optional
                'store_profile_id' => 'nullable|exists:stores_profile,id',
                'password'         => 'nullable|min:8|confirmed',
            )
        );
        // Check if the current authenticated user is an admin
        if (Auth::user()->roles->contains('name', 'admin')) {
            // If the user is an admin, make roles required
            $validated['roles']   = 'required|array';
            $validated['roles.*'] = 'exists:roles,id';
        }
        // Update only if a new password is provided
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->input('password'));
        } else {
            unset($validated['password']); // Do not update password if it's not provided
        }
        // Update user
        $user->update($validated);
        $user->roles()->sync($request->input('roles')); // Sync roles
        if (request()->expectsJson()) {
            return response()->json(array( 'message' => 'User updated successfully' ));
        }
        return redirect()->back()->with('success', 'User updated successfully');
    }
    public function store(Request $request)
    {
        $validated = $request->validate(
            array(
                'name'             => 'required|string|max:255',
                'email'            => 'required|email|max:255|unique:users',
                'phone'            => 'nullable|string|max:20|unique:users',
                'password'         => 'required|min:8|confirmed', // Required for creation
                'store_profile_id' => 'nullable|exists:stores_profile,id',
                'roles'            => 'required|array',
                'roles.*'          => 'exists:roles,id',
            )
        );

        $validated['password'] = bcrypt($request->input('password')); // Hash the password

        $user = User::create($validated);
        Cache::forget('total_user_count'); // Clear the cache
        $user->roles()->sync($request->input('roles')); // Sync roles
        return response()->json(array( 'message' => 'User created successfully' ));
    }

    public function searchUserHelper(Request $request)
    {
        // Extract search query
        $query = $request->input('search');

        // Ensure admin-only access
        $user = Auth::user();
        /*
        if (!$user->roles->contains('name', 'admin') ) {
            abort(403, 'Unauthorized action.');
        }
        */
        // Query for users based on phone number or name
        $users = User::query()
            ->select('name as buyer_name', 'phone as buyer_phone')
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('phone', 'like', "%$query%")
                    ->orWhere('name', 'like', "%$query%");
                });
            })
            ->orderBy('name')
            ->get();

        return $users;
    }
}
