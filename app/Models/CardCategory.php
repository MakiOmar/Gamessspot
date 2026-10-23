<?php

namespace App\Models;

use App\Models\Concerns\HasCatalogExtras;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardCategory extends Model
{
    use HasFactory;
    use HasCatalogExtras;

    protected $table = 'card_categories';

    /** Sell only codes added by staff (default). */
    public const MODE_LOCAL_ONLY = 'local_only';

    /** Sell a local code, buy from Next Load when none is left. */
    public const MODE_LOCAL_FIRST = 'local_first';

    /** Always buy the code from Next Load at sale time. */
    public const MODE_SUPPLIER_ONLY = 'supplier_only';

    public const SUPPLIER_MODES = [self::MODE_LOCAL_ONLY, self::MODE_LOCAL_FIRST, self::MODE_SUPPLIER_ONLY];

    protected $fillable = [
        'name', 'price', 'poster_image', 'description',
        'supplier_mode', 'next_load_product_id', 'supplier_cost', 'supplier_synced_at',
        'supplier_product_name', 'art_brand', 'art_country', 'art_value', 'supplier_daily_limit',
    ];

    protected $hidden = [
        'pos_product_id', 'pos_active', 'supplier_mode', 'next_load_product_id', 'supplier_cost', 'supplier_synced_at',
        'supplier_product_name', 'art_brand', 'art_country', 'art_value', 'supplier_daily_limit',
    ];

    protected $casts = [
        'pos_product_id' => 'integer',
        'pos_variation_id' => 'integer',
        'pos_active' => 'boolean',
        'next_load_product_id' => 'integer',
        'supplier_synced_at' => 'datetime',
        'supplier_daily_limit' => 'integer',
    ];

    /** Sales may buy from Next Load (mode allows it and a product is linked). */
    public function usesSupplier(): bool
    {
        return in_array($this->supplier_mode, [self::MODE_LOCAL_FIRST, self::MODE_SUPPLIER_ONLY], true)
            && (int) $this->next_load_product_id > 0;
    }

    public function usesLocalStock(): bool
    {
        return $this->supplier_mode !== self::MODE_SUPPLIER_ONLY;
    }

    public function nextLoadOrders()
    {
        return $this->hasMany(NextLoadOrder::class, 'card_category_id');
    }

    /**
     * POS variation the shop should sell, or null so it falls back to the shared gift-card product.
     */
    public function activePosVariationId(): ?int
    {
        return $this->pos_active && $this->pos_variation_id ? (int) $this->pos_variation_id : null;
    }

    /**
     * Get the cards for the category.
     */
    public function cards()
    {
        return $this->hasMany(Card::class, 'card_category_id');
    }

    /**
     * Adds `available_count` (unsold local codes) without loading the code rows.
     */
    public function scopeWithAvailableCount($query)
    {
        return $query->withCount(['cards as available_count' => fn ($q) => $q->where('status', true)]);
    }

    public static function sanitizeDescription(?string $html): ?string
    {
        return HtmlSanitizer::sanitize($html);
    }
}
