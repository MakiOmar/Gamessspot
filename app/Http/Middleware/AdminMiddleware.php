<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ( Auth::check() ) {
            // Eager load roles to prevent N+1 query problem
            $user = Auth::user()->loadMissing('roles');

            // Check if user has one of the allowed roles by name
            // Get allowed roles dynamically from database
            $allowedRoles = Role::getAllRoleNames();
            if (
                $user->roles->contains(function ($role) use ($allowedRoles) {
                    return in_array($role->name, $allowedRoles);
                })
            ) {
                return $next($request);
            }
        }

        // If the user is not authorized or is disabled, redirect to the home page or return an unauthorized response
        Auth::logout(); // Log out the user if they are disabled
        return redirect('/')->with('error', 'Unauthorized Access or Account Disabled');
    }
}
