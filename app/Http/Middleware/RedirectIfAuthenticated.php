<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use App\Services\RolePermissionService;
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
                    if (app(RolePermissionService::class)->userCanAccessManager($user)) {
                        return redirect(RouteServiceProvider::HOME);
                    }

                    // Signed in, but not staff. Drop the session so login can render.
                    Auth::guard($guard)->logout();

                    return $next($request);
                }
                
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
