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
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user()->loadMissing('roles');

            if (
                $user->roles->contains(function ($role) use ($roles) {
                    return in_array($role->name, $roles);
                })
            ) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized action.');
    }
}
