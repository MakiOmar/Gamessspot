<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }

    /**
     * Show the manager (admin) login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Regenerate CSRF token on each login page load to prevent 419 errors
        if ( ! request()->isMethod('post') ) {
            session()->regenerateToken();
        }

        // Prevent caching of login page to ensure fresh CSRF token
        return response()
            ->view('auth.manager-login')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Handle the admin login request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Validate the form data
        $request->validate(
            array(
                'phone'    => 'required|string',
                'password' => 'required|string',
            )
        );

        // Debug: Log login attempt
        Log::info('Login attempt', [
            'phone' => $request->phone,
            'has_password' => !empty($request->password),
        ]);

        // Attempt to log the manager in using phone number and password
        // Include is_active check in credentials to prevent inactive users from logging in
        $attemptResult = Auth::guard('admin')->attempt(
            array(
                'phone'    => $request->phone,
                'password' => $request->password,
                'is_active' => 1,
            ),
            $request->remember
        );

        Log::info('Login attempt result', [
            'success' => $attemptResult,
            'authenticated_user_id' => Auth::guard('admin')->check() ? Auth::guard('admin')->id() : null,
        ]);

        if ($attemptResult) {
            // Get the authenticated user and ensure roles are loaded
            $user = Auth::guard('admin')->user();
            $user->loadMissing('roles');
            
            Log::info('Authenticated user roles', [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
            // Check if the authenticated user has 'admin', 'sales', 'accountatnt', or 'account manager' role
            // Note: 'accountatnt' is the actual role name in database (typo)
            $hasValidRole = $user->roles->contains(
                function ($role) {
                    return in_array($role->name, array( 'admin', 'sales', 'accountatnt', 'account manager' ));
                }
            );

            Log::info('Role check result', [
                'has_valid_role' => $hasValidRole,
            ]);

            if ($hasValidRole) {
                // If the user has one of the allowed roles, redirect to the dashboard
                Log::info('Login successful, redirecting to dashboard');
                return redirect()->intended(route('manager.dashboard'));
            }
            // If the user doesn't have one of the specified roles, log them out and show an error
            Log::warning('User logged in but lacks required roles', [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            Auth::guard('admin')->logout();
            return redirect()->route('manager.login')->withErrors(array( 'You do not have the required role to access this area.' ));
        }

        // If unsuccessful, redirect back with an error
        Log::warning('Login failed - invalid credentials', [
            'phone' => $request->phone,
        ]);
        return redirect()->back()->withInput($request->only('phone', 'remember'))->withErrors(
            array(
                'phone' => 'These credentials do not match our records.',
            )
        );
    }

    /**
     * Log the admin out.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/manager/login');
    }
}
