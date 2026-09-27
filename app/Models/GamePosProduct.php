<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Link from one sellable game offer (platform + offer) to its POS product.
 *
 * @property int $game_id
 * @property string $platform ps4|ps5 (later pc|xbox)
 * @property string $offer primary|secondary|offline|full
 * @property int $pos_product_id
 * @property int $pos_variation_id
 * @property bool $pos_active
 */
class GamePosProduct extends Model
{
    protected $fillable = [
        'game_id',
        'platform',
        'offer',
        'pos_product_id',
        'pos_variation_id',
        'pos_active',
    ];

    protected $casts = [
        'pos_product_id' => 'integer',
        'pos_variation_id' => 'integer',
        'pos_active' => 'boolean',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Active offer links keyed for the shop: [gameId => ['5' => ['primary' => ['product_id', 'variation_id']]]].
     * Shop platforms are digits (4/5); inactive offers are omitted so clients fall back to shared SKUs.
     *
     * @param  list<int>  $gameIds
     * @return array<int, array<string, array<string, array{product_id:int,variation_id:int}>>>
     */
    public static function shopOffersFor(array $gameIds): array
    {
        if ($gameIds === []) {
            return [];
        }

        $map = [];
        static::query()
            ->whereIn('game_id', $gameIds)
            ->where('pos_active', true)
            ->get()
            ->each(function (self $row) use (&$map) {
                $platform = preg_replace('/^ps/', '', $row->platform);
                $map[(int) $row->game_id][$platform][$row->offer] = [
                    'product_id' => (int) $row->pos_product_id,
                    'variation_id' => (int) $row->pos_variation_id,
                ];
            });

        return $map;
    }
}
