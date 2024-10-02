<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    // Define the fillable fields (excluding _token)
    protected $fillable = [
        'title',
        'code',
        'full_price',
        'ps4_primary_price',
        'ps4_primary_status',
        'ps4_secondary_price',
        'ps4_secondary_status',
        'ps4_offline_price',
        'ps4_offline_status',
        'ps5_primary_price',
        'ps5_primary_status',
        'ps5_offline_price',
        'ps5_offline_status',
        'ps4_image_url',
        'ps5_image_url'
    ];

    /**
     * The accounts that belong to the game.
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class);
    }
}
