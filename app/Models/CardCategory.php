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
