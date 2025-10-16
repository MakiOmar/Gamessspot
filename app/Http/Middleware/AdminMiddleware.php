<?php

namespace App\Http\Middleware;

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

            if (
                $user->roles->contains(function ($role) {
                    return in_array($role->id, array( 1, 2, 3, 4 )); // Role IDs for 'admin', 'sales', 'accountant'
                }) &&
                $user->is_active // Ensure the user is active
            ) {
                return $next($request);
            }
        }

        // If the user is not authorized or is disabled, redirect to the home page or return an unauthorized response
        Auth::logout(); // Log out the user if they are disabled
        return redirect('/')->with('error', 'Unauthorized Access or Account Disabled');
    }
}
