<?php

/**
 * Manager login email OTP.
 *
 * ADMIN_OTP_WHITELIST: comma-separated user_id:email pairs. When non-empty, only
 * those id+email matches may receive or verify a manager login code (empty = all
 * active staff with manager access, as before).
 */
$whitelistRaw = (string) env('ADMIN_OTP_WHITELIST', '');
$whitelist = [];
foreach (explode(',', $whitelistRaw) as $segment) {
    $segment = trim($segment);
    if ($segment === '' || ! str_contains($segment, ':')) {
        continue;
    }
    [$idPart, $emailPart] = explode(':', $segment, 2);
    $id = (int) trim($idPart);
    $email = strtolower(trim($emailPart));
    if ($id > 0 && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $whitelist[$id] = $email;
    }
}

return [
    /** Minutes until a issued login code expires. */
    'expire_minutes' => max(1, (int) env('AUTH_OTP_EXPIRE_MINUTES', 10)),

    /** Max OTP issue requests per minute (IP + email). */
    'issue_per_minute' => max(1, (int) env('AUTH_OTP_ISSUE_PER_MINUTE', 5)),

    /**
     * Minimum seconds between OTP emails for the same user.
     * Within this window a new request is skipped (no mail); after it, a still-valid
     * code is re-sent unchanged instead of minting a new one.
     */
    'resend_cooldown_seconds' => max(1, (int) env('AUTH_OTP_RESEND_COOLDOWN_SECONDS', 60)),

    /**
     * Map of user id => lowercase email. Empty disables the gate.
     *
     * @var array<int, string>
     */
    'whitelist' => $whitelist,

    /**
     * When true, manager UI (and other write paths) cannot change email on
     * staff/admin users (any role except customer). Customer emails stay editable.
     */
    'email_lock' => filter_var(env('ADMIN_EMAIL_LOCK', true), FILTER_VALIDATE_BOOLEAN),
];
