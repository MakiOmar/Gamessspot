<?php

namespace App\Services\CardArt;

/**
 * Brands, countries and currencies the generated gift card artwork knows about.
 * Flag and logo files live in public/images/card-art/{flags,brands}/.
 */
final class CardArtCatalog
{
    public const GENERIC_BRAND = 'generic';

    public const GENERIC_COLOR = '#4b5563';

    /** @var array<string, array{label: string, color: string, keywords: list<string>}> */
    public const BRANDS = [
        'playstation' => ['label' => 'PlayStation Store', 'color' => '#0070d1', 'keywords' => ['playstation', 'psn', 'ps plus', 'ps store']],
        'xbox' => ['label' => 'Xbox', 'color' => '#107c10', 'keywords' => ['xbox']],
        'amazon' => ['label' => 'Amazon', 'color' => '#232f3e', 'keywords' => ['amazon']],
        'apple' => ['label' => 'App Store & iTunes', 'color' => '#1d1d1f', 'keywords' => ['itunes', 'apple', 'app store']],
        'googleplay' => ['label' => 'Google Play', 'color' => '#01875f', 'keywords' => ['google play', 'googleplay']],
        'steam' => ['label' => 'Steam', 'color' => '#171a21', 'keywords' => ['steam']],
        'nintendo' => ['label' => 'Nintendo eShop', 'color' => '#e60012', 'keywords' => ['nintendo', 'eshop']],
    ];

    /** ISO-2 country => display name (only countries with a bundled flag). */
    public const COUNTRIES = [
        'us' => 'United States', 'gb' => 'United Kingdom', 'sa' => 'Saudi Arabia', 'ae' => 'United Arab Emirates',
        'eg' => 'Egypt', 'kw' => 'Kuwait', 'qa' => 'Qatar', 'bh' => 'Bahrain', 'om' => 'Oman', 'jo' => 'Jordan',
        'lb' => 'Lebanon', 'tr' => 'Turkey', 'eu' => 'European Union', 'de' => 'Germany', 'fr' => 'France',
        'it' => 'Italy', 'es' => 'Spain', 'nl' => 'Netherlands', 'ca' => 'Canada', 'au' => 'Australia',
        'in' => 'India', 'jp' => 'Japan', 'br' => 'Brazil',
    ];

    /** Words matched case-insensitively. */
    public const COUNTRY_WORDS = [
        'usa' => 'us', 'america' => 'us', 'ksa' => 'sa', 'saudi' => 'sa', 'uae' => 'ae', 'emirates' => 'ae',
        'egypt' => 'eg', 'kuwait' => 'kw', 'qatar' => 'qa', 'bahrain' => 'bh', 'oman' => 'om', 'jordan' => 'jo',
        'lebanon' => 'lb', 'turkey' => 'tr', 'europe' => 'eu', 'germany' => 'de', 'france' => 'fr', 'italy' => 'it',
        'spain' => 'es', 'netherlands' => 'nl', 'canada' => 'ca', 'australia' => 'au', 'india' => 'in',
        'japan' => 'jp', 'brazil' => 'br',
    ];

    /** Two-letter codes only count in upper case ("US", not the word "us"). */
    public const COUNTRY_CODES = [
        'US' => 'us', 'UK' => 'gb', 'GB' => 'gb', 'SA' => 'sa', 'AE' => 'ae', 'EG' => 'eg', 'KW' => 'kw',
        'QA' => 'qa', 'BH' => 'bh', 'OM' => 'om', 'JO' => 'jo', 'LB' => 'lb', 'TR' => 'tr', 'EU' => 'eu',
        'DE' => 'de', 'FR' => 'fr', 'IT' => 'it', 'ES' => 'es', 'NL' => 'nl', 'CA' => 'ca', 'AU' => 'au',
        'IN' => 'in', 'JP' => 'jp', 'BR' => 'br',
    ];

    /** Cards sold worldwide: globe icon instead of a flag, no country code. */
    public const GLOBAL_WORDS = ['global', 'international', 'worldwide'];

    public const GLOBAL_FLAG = 'globe';

    /** Currency code => [symbol shown before the amount or null for "100 SAR", default country]. */
    public const CURRENCIES = [
        'USD' => ['$', 'us'], 'GBP' => ['£', 'gb'], 'EUR' => ['€', 'eu'],
        'SAR' => [null, 'sa'], 'AED' => [null, 'ae'], 'EGP' => [null, 'eg'], 'KWD' => [null, 'kw'],
        'QAR' => [null, 'qa'], 'BHD' => [null, 'bh'], 'OMR' => [null, 'om'], 'JOD' => [null, 'jo'],
        'TRY' => [null, 'tr'], 'CAD' => [null, 'ca'], 'AUD' => [null, 'au'], 'INR' => [null, 'in'],
        'JPY' => [null, 'jp'], 'BRL' => [null, 'br'],
    ];

    public const SYMBOLS = ['$' => 'USD', '£' => 'GBP', '€' => 'EUR'];

    public static function brandKeys(): array
    {
        return array_keys(self::BRANDS);
    }

    public static function countryKeys(): array
    {
        return array_keys(self::COUNTRIES);
    }

    public static function flagPath(string $country): string
    {
        return 'images/card-art/flags/'.$country.'.svg';
    }

    public static function logoPath(string $brand): string
    {
        return 'images/card-art/brands/'.$brand.'.svg';
    }
}
