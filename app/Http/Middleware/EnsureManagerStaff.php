<?php

namespace App\Http\Middleware;

use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manager pages require a staff role. Customer sessions are signed out
 * and sent back to the login form instead of a blank 403.
 */
class EnsureManagerStaff
{
    public function __construct(private RolePermissionService $roles)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('admin');

        if ($user && $this->roles->userCanAccessManager($user)) {
            return $next($request);
        }

        Auth::guard('admin')->logout();

        return redirect()->route('manager.login')->withErrors([
            'email' => 'This account cannot open the manager. Sign in with a staff email.',
        ]);
    }
}
