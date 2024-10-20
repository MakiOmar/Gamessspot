<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\StoresProfile;
use App\Models\Role;

class UserController extends Controller
{
    // Method to list users with role 1 or 2
    public function index()
    {
    // Fetch users who have role with id 2 (sales)
        $users = User::whereHas('roles', function ($query) {
            $query->where('id', 2); // Role ID 2 corresponds to 'sales'
        })->with('storeProfile')->paginate(10);

        $storeProfiles = StoresProfile::all(); // Fetch all store profiles

        $roles = Role::all(); // Fetch all available roles

        return view('manager.users', compact('users', 'storeProfiles', 'roles'));
    }

    // Method to search users based on the input
    public function search(Request $request)
    {
        $query = $request->input('search');

        if (!empty($query)) {
            // Fetch users who have the 'sales' role (role ID 2)
            $users = User::whereHas('roles', function ($q) {
                    $q->where('id', 2); // Role ID for 'sales'
            })
                ->where(function ($q) use ($query) {
                    // Search for the query in the user's name or the store profile's name
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhereHas('storeProfile', function ($q) use ($query) {
                            $q->where('name', 'like', "%{$query}%");
                        });
                })
                ->with('storeProfile')
                ->get();

            // Return the search result as a partial view to dynamically update the table rows
            return view('manager.partials.user_table_rows', compact('users'))->render();
        }

        return '';
    }

    public function edit($id)
    {
        $user = User::with(['storeProfile', 'roles'])->findOrFail($id);
        // Convert the user object to an array
        $userArray = $user->toArray();

        // Return the user data as a JSON response
        return response()->json($userArray);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Delete the user
        $user->delete();

        // Return a success response
        return response()->json(['message' => 'User deleted successfully!']);
    }

    // Method to update the user in the database
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Validate request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id, // Ensure email is unique except for the current user
            'phone' => 'nullable|string|max:20', // Phone can be optional
            'store_profile_id' => 'nullable|exists:stores_profile,id',
            'password' => 'nullable|min:8|confirmed', // Optional password field, must be at least 8 characters if filled
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);
        // Update only if a new password is provided
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->input('password'));
        } else {
            unset($validated['password']); // Do not update password if it's not provided
        }
        // Update user
        $user->update($validated);
        $user->roles()->sync($request->input('roles')); // Sync roles
        return response()->json(['message' => 'User updated successfully']);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users',
        'phone' => 'nullable|string|max:20',
        'password' => 'required|min:8|confirmed', // Required for creation
        'store_profile_id' => 'nullable|exists:stores_profile,id',
        'roles' => 'required|array',
        'roles.*' => 'exists:roles,id',
        ]);

        $validated['password'] = bcrypt($request->input('password')); // Hash the password

        $user = User::create($validated);
        $user->roles()->sync($request->input('roles')); // Sync roles
        return response()->json(['message' => 'User created successfully']);
    }
}
