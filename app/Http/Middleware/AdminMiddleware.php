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
            Auth::check() && Auth::user()->roles->contains(
                function ($role) {
                    return in_array($role->id, array( 1, 2, 3 )); // Role IDs for 'admin', 'sales', and 'accountant'
                }
            )
        ) {
            return $next($request);
        }

        // If the user is not an admin, redirect to the home page or return an unauthorized response
        return redirect('/')->with('error', 'Unauthorized Access');
    }
}
