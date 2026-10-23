<?php

namespace App\Services\CardArt;

use App\Models\CardCategory;

/**
 * Builds the "card_art" payload used to render HTML artwork for categories
 * without a poster image (typically Next Load products).
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

    private function assetIfExists(string $path): ?string
    {
        self::$fileExists[$path] ??= is_file(public_path($path));

        return self::$fileExists[$path] ? asset($path) : null;
    }
}
