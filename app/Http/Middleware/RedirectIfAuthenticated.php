<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? array( null ) : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                // For admin guard, check if user has proper roles before redirecting
                // This prevents redirect loops when user is authenticated but lacks roles
                if ($guard === 'admin') {
                    $user->loadMissing('roles');
                    // Get allowed roles dynamically from database
                    $allowedRoles = Role::getAllRoleNames();
                    if ($user->roles->contains(function ($role) use ($allowedRoles) {
                        return in_array($role->name, $allowedRoles);
                    })) {
                        return redirect(RouteServiceProvider::HOME);
                    } else {
                        // User is authenticated but doesn't have required roles
                        // Log them out and allow them to see the login page
                        Auth::guard($guard)->logout();
                        return $next($request);
                    }
                }
                
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
