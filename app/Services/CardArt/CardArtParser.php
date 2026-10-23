<?php

namespace App\Services\CardArt;

/**
 * Reads brand, face value and country out of a gift card product name
 * such as "amazon KSA 100 SAR", "Amazon UK 10 £" or "PSN US $10".
 */
final class CardArtParser
{
    private const AMOUNT = '(?<![\d.,])(\d+(?:[.,]\d+)?)';

    /**
     * @return array{brand: string, label: string, value: ?string, currency: ?string, country: ?string, global: bool}
     */
    public function parse(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        [$amount, $currency, $matched] = $this->amount($name);
        $brand = $this->brand($name);
        $global = $this->hasWord($name, CardArtCatalog::GLOBAL_WORDS);
        $country = $global ? null : ($this->country($name) ?? ($currency ? CardArtCatalog::CURRENCIES[$currency][1] : null));

        return [
            'brand' => $brand,
            'label' => $brand === CardArtCatalog::GENERIC_BRAND
                ? $this->genericLabel($name, $matched)
                : CardArtCatalog::BRANDS[$brand]['label'],
            'value' => $amount !== null && $currency !== null ? self::formatValue($amount, $currency) : null,
            'currency' => $currency,
            'country' => $country,
            'global' => $global,
        ];
    }

    public static function formatValue(string $amount, string $currency): string
    {
        $amount = preg_match('/^\d+,\d{3}$/', $amount) ? str_replace(',', '', $amount) : str_replace(',', '.', $amount);
        $amount = str_contains($amount, '.') ? rtrim(rtrim($amount, '0'), '.') : $amount;
        $symbol = CardArtCatalog::CURRENCIES[$currency][0] ?? null;

        return $symbol !== null ? $symbol.$amount : $amount.' '.$currency;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string} amount, currency code, matched text
     */
    private function amount(string $name): array
    {
        $codes = implode('|', array_keys(CardArtCatalog::CURRENCIES));
        $symbols = implode('|', array_map(fn ($s) => preg_quote($s, '/'), array_keys(CardArtCatalog::SYMBOLS)));

        $patterns = [
            '/('.$symbols.')\s*'.self::AMOUNT.'/u' => fn ($m) => [$m[2], CardArtCatalog::SYMBOLS[$m[1]]],
            '/'.self::AMOUNT.'\s*('.$symbols.')/u' => fn ($m) => [$m[1], CardArtCatalog::SYMBOLS[$m[2]]],
            '/'.self::AMOUNT.'\s*('.$codes.')(?![A-Za-z])/iu' => fn ($m) => [$m[1], strtoupper($m[2])],
            '/(?<![A-Za-z])('.$codes.')\s*'.self::AMOUNT.'/iu' => fn ($m) => [$m[2], strtoupper($m[1])],
        ];
        foreach ($patterns as $pattern => $extract) {
            if (preg_match($pattern, $name, $m)) {
                return [...$extract($m), $m[0]];
            }
        }

        return [null, null, null];
    }

    private function brand(string $name): string
    {
        foreach (CardArtCatalog::BRANDS as $key => $brand) {
            if ($this->hasWord($name, $brand['keywords'])) {
                return $key;
            }
        }

        return CardArtCatalog::GENERIC_BRAND;
    }

    private function country(string $name): ?string
    {
        foreach (CardArtCatalog::COUNTRY_CODES as $code => $country) {
            if (preg_match('/(?<![A-Za-z])'.$code.'(?![A-Za-z])/u', $name)) {
                return $country;
            }
        }
        foreach (CardArtCatalog::COUNTRY_WORDS as $word => $country) {
            if ($this->hasWord($name, [$word])) {
                return $country;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $words
     */
    private function hasWord(string $name, array $words): bool
    {
        foreach ($words as $word) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($word, '/').'(?![\p{L}\p{N}])/iu', $name)) {
                return true;
            }
        }

        return false;
    }

    /** Unknown brand: the name without the amount and country tokens, e.g. "Razer Gold". */
    private function genericLabel(string $name, ?string $matched): string
    {
        $label = $matched !== null ? str_replace($matched, ' ', $name) : $name;
        $tokens = array_filter(explode(' ', $label), function (string $token) {
            $plain = trim($token, '()-–,');

            return $plain !== ''
                && ! isset(CardArtCatalog::COUNTRY_CODES[$plain])
                && ! isset(CardArtCatalog::COUNTRY_WORDS[mb_strtolower($plain)])
                && ! in_array(mb_strtolower($plain), CardArtCatalog::GLOBAL_WORDS, true);
        });
        $label = trim(implode(' ', $tokens));

        return $label === '' ? 'Gift Card' : mb_strimwidth($label, 0, 24, '…');
    }
}
