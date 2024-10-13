<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleAssignmentController extends Controller
{
    public function assignRolesBasedOnQuery()
    {
        // Find the admin role and sales role
        $adminRole = Role::where('name', 'admin')->first();
        $salesRole = Role::where('name', 'sales')->first();

        // Assign admin role to user with id 1
        $adminUser = User::find(1);
        if ($adminUser) {
            $adminUser->roles()->sync([$adminRole->id]);
        }

        // Assign sales role to all other users except user with id 1
        $salesUsers = User::where('id', '!=', 1)->get();
        foreach ($salesUsers as $user) {
            $user->roles()->sync([$salesRole->id]);
        }

        return 'Roles assigned successfully!';
    }
}
