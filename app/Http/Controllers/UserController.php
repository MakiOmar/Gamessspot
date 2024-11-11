<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\StoresProfile;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function users($role = 'any')
    {
        if ('any' === $role) {
            $users = User::with('storeProfile')->paginate(10);
        } else {
            $users = User::whereHas(
                'roles',
                function ($query) use ($role) {
                    $query->where('id', intval($role));
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

    // Method to search users based on the input
    public function search(Request $request)
    {
        $query = $request->input('search');

        if (! empty($query)) {
            // Fetch users who have the 'sales' role (role ID 2)
            $users = User::whereHas(
                'roles',
                function ($q) {
                    $q->where('id', 2); // Role ID for 'sales'
                }
            )
                ->where(
                    function ($q) use ($query) {
                        // Search for the query in the user's name or the store profile's name
                        $q->where('name', 'like', "%{$query}%")
                        ->orWhereHas(
                            'storeProfile',
                            function ($q) use ($query) {
                                $q->where('name', 'like', "%{$query}%");
                            }
                        );
                    }
                )
                ->with('storeProfile')
                ->get();

            // Return the search result as a partial view to dynamically update the table rows
            return view('manager.partials.user_table_rows', compact('users'))->render();
        }

        return '';
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
                'phone'            => 'nullable|string|max:20',
                'password'         => 'required|min:8|confirmed', // Required for creation
                'store_profile_id' => 'nullable|exists:stores_profile,id',
                'roles'            => 'required|array',
                'roles.*'          => 'exists:roles,id',
            )
        );

        $validated['password'] = bcrypt($request->input('password')); // Hash the password

        $user = User::create($validated);
        $user->roles()->sync($request->input('roles')); // Sync roles
        return response()->json(array( 'message' => 'User created successfully' ));
    }
}
