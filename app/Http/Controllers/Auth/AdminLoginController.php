<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminLoginOtpService;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function __construct(
        private AdminLoginOtpService $otpService,
        private RolePermissionService $roles,
    ) {
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
        $step = ($request->query('step') === 'code' || session('login_otp_email') || old('code'))
            ? 'code'
            : 'email';

        $resendAvailableIn = 0;
        if ($step === 'code' && is_string($otpEmail) && $otpEmail !== '') {
            $resendAvailableIn = $this->otpService->secondsUntilResendAllowedForEmail($otpEmail);
        }

        return response()
            ->view('adminlte::auth.login', [
                'loginStep' => $step,
                'otpEmail' => $otpEmail,
                'resendAvailableIn' => $resendAvailableIn,
                'resendCooldownSeconds' => max(1, (int) config('auth_otp.resend_cooldown_seconds', 60)),
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
        $result = $this->otpService->requestCode($email);

        $redirect = redirect()
            ->route('manager.login', ['step' => 'code'])
            ->with('login_otp_email', $email);

        return match ($result['outcome']) {
            AdminLoginOtpService::OUTCOME_COOLDOWN => $redirect
                ->with('status', 'Please wait '.(int) ($result['retry_after'] ?? 1).' seconds before requesting another code.')
                ->with('status_level', 'warning'),
            AdminLoginOtpService::OUTCOME_REUSED => $redirect
                ->with('status', 'We sent the same login code again. Earlier emails with that code still work. Check spam if needed.')
                ->with('status_level', 'success'),
            AdminLoginOtpService::OUTCOME_SENT => $redirect
                ->with('status', 'If that email is registered, a login code has been sent. Check spam if it is missing.')
                ->with('status_level', 'success'),
            default => $redirect
                ->with('status', 'If that email is registered, a login code has been sent.')
                ->with('status_level', 'success'),
        };
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

        if ($this->roles->userCanAccessManager($user)) {
            return redirect()->intended(route('manager.dashboard'));
        }

        Log::warning('User logged in but lacks a staff role', [
            'user_id' => $user->id,
            'user_roles' => $userRoles,
        ]);
        Auth::guard('admin')->logout();

        return redirect()->route('manager.login')->withErrors([
            'You do not have the required role to access this area. Your roles: '.implode(', ', $userRoles),
        ]);
    }
}
