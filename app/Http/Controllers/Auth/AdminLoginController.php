<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminLoginOtpService;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
     * Show the manager (admin) login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm(Request $request)
    {
        // Regenerate CSRF token on each login page load to prevent 419 errors
        if (! $request->isMethod('post')) {
            session()->regenerateToken();
        }

        $loginStep = $this->resolveLoginStep($request);
        $otpEmail = old('email', session('login_otp_email'));

        $resendAvailableIn = 0;
        if ($loginStep === 'code' && is_string($otpEmail) && $otpEmail !== '') {
            $resendAvailableIn = $this->otpService->secondsUntilResendAllowedForEmail($otpEmail);
        }

        return response()
            ->view('adminlte::auth.login', [
                'loginStep' => $loginStep,
                'otpEmail' => $otpEmail,
                'loginMethod' => $this->loginMethod(),
                'passwordLoginEnabled' => $this->passwordLoginEnabled(),
                'otpLoginEnabled' => $this->otpLoginEnabled(),
                'resendAvailableIn' => $resendAvailableIn,
                'resendCooldownSeconds' => max(1, (int) config('auth_otp.resend_cooldown_seconds', 60)),
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Phone + password login (when STAFF_LOGIN_METHOD is password or both).
     */
    public function login(Request $request)
    {
        $this->ensurePasswordLoginAllowed();

        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $attemptResult = Auth::guard('admin')->attempt([
            'phone' => $request->phone,
            'password' => $request->password,
            'is_active' => 1,
        ], false);

        if ($attemptResult) {
            $this->clearLoginAttempts($request);
            $user = Auth::guard('admin')->user();
            $request->session()->regenerate();
            $request->session()->put('admin_auth_at', now()->getTimestamp());

            return $this->finishAdminLogin($user);
        }

        $user = User::where('phone', $request->phone)->first();
        if ($user && (int) $user->is_active !== 1) {
            Log::warning('Login attempt failed - account is inactive', [
                'phone' => $request->phone,
                'user_id' => $user->id,
            ]);
            $this->incrementLoginAttempts($request);

            return redirect()
                ->route('manager.login')
                ->withInput($request->only('phone'))
                ->withErrors([
                    'phone' => 'Your account has been deactivated. Please contact support.',
                ]);
        }

        $this->incrementLoginAttempts($request);
        Log::warning('Login failed - invalid credentials', [
            'phone' => $request->phone,
        ]);

        return redirect()
            ->route('manager.login')
            ->withInput($request->only('phone'))
            ->withErrors([
                'phone' => 'These credentials do not match our records.',
            ]);
    }

    /**
     * Email a 6-digit login code when the address matches an active staff user.
     */
    public function requestOtp(Request $request)
    {
        $this->ensureOtpLoginAllowed();

        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($data['email']));
        $result = $this->otpService->requestCode($email);

        $redirect = redirect()
            ->route('manager.login', $this->otpRouteParams(['step' => 'code']))
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
        $this->ensureOtpLoginAllowed();

        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $email = strtolower(trim($data['email']));

        try {
            $user = $this->otpService->verifyCode($email, $data['code']);
        } catch (ValidationException $e) {
            return redirect()
                ->route('manager.login', $this->otpRouteParams(['step' => 'code']))
                ->with('login_otp_email', $email)
                ->withInput($request->only('email'))
                ->withErrors($e->errors());
        }

        Auth::guard('admin')->login($user);
        $request->session()->forget('login_otp_email');
        $request->session()->regenerate();
        $request->session()->put('admin_auth_at', now()->getTimestamp());

        return $this->finishAdminLogin($user);
    }

    /**
     * Log the admin out.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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

    private function loginMethod(): string
    {
        $method = strtolower((string) config('auth_otp.login_method', 'otp'));

        return in_array($method, ['otp', 'password', 'both'], true) ? $method : 'otp';
    }

    private function passwordLoginEnabled(): bool
    {
        return in_array($this->loginMethod(), ['password', 'both'], true);
    }

    private function otpLoginEnabled(): bool
    {
        return in_array($this->loginMethod(), ['otp', 'both'], true);
    }

    private function ensurePasswordLoginAllowed(): void
    {
        if (! $this->passwordLoginEnabled()) {
            abort(404);
        }
    }

    private function ensureOtpLoginAllowed(): void
    {
        if (! $this->otpLoginEnabled()) {
            abort(404);
        }
    }

    /**
     * Keep OTP redirects on the OTP path when dual-mode would otherwise default to password.
     *
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function otpRouteParams(array $extra = []): array
    {
        if ($this->loginMethod() === 'both') {
            $extra['method'] = 'otp';
        }

        return $extra;
    }

    private function resolveLoginStep(Request $request): string
    {
        $method = $this->loginMethod();

        if ($method === 'password') {
            return 'password';
        }

        $wantsOtp = $method === 'otp'
            || $request->query('method') === 'otp'
            || $request->query('step') === 'code'
            || session()->has('login_otp_email')
            || old('code');

        if (! $wantsOtp && $this->passwordLoginEnabled()) {
            return 'password';
        }

        if ($request->query('step') === 'code' || session('login_otp_email') || old('code')) {
            return 'code';
        }

        return 'email';
    }

    private function hasTooManyLoginAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->throttleKey($request),
            max(1, (int) config('auth_otp.password_max_attempts', 5))
        );
    }

    private function incrementLoginAttempts(Request $request): void
    {
        RateLimiter::hit(
            $this->throttleKey($request),
            max(1, (int) config('auth_otp.password_decay_minutes', 1)) * 60
        );
    }

    private function clearLoginAttempts(Request $request): void
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    private function sendLockoutResponse(Request $request)
    {
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'phone' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ])->status(429);
    }

    private function throttleKey(Request $request): string
    {
        return strtolower((string) $request->input('phone')).'|'.$request->ip();
    }
}
