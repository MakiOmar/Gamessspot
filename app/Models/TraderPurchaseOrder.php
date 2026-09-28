<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraderPurchaseOrder extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = array(
        'po_number',
        'trader_id',
        'purchase_date',
        'notes',
        'total_quantity',
        'total_cost',
        'status',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    );

    protected $casts = array(
        'purchase_date' => 'date',
        'total_quantity' => 'integer',
        'total_cost' => 'decimal:2',
        'cancelled_at' => 'datetime',
    );

    public function trader()
    {
        return $this->belongsTo(Trader::class);
    }

    public function items()
    {
        return $this->hasMany(TraderPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'purchase_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
