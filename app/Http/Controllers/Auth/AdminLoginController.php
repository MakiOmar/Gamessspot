<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminLoginOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function __construct(private AdminLoginOtpService $otpService)
    {
        $this->middleware('guest:admin')->except('logout');
    }

    /**
     * Show the manager (admin) login form (email OTP by default).
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm(Request $request)
    {
        // Regenerate CSRF token on each login page load to prevent 419 errors
        if (! $request->isMethod('post')) {
            session()->regenerateToken();
        }

        $otpEmail = old('email', session('login_otp_email'));
        $step = 'email';
        if ($request->query('method') === 'password') {
            $step = 'password';
        } elseif ($request->query('step') === 'code' || session('login_otp_email') || old('code')) {
            $step = 'code';
        }

        return response()
            ->view('auth.manager-login', [
                'loginStep' => $step,
                'otpEmail' => $otpEmail,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Email a 6-digit login code when the address matches an active staff user.
     */
    public function requestOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($data['email']));
        $this->otpService->requestCode($email);

        return redirect()
            ->route('manager.login', ['step' => 'code'])
            ->with('login_otp_email', $email)
            ->with('status', 'If that email is registered, a login code has been sent.');
    }

    /**
     * Verify the emailed OTP and start an admin session.
     */
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'remember' => 'sometimes|boolean',
        ]);

        $email = strtolower(trim($data['email']));

        try {
            $user = $this->otpService->verifyCode($email, $data['code']);
        } catch (ValidationException $e) {
            return redirect()
                ->route('manager.login', ['step' => 'code'])
                ->with('login_otp_email', $email)
                ->withInput($request->only('email', 'remember'))
                ->withErrors($e->errors());
        }

        Auth::guard('admin')->login($user, (bool) $request->boolean('remember'));
        $request->session()->forget('login_otp_email');
        $request->session()->regenerate();

        return $this->finishAdminLogin($user);
    }

    /**
     * Handle phone + password login (fallback while email OTP is primary).
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->is_active != 1) {
                Log::warning('Login attempt failed - account is inactive', [
                    'phone' => $request->phone,
                    'user_id' => $user->id,
                ]);

                return redirect()->back()->withInput($request->only('phone', 'remember'))->withErrors([
                    'phone' => 'Your account has been deactivated. Please contact support.',
                ]);
            }
        }

        $attemptResult = Auth::guard('admin')->attempt([
            'phone' => $request->phone,
            'password' => $request->password,
            'is_active' => 1,
        ], $request->boolean('remember'));

        if ($attemptResult) {
            $user = Auth::guard('admin')->user();
            $request->session()->regenerate();

            return $this->finishAdminLogin($user);
        }

        Log::warning('Login failed - invalid credentials', [
            'phone' => $request->phone,
        ]);

        return redirect()
            ->route('manager.login', ['method' => 'password'])
            ->withInput($request->only('phone', 'remember'))
            ->withErrors([
                'phone' => 'These credentials do not match our records.',
            ]);
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

    /**
     * After Auth::login / attempt: require a valid role, else log out.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    private function finishAdminLogin(User $user)
    {
        $user->loadMissing('roles');
        $userRoles = $user->roles->pluck('name')->toArray();

        if ($user->roles->isEmpty()) {
            Log::warning('User logged in but has no roles assigned', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
            Auth::guard('admin')->logout();

            return redirect()->route('manager.login')->withErrors([
                'Your account has no roles assigned. Please contact an administrator.',
            ]);
        }

        $allowedRoles = Role::getAllRoleNames();
        $hasValidRole = $user->roles->contains(function ($role) use ($allowedRoles) {
            return in_array($role->name, $allowedRoles);
        });

        if ($hasValidRole) {
            return redirect()->intended(route('manager.dashboard'));
        }

        Log::warning('User logged in but lacks required roles', [
            'user_id' => $user->id,
            'user_roles' => $userRoles,
            'allowed_roles' => $allowedRoles,
        ]);
        Auth::guard('admin')->logout();

        return redirect()->route('manager.login')->withErrors([
            'You do not have the required role to access this area. Your roles: '.implode(', ', $userRoles),
        ]);
    }
}
