<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardCategory extends Model
{
    use HasFactory;

    protected $table = 'card_categories';

    protected $fillable = ['name', 'price', 'poster_image'];

    /**
     * Get the cards for the category.
     */
    public function cards()
    {
        return $this->hasMany(Card::class, 'card_category_id');
    }
}
