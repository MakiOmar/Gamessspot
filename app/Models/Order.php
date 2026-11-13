<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Report;
use App\Models\StoresProfile;

class Order extends Model
{
    use HasFactory;

    /** Seller filter value for {@see isWebsiteSale()} orders. */
    public const WEBSITE_SELLER = 'website';

    public const LEGACY_WEBSITE_STORE_PROFILE_ID = 17;

    // Define which fields are mass-assignable (fillable)
    protected $fillable = array(
        'seller_id',
        'store_profile_id',
        'account_id',
        'buyer_phone',
        'buyer_name',
        'price',
        'notes',
        'sold_item',
        'card_id',
        'pos_order_id',
        'woocommerce_order_id',
        'storefront_order_id',
        'storefront_line_key',
        'login_code',
        'stock_snapshot',
    );

    protected $casts = array(
        'stock_snapshot' => 'array',
    );

    /**
     * Get the seller associated with the order.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the account associated with the order.
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
    /**
     * An order can have many reports.
     */
    public function reports()
    {
        return $this->hasOne(Report::class, 'order_id');
    }
    /**
     * An order can have one storeProfile.
     */
    public function storeProfile()
    {
        return $this->belongsTo(StoresProfile::class, 'store_profile_id');
    }

    public function card()
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Sold online with no staff seller: storefront orders, or the legacy WooCommerce store profile (17).
     */
    public function isWebsiteSale(): bool
    {
        return $this->storefront_order_id !== null
            || ((int) $this->store_profile_id === self::LEGACY_WEBSITE_STORE_PROFILE_ID && $this->seller_id === null);
    }

    /**
     * Same rule as {@see isWebsiteSale()}, as a query.
     */
    public function scopeWebsiteSales(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNotNull('orders.storefront_order_id')
            ->orWhere(fn (Builder $legacy) => $legacy->where('orders.store_profile_id', self::LEGACY_WEBSITE_STORE_PROFILE_ID)->whereNull('orders.seller_id')));
    }

    /**
     * @param int|string $seller A seller user id, or {@see WEBSITE_SELLER}
     */
    public function scopeSoldBy(Builder $query, int|string $seller): Builder
    {
        return $seller === self::WEBSITE_SELLER
            ? $query->websiteSales()
            : $query->where('orders.seller_id', (int) $seller);
    }
}
