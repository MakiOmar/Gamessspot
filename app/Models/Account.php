<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property string $mail
 * @property string $region
 * @property int $ps4_offline_stock
 * @property int $ps4_primary_stock
 * @property int $ps4_secondary_stock
 * @property int $ps5_offline_stock
 * @property int $ps5_primary_stock
 * @property int $ps5_secondary_stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Game> $games
 * @property-read int|null $games_count
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs4OfflineStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs4PrimaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs4SecondaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs5OfflineStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs5PrimaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePs5SecondaryStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail',
        'region',
        'ps4_offline_stock',
        'ps4_primary_stock',
        'ps4_secondary_stock',
        'ps5_offline_stock',
        'ps5_primary_stock',
        'ps5_secondary_stock',
        'game_id', // Include game_id in the fillable fields
    ];

    /**
     * The games that belong to the account.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
