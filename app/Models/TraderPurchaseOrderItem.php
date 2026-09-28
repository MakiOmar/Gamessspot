<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TraderPurchaseOrderItem extends Model
{
    protected $fillable = array(
        'purchase_order_id',
        'game_id',
        'quantity',
        'cost_per_account',
        'total_cost',
    );

    protected $casts = array(
        'quantity' => 'integer',
        'cost_per_account' => 'decimal:2',
        'total_cost' => 'decimal:2',
    );

    public function purchaseOrder()
    {
        return $this->belongsTo(TraderPurchaseOrder::class, 'purchase_order_id');
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'purchase_order_item_id');
    }

    /**
     * Imported count — uses withCount('accounts') value when eager loaded.
     */
    public function importedCount(): int
    {
        if (array_key_exists('accounts_count', $this->attributes)) {
            return (int) $this->attributes['accounts_count'];
        }

        return $this->accounts()->count();
    }

    public function remaining(): int
    {
        return max(0, (int) $this->quantity - $this->importedCount());
    }
}
