<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoresProfile extends Model
{
    use HasFactory;

    protected $table = 'stores_profile';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = array(
        'name',
        'phone_number',
    );
    public function users()
    {
        return $this->hasMany(User::class, 'store_profile_id');
    }
    /**
     * A storeProfile can have many orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'store_profile_id');
    }
    // In StoresProfile model
    public function specialPrices()
    {
        return $this->hasMany(SpecialPrice::class, 'store_profile_id');
    }
    // In StoreProfile model
    public function isBlockedForGame($gameId)
    {
        // Correct the column name to 'store_profile_id'
        return $this->specialPrices()
            ->where('game_id', $gameId)
            ->where('store_profile_id', $this->id) // Ensure correct column name here
            ->where('is_available', false)
            ->exists();
    }

}
