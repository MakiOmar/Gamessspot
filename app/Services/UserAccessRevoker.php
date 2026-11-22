<?php

namespace App\Services;

use App\Models\User;

/**
 * Ends every live credential for a user: Sanctum tokens, remember cookie,
 * pending manager OTP, and stamps auth_revoked_at so open file sessions die
 * on the next manager request (see EnsureManagerStaff).
 */
class UserAccessRevoker
{
    public function revoke(User $user): void
    {
        $user->forceFill([
            'remember_token' => null,
            'auth_revoked_at' => now(),
            'login_otp_hash' => null,
            'login_otp_expires_at' => null,
        ])->save();

        $user->tokens()->delete();
    }
}
