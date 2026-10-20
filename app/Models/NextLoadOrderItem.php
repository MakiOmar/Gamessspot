<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One delivered supplier code. Serial and pin are encrypted; the pin only leaves via a sale or a logged reveal.
 */
class NextLoadOrderItem extends Model
{
    protected $fillable = ['next_load_order_id', 'serial', 'pin', 'pin_hash', 'price'];

    protected $hidden = ['serial', 'pin', 'pin_hash'];

    protected $casts = [
        'serial' => 'encrypted',
        'pin' => 'encrypted',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(NextLoadOrder::class, 'next_load_order_id');
    }

    public function card(): HasOne
    {
        return $this->hasOne(Card::class, 'next_load_order_item_id');
    }

    /** Serial with all but the last 4 characters masked, for admin lists. */
    public function maskedSerial(): string
    {
        $serial = (string) $this->serial;

        return strlen($serial) <= 4 ? $serial : str_repeat('•', 6).substr($serial, -4);
    }
}
