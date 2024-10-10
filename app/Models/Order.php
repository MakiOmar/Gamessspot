<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Define which fields are mass-assignable (fillable)
    protected $fillable = [
        'seller_id',
        'account_id',
        'buyer_phone',
        'buyer_name',
        'price',
        'notes',
        'sold_item'
    ];

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
}

