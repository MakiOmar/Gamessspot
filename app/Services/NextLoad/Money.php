<?php

namespace App\Services\NextLoad;

/**
 * Next Load amounts are 3-decimal strings ("13.380"). They are compared as integer
 * thousandths so no float rounding ever decides whether a purchase is allowed.
 */
final class Money
{
    public static function toMillis(string|int|float|null $amount): int
    {
        $value = trim((string) ($amount ?? '0'));
        if ($value === '' || ! preg_match('/^(-)?(\d*)(?:\.(\d*))?$/', $value, $m)) {
            return 0;
        }

        $whole = (int) ($m[2] === '' ? '0' : $m[2]);
        $fraction = (int) str_pad(substr($m[3] ?? '', 0, 3), 3, '0');
        $millis = $whole * 1000 + $fraction;

        return $m[1] === '-' ? -$millis : $millis;
    }

    public static function fromMillis(int $millis): string
    {
        $sign = $millis < 0 ? '-' : '';
        $millis = abs($millis);

        return $sign.intdiv($millis, 1000).'.'.str_pad((string) ($millis % 1000), 3, '0', STR_PAD_LEFT);
    }

    public static function normalize(string|int|float|null $amount): string
    {
        return self::fromMillis(self::toMillis($amount));
    }

    /**
     * Supplier amount converted to local currency (NEXTLOAD_EXCHANGE_RATE local units per supplier unit), rounded up.
     */
    public static function toLocal(string|int|float|null $supplierAmount, string|int|float|null $rate): string
    {
        $product = self::toMillis($supplierAmount) * self::toMillis($rate);

        return self::fromMillis(intdiv($product + 999, 1000));
    }

    /** -1, 0 or 1 like the spaceship operator. */
    public static function compare(string|int|float|null $a, string|int|float|null $b): int
    {
        return self::toMillis($a) <=> self::toMillis($b);
    }
}
