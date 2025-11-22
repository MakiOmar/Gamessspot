<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // First, check if user exists and if account is inactive
        $user = User::where('phone', $request->phone)->first();

        // If user exists and password is correct but account is inactive
        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->is_active != 1) {
                Log::warning('Login attempt failed - account is inactive', [
                    'phone' => $request->phone,
                    'user_id' => $user->id,
                ]);
                return redirect()->back()->withInput($request->only('phone', 'remember'))->withErrors(
                    array(
                        'phone' => 'Your account has been deactivated. Please contact support.',
                    )
                );
            }
        }

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
            
            $userRoles = $user->roles->pluck('name')->toArray();
            
            Log::info('Authenticated user roles', [
                'user_id' => $user->id,
                'roles' => $userRoles,
            ]);
            
            // Check if user has any roles at all
            if ($user->roles->isEmpty()) {
                Log::warning('User logged in but has no roles assigned', [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                ]);
                Auth::guard('admin')->logout();
                return redirect()->route('manager.login')->withErrors(array( 'Your account has no roles assigned. Please contact an administrator.' ));
            }
            
            // Check if the authenticated user has 'admin', 'sales', 'accountatnt', or 'account manager' role
            // Note: 'accountatnt' is the actual role name in database (typo)
            $allowedRoles = array( 'admin', 'sales', 'accountatnt', 'account manager' );
            $hasValidRole = $user->roles->contains(
                function ($role) use ($allowedRoles) {
                    return in_array($role->name, $allowedRoles);
                }
            );

            Log::info('Role check result', [
                'has_valid_role' => $hasValidRole,
                'user_roles' => $userRoles,
                'allowed_roles' => $allowedRoles,
            ]);

            if ($hasValidRole) {
                // If the user has one of the allowed roles, redirect to the dashboard
                Log::info('Login successful, redirecting to dashboard');
                return redirect()->intended(route('manager.dashboard'));
            }
            // If the user doesn't have one of the specified roles, log them out and show an error
            Log::warning('User logged in but lacks required roles', [
                'user_id' => $user->id,
                'user_roles' => $userRoles,
                'allowed_roles' => $allowedRoles,
            ]);
            Auth::guard('admin')->logout();
            return redirect()->route('manager.login')->withErrors(array( 'You do not have the required role to access this area. Your roles: ' . implode(', ', $userRoles) ));
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
