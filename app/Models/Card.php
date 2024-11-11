<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'cost', 'card_category_id'];

    /**
     * Get the category that the card belongs to.
     */
    public function category()
    {
        return $this->belongsTo(CardCategory::class, 'card_category_id');
    }
}
