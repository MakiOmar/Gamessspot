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

    protected $fillable = array(
        'mail',
        'password',
        'game_id',
        'region',
        'cost',
        'birthdate',
        'login_code',
        'is_full',
        'ps4_primary_stock',
        'ps4_secondary_stock',
        'ps4_offline_stock',
        'ps5_primary_stock',
        'ps5_secondary_stock',
        'ps5_offline_stock',
    );

    protected $casts = [
        'is_full' => 'boolean',
    ];

    /**
     * Resolve initial platform stocks for account creation.
     *
     * @param  array{ps4_primary?:bool,ps4_secondary?:bool,ps5_primary?:bool,ps5_secondary?:bool,ps4_offline1?:bool,ps4_offline2?:bool,ps5_offline?:bool}  $soldFlags
     * @return array{ps4_primary_stock:int,ps4_secondary_stock:int,ps4_offline_stock:int,ps5_primary_stock:int,ps5_secondary_stock:int,ps5_offline_stock:int}
     */
    public static function resolveInitialStocks(bool $isFull, bool $ps5Only, array $soldFlags = []): array
    {
        if ($isFull) {
            if ($ps5Only) {
                return [
                    'ps4_primary_stock' => 0,
                    'ps4_secondary_stock' => 0,
                    'ps4_offline_stock' => 0,
                    'ps5_primary_stock' => 1,
                    'ps5_secondary_stock' => 1,
                    'ps5_offline_stock' => 1,
                ];
            }

            return [
                'ps4_primary_stock' => 1,
                'ps4_secondary_stock' => 1,
                'ps4_offline_stock' => 1,
                'ps5_primary_stock' => 1,
                'ps5_secondary_stock' => 1,
                'ps5_offline_stock' => 1,
            ];
        }

        $stocks = [
            'ps4_primary_stock' => 1,
            'ps4_secondary_stock' => 1,
            'ps4_offline_stock' => 2,
            'ps5_primary_stock' => 1,
            'ps5_secondary_stock' => 1,
            'ps5_offline_stock' => 1,
        ];

        if ($ps5Only) {
            $stocks['ps4_primary_stock'] = 0;
            $stocks['ps4_secondary_stock'] = 0;
            $stocks['ps4_offline_stock'] = 0;
            $stocks['ps5_offline_stock'] = 2;

            return $stocks;
        }

        if (!empty($soldFlags['ps4_primary'])) {
            $stocks['ps4_primary_stock'] = 0;
        }
        if (!empty($soldFlags['ps4_secondary'])) {
            $stocks['ps4_secondary_stock'] = 0;
        }
        if (!empty($soldFlags['ps5_primary'])) {
            $stocks['ps5_primary_stock'] = 0;
        }
        if (!empty($soldFlags['ps5_secondary'])) {
            $stocks['ps5_secondary_stock'] = 0;
        }
        if (!empty($soldFlags['ps4_offline1'])) {
            $stocks['ps4_offline_stock'] = 1;
        }
        if (!empty($soldFlags['ps4_offline2'])) {
            $stocks['ps4_offline_stock'] = 0;
        }
        if (!empty($soldFlags['ps5_offline'])) {
            $stocks['ps5_offline_stock'] = 0;
        }

        return $stocks;
    }

    /**
     * Whether this full account still has a sellable bundle on the platform.
     */
    public function hasSellableFullBundle(int|string $platform): bool
    {
        if (!$this->is_full) {
            return false;
        }

        return (int) $this->{"ps{$platform}_primary_stock"} > 0
            && (int) $this->{"ps{$platform}_secondary_stock"} > 0
            && (int) $this->{"ps{$platform}_offline_stock"} > 0;
    }

    /**
     * The games that belong to the account.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
