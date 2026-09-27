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

    protected $fillable = ['name', 'price', 'poster_image', 'description'];

    protected $hidden = ['pos_product_id', 'pos_active'];

    protected $casts = [
        'pos_product_id' => 'integer',
        'pos_variation_id' => 'integer',
        'pos_active' => 'boolean',
    ];

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

    public static function sanitizeDescription(?string $html): ?string
    {
        return HtmlSanitizer::sanitize($html);
    }
}
