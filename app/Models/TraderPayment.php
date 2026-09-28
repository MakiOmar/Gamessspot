<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraderPayment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    public const METHODS = array(
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'instapay' => 'InstaPay',
        'wallet' => 'Wallet',
        'other' => 'Other',
    );

    protected $fillable = array(
        'payment_number',
        'trader_id',
        'amount',
        'payment_date',
        'method',
        'reference_number',
        'notes',
        'attachment_path',
        'status',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    );

    protected $casts = array(
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'cancelled_at' => 'datetime',
    );

    public function trader()
    {
        return $this->belongsTo(Trader::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst((string) $this->method);
    }
}
