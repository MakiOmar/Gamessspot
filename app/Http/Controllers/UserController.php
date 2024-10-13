<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\StoresProfile;

class UserController extends Controller
{
    // Method to list users with role 1 or 2
    public function index()
    {
        $users = User::whereIn('role', [1, 2])->with('storeProfile')->paginate(10);
        $storeProfiles = StoresProfile::all(); // Fetch all store profiles
        return view('manager.users', compact('users', 'storeProfiles'));
    }


    // Method to search users based on the input
    public function search(Request $request)
    {
        $query = $request->input('search');

        if (!empty($query)) {
            $users = User::whereIn('role', [1, 2])
                ->where(function ($q) use ($query) {
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
        $user = User::with('storeProfile')->findOrFail($id);
        return response()->json($user); // Return user data as JSON
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

        ]);
        // Update only if a new password is provided
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->input('password'));
        } else {
            unset($validated['password']); // Do not update password if it's not provided
        }
        // Update user
        $user->update($validated);

        return response()->json(['message' => 'User updated successfully']);
    }
}
