<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $mail
 * @property string $region
 * @property bool $is_full
 * @property int $ps4_offline_stock
 * @property int $ps4_primary_stock
 * @property int $ps4_secondary_stock
 * @property int $ps5_offline_stock
 * @property int $ps5_primary_stock
 * @property int $ps5_secondary_stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Game|null $game
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
     * $isFull is ignored — full sell is a feature flag, not a stock profile.
     *
     * @param  array{ps4_primary?:bool,ps4_secondary?:bool,ps5_primary?:bool,ps5_secondary?:bool,ps4_offline1?:bool,ps4_offline2?:bool,ps5_offline?:bool}  $soldFlags
     * @return array{ps4_primary_stock:int,ps4_secondary_stock:int,ps4_offline_stock:int,ps5_primary_stock:int,ps5_secondary_stock:int,ps5_offline_stock:int}
     */
    public static function resolveInitialStocks(bool $isFull, bool $ps5Only, array $soldFlags = []): array
    {
        // $isFull intentionally unused: enabling full feature never changes stock numbers.
        unset($isFull);

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
     * Whether stocks match pristine dual or PS5-only defaults (nothing sold yet).
     */
    public function isPristineForFullFeature(): bool
    {
        return self::stocksArePristine([
            'ps4_primary_stock' => (int) $this->ps4_primary_stock,
            'ps4_secondary_stock' => (int) $this->ps4_secondary_stock,
            'ps4_offline_stock' => (int) $this->ps4_offline_stock,
            'ps5_primary_stock' => (int) $this->ps5_primary_stock,
            'ps5_secondary_stock' => (int) $this->ps5_secondary_stock,
            'ps5_offline_stock' => (int) $this->ps5_offline_stock,
        ]);
    }

    /**
     * @param  array{ps4_primary_stock:int,ps4_secondary_stock:int,ps4_offline_stock:int,ps5_primary_stock:int,ps5_secondary_stock:int,ps5_offline_stock:int}  $stocks
     */
    public static function stocksArePristine(array $stocks): bool
    {
        $dual = [
            'ps4_primary_stock' => 1,
            'ps4_secondary_stock' => 1,
            'ps4_offline_stock' => 2,
            'ps5_primary_stock' => 1,
            'ps5_secondary_stock' => 1,
            'ps5_offline_stock' => 1,
        ];

        $ps5Only = [
            'ps4_primary_stock' => 0,
            'ps4_secondary_stock' => 0,
            'ps4_offline_stock' => 0,
            'ps5_primary_stock' => 1,
            'ps5_secondary_stock' => 1,
            'ps5_offline_stock' => 2,
        ];

        $normalized = [];
        foreach (array_keys($dual) as $key) {
            $normalized[$key] = (int) ($stocks[$key] ?? 0);
        }

        return $normalized === $dual || $normalized === $ps5Only;
    }

    /**
     * PS5-only accounts have all PS4 stocks at zero (no DB column for ps5_only).
     */
    public function isPs5OnlyAccount(): bool
    {
        return (int) $this->ps4_primary_stock === 0
            && (int) $this->ps4_secondary_stock === 0
            && (int) $this->ps4_offline_stock === 0;
    }

    /**
     * Whether this account can be sold as a full bundle right now.
     * Dual: PS4 secondary+offline and all PS5 slots > 0 (PS4 primary is not part of the bundle).
     * PS5-only: all PS5 slots > 0.
     */
    public function canSellAsFull(): bool
    {
        if (!$this->is_full) {
            return false;
        }

        if ($this->isPs5OnlyAccount()) {
            return (int) $this->ps5_primary_stock > 0
                && (int) $this->ps5_secondary_stock > 0
                && (int) $this->ps5_offline_stock > 0;
        }

        return (int) $this->ps4_secondary_stock > 0
            && (int) $this->ps4_offline_stock > 0
            && (int) $this->ps5_primary_stock > 0
            && (int) $this->ps5_secondary_stock > 0
            && (int) $this->ps5_offline_stock > 0;
    }

    /**
     * @deprecated Use canSellAsFull() — full sell is cross-platform, not per-platform.
     */
    public function hasSellableFullBundle(int|string $platform): bool
    {
        return $this->canSellAsFull();
    }

    /**
     * Apply a full-account sale: zero bundle stocks, keep PS4 primary, clear feature flag.
     */
    public function applyFullSale(): void
    {
        $this->ps4_secondary_stock = 0;
        $this->ps4_offline_stock = 0;
        $this->ps5_primary_stock = 0;
        $this->ps5_secondary_stock = 0;
        $this->ps5_offline_stock = 0;
        $this->is_full = false;
        $this->save();
    }

    /**
     * Restore stocks after undoing a full sale (does not change ps4_primary).
     * Re-enables is_full when stocks become pristine again.
     */
    public function restoreFullSaleBundle(): void
    {
        if ($this->isPs5OnlyAccount()
            && (int) $this->ps5_primary_stock === 0
            && (int) $this->ps5_secondary_stock === 0
            && (int) $this->ps5_offline_stock === 0
        ) {
            $this->ps5_primary_stock = 1;
            $this->ps5_secondary_stock = 1;
            $this->ps5_offline_stock = 2;
        } else {
            if ((int) $this->ps4_secondary_stock === 0) {
                $this->ps4_secondary_stock = 1;
            }
            if ((int) $this->ps4_offline_stock === 0) {
                $this->ps4_offline_stock = 2;
            }
            if ((int) $this->ps5_primary_stock === 0) {
                $this->ps5_primary_stock = 1;
            }
            if ((int) $this->ps5_secondary_stock === 0) {
                $this->ps5_secondary_stock = 1;
            }
            if ((int) $this->ps5_offline_stock === 0) {
                $this->ps5_offline_stock = 1;
            }
        }

        $this->save();

        if ($this->isPristineForFullFeature()) {
            $this->is_full = true;
            $this->save();
        }
    }

    /**
     * Clear the full-sell feature after an individual slot sale (or when no longer pristine).
     */
    public function disableFullFeatureIfNeeded(): void
    {
        if (!$this->is_full) {
            return;
        }

        if (!$this->isPristineForFullFeature()) {
            $this->is_full = false;
            $this->save();
        }
    }

    /**
     * SQL fragment: accounts eligible to sell as full (feature on + bundle stocks).
     */
    public static function fullSellEligibleSql(string $table = 'accounts'): string
    {
        return "{$table}.is_full = 1 AND {$table}.ps5_primary_stock > 0 AND {$table}.ps5_secondary_stock > 0 AND {$table}.ps5_offline_stock > 0"
            . ' AND ('
            . "({$table}.ps4_primary_stock = 0 AND {$table}.ps4_secondary_stock = 0 AND {$table}.ps4_offline_stock = 0)"
            . " OR ({$table}.ps4_secondary_stock > 0 AND {$table}.ps4_offline_stock > 0)"
            . ')';
    }

    /**
     * Scope: accounts with full-sell feature and required bundle stocks.
     */
    public function scopeEligibleForFullSell($query)
    {
        $table = $query->getModel()->getTable();

        return $query->whereRaw('(' . self::fullSellEligibleSql($table) . ')');
    }

    /**
     * The games that belong to the account.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
