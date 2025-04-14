<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Report;
use App\Models\StoresProfile;

class Order extends Model
{
    use HasFactory;

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
}
