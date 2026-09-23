<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemOpsUnlocked
{
    /**
     * Require SYSTEM_OPS_PASSWORD unlock in session (in addition to admin auth).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = config('system_ops.session_key', 'system_ops_unlocked');

        if ($request->session()->get($sessionKey) === true) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(array(
                'success' => false,
                'message' => 'System Ops is locked. Unlock with the configured password.',
            ), 403);
        }

        return redirect()->route('manager.system-ops.unlock');
    }
}
