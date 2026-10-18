<?php

namespace App\Services;

use App\Models\Account;

/**
 * Hands out the one-time login codes stored on an account (space- or comma-separated).
 */
class AccountLoginCodeService
{
    /** Order types that deliver a login code to the buyer. */
    public const CODE_TYPES = array('primary', 'secondary', 'full');

    public static function deliversCode(string $type): bool
    {
        return in_array($type, self::CODE_TYPES, true);
    }

    /**
     * @return list<string>
     */
    public function parse(?string $raw): array
    {
        $parts = preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);

        return array_values($parts ?: array());
    }

    /**
     * Remove and return the first code; null when none are left. Call inside the sale's transaction.
     */
    public function take(Account $account): ?string
    {
        $codes = $this->parse($account->login_code);
        if ($codes === array()) {
            return null;
        }

        $code = array_shift($codes);
        $account->login_code = implode(' ', $codes);
        $account->save();

        return $code;
    }

    /**
     * Put a code back at the front of the list (e.g. when its sale is undone).
     */
    public function giveBack(Account $account, string $code): void
    {
        $codes = $this->parse($account->login_code);
        if (in_array($code, $codes, true)) {
            return;
        }

        array_unshift($codes, $code);
        $account->login_code = implode(' ', $codes);
        $account->save();
    }
}
