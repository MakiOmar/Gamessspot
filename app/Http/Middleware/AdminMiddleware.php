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
        if (
            Auth::check() &&
            Auth::user()->roles->contains(function ($role) {
                return in_array($role->id, [1, 2, 3, 4]); // Role IDs for 'admin', 'sales', 'accountant'
            }) &&
            Auth::user()->is_active // Ensure the user is active
        ) {
            return $next($request);
        }

        // If the user is not authorized or is disabled, redirect to the home page or return an unauthorized response
        Auth::logout(); // Log out the user if they are disabled
        return redirect('/')->with('error', 'Unauthorized Access or Account Disabled');
    }
}
