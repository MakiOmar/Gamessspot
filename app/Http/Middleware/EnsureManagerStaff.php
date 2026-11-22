<?php

namespace App\Http\Middleware;

use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manager pages require an active staff role. Customer / deactivated /
 * revoked sessions are signed out and sent back to the login form.
 */
class EnsureManagerStaff
{
    public function __construct(private RolePermissionService $roles)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('admin');

        if (
            $user
            && (int) $user->is_active === 1
            && $this->roles->userCanAccessManager($user)
            && ! $this->sessionWasRevoked($request, $user)
        ) {
            return $next($request);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('manager.login')->withErrors([
            'email' => 'This account cannot open the manager. Sign in with a staff email.',
        ]);
    }

    private function sessionWasRevoked(Request $request, $user): bool
    {
        if (! $user->auth_revoked_at) {
            return false;
        }

        $issuedAt = (int) $request->session()->get('admin_auth_at', 0);

        return $issuedAt < $user->auth_revoked_at->getTimestamp();
    }
}
