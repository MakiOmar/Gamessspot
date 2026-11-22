<?php

namespace App\Services;

use App\Mail\AdminLoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Issue and verify 6-digit email OTP for manager (admin guard) login.
 */
class AdminLoginOtpService
{
    public const PURPOSE = 'admin-login';

    public const OUTCOME_SENT = 'sent';

    public const OUTCOME_REUSED = 'reused';

    public const OUTCOME_SKIPPED = 'skipped';

    public const OUTCOME_COOLDOWN = 'cooldown';

    public function __construct(
        private OneTimeCodeAttempts $codeAttempts,
        private RolePermissionService $roles,
    ) {
    }

    /**
     * Send a login OTP when the email matches an active staff user.
     * Unknown / ineligible addresses return OUTCOME_SKIPPED (no enumeration).
     *
     * Within the resend cooldown, returns OUTCOME_COOLDOWN (no mail). After cooldown,
     * a still-valid code is re-mailed unchanged; otherwise a new code is issued.
     *
     * @return array{outcome: string, retry_after?: int}
     */
    public function requestCode(string $email): array
    {
        $email = strtolower(trim($email));
        $base = $this->logContext(email: $email);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', 1)
            ->first();

        if (! $user || empty($user->email) || ! $this->roles->userCanAccessManager($user)) {
            $this->logRequest('blocked', 'ineligible', $base + [
                'found_active' => (bool) $user,
                'has_email' => $user ? ! empty($user->email) : false,
                'manager_access' => $user ? $this->roles->userCanAccessManager($user) : false,
            ]);

            return ['outcome' => self::OUTCOME_SKIPPED];
        }

        $base = $this->logContext(email: $email, user: $user);

        if (! $this->passesWhitelist($user, logBlock: true)) {
            $this->logRequest('blocked', 'whitelist', $base, warning: true);

            return ['outcome' => self::OUTCOME_SKIPPED];
        }

        $retryAfter = $this->secondsUntilResendAllowed($user);
        if ($retryAfter > 0) {
            $this->logRequest('blocked', self::OUTCOME_COOLDOWN, $base + [
                'retry_after' => $retryAfter,
            ]);

            return [
                'outcome' => self::OUTCOME_COOLDOWN,
                'retry_after' => $retryAfter,
            ];
        }

        $payload = $this->cachedPayload($user);
        $reuse = is_array($payload)
            && $this->hasActiveOtp($user)
            && is_string($payload['code'] ?? null)
            && preg_match('/^\d{6}$/', $payload['code']);

        if ($reuse) {
            $code = $payload['code'];
            $this->rememberPayload(
                $user,
                $code,
                (int) ($user->login_otp_expires_at?->getTimestamp() ?? now()->addMinutes($this->expireMinutes())->getTimestamp())
            );

            return $this->sendMail($user, $code, reused: true);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes($this->expireMinutes());
        $user->login_otp_hash = Hash::make($code);
        $user->login_otp_expires_at = $expiresAt;
        $user->save();
        $this->codeAttempts->clear(self::PURPOSE, (string) $user->id);
        $this->rememberPayload($user, $code, $expiresAt->getTimestamp());

        return $this->sendMail($user, $code, reused: false);
    }

    /**
     * Seconds until another OTP email may be sent for this address (0 = allowed now).
     */
    public function secondsUntilResendAllowedForEmail(string $email): int
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return 0;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', 1)
            ->first();

        if (! $user || ! $this->roles->userCanAccessManager($user) || ! $this->passesWhitelist($user, logBlock: false)) {
            return 0;
        }

        return $this->secondsUntilResendAllowed($user);
    }

    /**
     * Verify OTP and return the user, or throw a generic validation error.
     */
    public function verifyCode(string $email, string $code): User
    {
        $email = strtolower(trim($email));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $invalid = ValidationException::withMessages([
            'code' => ['Invalid or expired login code.'],
        ]);

        if (! $user || (int) $user->is_active !== 1 || ! $this->roles->userCanAccessManager($user)) {
            $this->logVerify('failed', 'ineligible', $this->logContext(email: $email, user: $user));
            throw $invalid;
        }

        $base = $this->logContext(email: $email, user: $user);

        if (! $this->passesWhitelist($user, logBlock: true)) {
            $this->logVerify('failed', 'whitelist', $base);
            throw $invalid;
        }

        if (
            empty($user->login_otp_hash)
            || empty($user->login_otp_expires_at)
            || now()->gt($user->login_otp_expires_at)
        ) {
            $this->logVerify('failed', 'missing_or_expired', $base + [
                'has_hash' => ! empty($user->login_otp_hash),
                'expires_at' => $user->login_otp_expires_at?->toIso8601String(),
            ]);
            throw $invalid;
        }

        if (! Hash::check($code, $user->login_otp_hash)) {
            $burned = $this->codeAttempts->recordFailure(self::PURPOSE, (string) $user->id);
            if ($burned) {
                $this->clearOtp($user);
            }
            $this->logVerify('failed', 'bad_code', $base + [
                'burned' => $burned,
            ]);
            throw $invalid;
        }

        $this->clearOtp($user);
        $this->codeAttempts->clear(self::PURPOSE, (string) $user->id);
        $this->logVerify('ok', 'accepted', $base);

        return $user;
    }

    /**
     * When ADMIN_OTP_WHITELIST is set, only listed id+email pairs are eligible.
     */
    private function passesWhitelist(User $user, bool $logBlock = true): bool
    {
        /** @var array<int, string> $whitelist */
        $whitelist = config('auth_otp.whitelist', []);
        if ($whitelist === []) {
            return true;
        }

        $userId = (int) $user->id;
        $email = strtolower(trim((string) $user->email));
        $expected = $whitelist[$userId] ?? null;

        if ($expected !== null && $expected === $email) {
            return true;
        }

        if ($logBlock) {
            Log::warning('Admin login OTP: whitelist_block', [
                'purpose' => self::PURPOSE,
                'user_id' => $userId,
                'email' => $email,
                'expected_email' => $expected,
                'listed' => array_key_exists($userId, $whitelist),
                'ip' => request()?->ip(),
            ]);
        }

        return false;
    }

    /**
     * @return array{outcome: string}
     */
    private function sendMail(User $user, string $code, bool $reused): array
    {
        $context = $this->logContext(email: (string) $user->email, user: $user) + [
            'reused' => $reused,
            'mailer' => (string) config('mail.default'),
            'mail_from' => (string) config('mail.from.address'),
            'expires_at' => $user->login_otp_expires_at?->toIso8601String(),
        ];

        Log::info('Admin login OTP: send_attempt', $context);

        $started = microtime(true);

        try {
            // Sync send — manager login must not depend on a queue worker.
            Mail::to($user->email)->sendNow(new AdminLoginOtpMail($user, $code));
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            Log::warning('Admin login OTP: send_failed', $context + [
                'duration_ms' => $durationMs,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
            report($e);

            if (! $reused) {
                $this->clearOtp($user);
            } else {
                $this->forgetCache($user);
            }

            // Same generic outcome as an unknown address — do not reveal mail failure.
            return ['outcome' => self::OUTCOME_SKIPPED];
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $outcome = $reused ? self::OUTCOME_REUSED : self::OUTCOME_SENT;
        Log::info('Admin login OTP: send_ok', $context + [
            'duration_ms' => $durationMs,
            'outcome' => $outcome,
        ]);

        return [
            'outcome' => $outcome,
        ];
    }

    /**
     * Shared context for OTP diagnostics (never includes the plaintext code).
     *
     * @return array{purpose: string, email: string, ip?: string|null, user_id?: int, name?: string|null}
     */
    private function logContext(string $email, ?User $user = null): array
    {
        $context = [
            'purpose' => self::PURPOSE,
            'email' => $email,
            'ip' => request()?->ip(),
        ];

        if ($user) {
            $context['user_id'] = (int) $user->id;
            $context['name'] = $user->name;
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logRequest(string $result, string $reason, array $context, bool $warning = false): void
    {
        $payload = $context + [
            'result' => $result,
            'reason' => $reason,
        ];

        if ($warning) {
            Log::warning('Admin login OTP: request', $payload);
        } else {
            Log::info('Admin login OTP: request', $payload);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logVerify(string $result, string $reason, array $context): void
    {
        $level = $result === 'ok' ? 'info' : 'warning';
        Log::{$level}('Admin login OTP: verify', $context + [
            'result' => $result,
            'reason' => $reason,
        ]);
    }

    private function secondsUntilResendAllowed(User $user): int
    {
        $payload = $this->cachedPayload($user);
        if (! is_array($payload) || ! isset($payload['sent_at'])) {
            return 0;
        }

        $cooldown = max(1, (int) config('auth_otp.resend_cooldown_seconds', 60));
        $elapsed = now()->getTimestamp() - (int) $payload['sent_at'];

        return max(0, $cooldown - $elapsed);
    }

    private function hasActiveOtp(User $user): bool
    {
        return ! empty($user->login_otp_hash)
            && ! empty($user->login_otp_expires_at)
            && now()->lte($user->login_otp_expires_at);
    }

    private function expireMinutes(): int
    {
        return max(1, (int) config('auth_otp.expire_minutes', 10));
    }

    private function cacheKey(User $user): string
    {
        return 'admin-login-otp:'.$user->id;
    }

    /**
     * @return array{code?: string, sent_at?: int}|null
     */
    private function cachedPayload(User $user): ?array
    {
        $payload = Cache::get($this->cacheKey($user));

        return is_array($payload) ? $payload : null;
    }

    private function rememberPayload(User $user, string $code, int $expiresAtUnix): void
    {
        $ttl = max(1, $expiresAtUnix - now()->getTimestamp());
        Cache::put($this->cacheKey($user), [
            'code' => $code,
            'sent_at' => now()->getTimestamp(),
        ], $ttl);
    }

    private function forgetCache(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    private function clearOtp(User $user): void
    {
        $user->login_otp_hash = null;
        $user->login_otp_expires_at = null;
        $user->save();
        $this->forgetCache($user);
    }
}
