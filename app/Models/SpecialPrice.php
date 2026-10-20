<?php

namespace App\Models;

use App\Services\CacheManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'store_profile_id',
        'ps4_primary_price',
        'ps4_secondary_price',
        'ps4_offline_price',
        'ps5_primary_price',
        'ps5_secondary_price',
        'ps5_offline_price',
        'is_available',
    ];

    protected static function booted(): void
    {
        // Store-specific game listings are cached with these prices applied.
        static::saved(fn () => CacheManager::invalidateGames());
        static::deleted(fn () => CacheManager::invalidateGames());
    }

    // Relation to the Game model
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // Relation to the StoreProfile model
    public function storeProfile()
    {
        return $this->belongsTo(StoresProfile::class, 'store_profile_id');
    }
}
