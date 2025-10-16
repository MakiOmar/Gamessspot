<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if the authenticated user has one of the allowed roles
        if ( Auth::check() ) {
            // Eager load roles to prevent N+1 query problem
            $user = Auth::user()->loadMissing('roles');

            if (
                $user->roles->contains(function ($role) use ($roles) {
                    return in_array($role->name, $roles);
                })
            ) {
                return $next($request);  // Allow the request to proceed
            }
        }

        // If the user does not have the required roles, abort with a 403 forbidden error
        abort(403, 'Unauthorized action.');
    }
}
