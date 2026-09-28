<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trader extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = array(self::STATUS_ACTIVE, self::STATUS_INACTIVE);

    protected $fillable = array(
        'name',
        'phone',
        'whatsapp',
        'notes',
        'status',
        'opening_balance',
        'opening_balance_date',
        'created_by',
        'updated_by',
    );

    protected $casts = array(
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
    );

    public function purchaseOrders()
    {
        return $this->hasMany(TraderPurchaseOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(TraderPayment::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
