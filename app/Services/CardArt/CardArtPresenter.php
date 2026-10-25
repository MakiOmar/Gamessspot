<?php

namespace App\Services\CardArt;

use App\Models\CardCategory;

/**
 * Builds the "card_art" payload used to render HTML artwork for categories
 * without a poster image (typically Next Load products), and the brand/country
 * "classification" used to browse gift cards.
 */
final class CardArtPresenter
{
    /** @var array<string, bool> */
    private static array $fileExists = [];

    public function __construct(private readonly CardArtParser $parser) {}

    /**
     * @return array{brand: string, brand_label: string, value: string, country_code: ?string, flag_url: ?string, logo_url: ?string, color: string}|null
     */
    public function forCategory(CardCategory $category): ?array
    {
        if (filled($category->poster_image)) {
            return null;
        }

        return $this->build(
            (string) ($category->supplier_product_name ?: $category->name),
            $category->art_brand,
            $category->art_country,
            $category->art_value,
        );
    }

    /**
     * @return array{brand: string, brand_label: string, value: string, country_code: ?string, flag_url: ?string, logo_url: ?string, color: string}|null
     */
    public function build(string $name, ?string $brand = null, ?string $country = null, ?string $value = null): ?array
    {
        $parsed = $this->parser->parse($name);
        $value = filled($value) ? trim($value) : $parsed['value'];
        if ($value === null) {
            return null;
        }

        $brand = isset(CardArtCatalog::BRANDS[$brand ?? '']) ? $brand : $parsed['brand'];
        $country = isset(CardArtCatalog::COUNTRIES[$country ?? '']) ? $country : $parsed['country'];
        $known = CardArtCatalog::BRANDS[$brand] ?? null;
        $flag = $country ?? ($parsed['global'] ? CardArtCatalog::GLOBAL_FLAG : null);

        return [
            'brand' => $brand,
            'brand_label' => $known['label'] ?? $parsed['label'],
            'value' => $value,
            'country_code' => $country,
            'flag_url' => $flag ? $this->assetIfExists(CardArtCatalog::flagPath($flag)) : null,
            'logo_url' => $known ? $this->assetIfExists(CardArtCatalog::logoPath($brand)) : null,
            'color' => $known['color'] ?? CardArtCatalog::GENERIC_COLOR,
        ];
    }

    /**
     * Brand and country used to browse cards (always returned, poster or not).
     * `country` is an ISO-2 code, "global" or "other".
     *
     * @return array{brand: string, brand_label: string, logo_url: ?string, color: string, country: string, country_label: string, flag_url: ?string}
     */
    public function classify(CardCategory $category): array
    {
        $parsed = $this->parser->parse((string) ($category->supplier_product_name ?: $category->name));

        $brand = isset(CardArtCatalog::BRANDS[$category->art_brand ?? '']) ? $category->art_brand : $parsed['brand'];
        $brand = isset(CardArtCatalog::BRANDS[$brand]) ? $brand : CardArtCatalog::OTHER_BRAND;

        $country = match (true) {
            isset(CardArtCatalog::COUNTRIES[$category->art_country ?? '']) => $category->art_country,
            $parsed['global'] => CardArtCatalog::GLOBAL_COUNTRY,
            $parsed['country'] !== null => $parsed['country'],
            default => CardArtCatalog::OTHER_COUNTRY,
        };
        $flag = match ($country) {
            CardArtCatalog::GLOBAL_COUNTRY => CardArtCatalog::GLOBAL_FLAG,
            CardArtCatalog::OTHER_COUNTRY => null,
            default => $country,
        };
        $known = CardArtCatalog::BRANDS[$brand] ?? null;

        return [
            'brand' => $brand,
            'brand_label' => CardArtCatalog::brandLabel($brand),
            'logo_url' => $known ? $this->assetIfExists(CardArtCatalog::logoPath($brand)) : null,
            'color' => $known['color'] ?? CardArtCatalog::GENERIC_COLOR,
            'country' => $country,
            'country_label' => CardArtCatalog::countryLabel($country),
            'flag_url' => $flag ? $this->assetIfExists(CardArtCatalog::flagPath($flag)) : null,
        ];
    }

    private function assetIfExists(string $path): ?string
    {
        self::$fileExists[$path] ??= is_file(public_path($path));

        return self::$fileExists[$path] ? asset($path) : null;
    }
}
