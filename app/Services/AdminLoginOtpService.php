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
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', 1)
            ->first();

        if (! $user || empty($user->email) || ! $this->roles->userCanAccessManager($user)) {
            return ['outcome' => self::OUTCOME_SKIPPED];
        }

        if (! $this->passesWhitelist($user, logBlock: true)) {
            return ['outcome' => self::OUTCOME_SKIPPED];
        }

        $retryAfter = $this->secondsUntilResendAllowed($user);
        if ($retryAfter > 0) {
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
            throw $invalid;
        }

        if (! $this->passesWhitelist($user, logBlock: true)) {
            throw $invalid;
        }

        if (
            empty($user->login_otp_hash)
            || empty($user->login_otp_expires_at)
            || now()->gt($user->login_otp_expires_at)
        ) {
            throw $invalid;
        }

        if (! Hash::check($code, $user->login_otp_hash)) {
            if ($this->codeAttempts->recordFailure(self::PURPOSE, (string) $user->id)) {
                $this->clearOtp($user);
            }
            throw $invalid;
        }

        $this->clearOtp($user);
        $this->codeAttempts->clear(self::PURPOSE, (string) $user->id);

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
            Log::warning('Admin login OTP blocked by whitelist.', [
                'user_id' => $userId,
                'email' => $email,
                'expected_email' => $expected,
                'listed' => array_key_exists($userId, $whitelist),
            ]);
        }

        return false;
    }

    /**
     * @return array{outcome: string}
     */
    private function sendMail(User $user, string $code, bool $reused): array
    {
        try {
            // Sync send — manager login must not depend on a queue worker.
            Mail::to($user->email)->sendNow(new AdminLoginOtpMail($user, $code));
        } catch (\Throwable $e) {
            Log::warning('Admin login OTP email failed.', [
                'user_id' => $user->id,
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

        return [
            'outcome' => $reused ? self::OUTCOME_REUSED : self::OUTCOME_SENT,
        ];
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
