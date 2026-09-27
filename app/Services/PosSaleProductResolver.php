<?php

namespace App\Services;

use App\Models\CardCategory;
use App\Models\GamePosProduct;

/**
 * Picks the POS product a sold order line should reference: the synced per-offer (or per-category)
 * product when linked, otherwise the shared placeholder configured in settings.
 */
class PosSaleProductResolver
{
    /**
     * @param  array<string, string>  $posSkus  shared placeholder SKUs keyed by type
     * @param  array<string, int|string>  $posIds  shared placeholder product ids keyed by type
     */
    public function __construct(
        private readonly array $posSkus,
        private readonly array $posIds,
    ) {}

    public static function fromSettings(): self
    {
        return new self(SettingsService::getPosSkus(), SettingsService::getPosIds());
    }

    /**
     * @return array{sku:string, pos_product_id:int|string|null, type:string, platform:string}
     */
    public function forGame(string $soldItem, int $gameId): array
    {
        [$platform, $offer] = self::parseSoldItem($soldItem);

        // Inactive links still resolve: the offer may be switched off after this account was sold.
        $link = GamePosProduct::query()
            ->where('game_id', $gameId)
            ->where('platform', $platform)
            ->where('offer', $offer)
            ->first();

        if ($link && $link->pos_product_id) {
            return [
                'sku' => PosCatalogSync::gameSku($gameId, $platform, $offer),
                'pos_product_id' => (int) $link->pos_product_id,
                'type' => $offer,
                'platform' => $platform,
            ];
        }

        return [
            'sku' => (string) ($this->posSkus[$offer] ?? $this->posSkus['primary'] ?? ''),
            'pos_product_id' => $this->posIds[$offer] ?? $this->posIds['primary'] ?? null,
            'type' => $offer,
            'platform' => $platform,
        ];
    }

    /**
     * @return array{sku:string, pos_product_id:int|string|null, type:string, platform:string}
     */
    public function forCard(?CardCategory $category): array
    {
        if ($category && $category->pos_product_id) {
            return [
                'sku' => PosCatalogSync::cardSku((int) $category->id),
                'pos_product_id' => (int) $category->pos_product_id,
                'type' => 'card',
                'platform' => 'card',
            ];
        }

        return [
            'sku' => (string) ($this->posSkus['card'] ?? ''),
            'pos_product_id' => $this->posIds['card'] ?? null,
            'type' => 'card',
            'platform' => 'card',
        ];
    }

    /**
     * `ps5_primary_stock` → [ps5, primary]; `ps4_full` → [ps4, full].
     *
     * @return array{0:string, 1:string}
     */
    public static function parseSoldItem(string $soldItem): array
    {
        $parts = explode('_', $soldItem);
        $platform = $parts[0] ?? '';
        $offer = $parts[1] ?? 'primary';

        return [$platform, in_array($offer, PosCatalogSync::OFFERS, true) ? $offer : 'primary'];
    }
}
