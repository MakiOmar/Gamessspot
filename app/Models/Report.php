<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'seller_id',
        'status',
        'note',
    ];

    /**
     * Define the relationship between Report and Order.
     * A report belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Define the relationship between Report and User (Seller).
     * A report belongs to a seller.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
