<?php

namespace App\Services;

use App\Mail\AdminLoginOtpMail;
use App\Models\User;
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

    public function __construct(
        private OneTimeCodeAttempts $codeAttempts,
        private RolePermissionService $roles,
    ) {
    }

    /**
     * Send a login OTP when the email matches an active staff user.
     * Always returns without revealing whether the email exists.
     */
    public function requestCode(string $email): void
    {
        $email = strtolower(trim($email));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', 1)
            ->first();

        if (! $user || empty($user->email) || ! $this->roles->userCanAccessManager($user)) {
            return;
        }

        if (! $this->passesWhitelist($user)) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->login_otp_hash = Hash::make($code);
        $user->login_otp_expires_at = now()->addMinutes(
            max(1, (int) config('auth_otp.expire_minutes', 10))
        );
        $user->save();
        $this->codeAttempts->clear(self::PURPOSE, (string) $user->id);

        try {
            // Sync send — manager login must not depend on a queue worker.
            Mail::to($user->email)->sendNow(new AdminLoginOtpMail($user, $code));
        } catch (\Throwable $e) {
            Log::warning('Admin login OTP email failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
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

        if (! $this->passesWhitelist($user)) {
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
                $user->login_otp_hash = null;
                $user->login_otp_expires_at = null;
                $user->save();
            }
            throw $invalid;
        }

        $user->login_otp_hash = null;
        $user->login_otp_expires_at = null;
        $user->save();
        $this->codeAttempts->clear(self::PURPOSE, (string) $user->id);

        return $user;
    }

    /**
     * When ADMIN_OTP_WHITELIST is set, only listed id+email pairs are eligible.
     */
    private function passesWhitelist(User $user): bool
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

        Log::warning('Admin login OTP blocked by whitelist.', [
            'user_id' => $userId,
            'email' => $email,
            'expected_email' => $expected,
            'listed' => array_key_exists($userId, $whitelist),
        ]);

        return false;
    }
}
