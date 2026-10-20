<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One Next Load CreateOrder call. The row (and its reference) exists before the HTTP call so a
 * timeout is retried with the same reference and a sale can never be bought twice.
 */
class NextLoadOrder extends Model
{
    public const STATUS_PENDING_SEND = 'pending_send';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_FAILED = 'failed';

    public const PURPOSE_SALE = 'sale';

    public const PURPOSE_RESTOCK = 'restock';

    /** Polling gives up (and flags the order for manual review) after this many FetchOrder attempts. */
    public const MAX_POLL_ATTEMPTS = 60;

    protected $fillable = [
        'reference', 'next_load_order_id', 'card_category_id', 'product_id', 'product_name',
        'quantity', 'delivered_quantity', 'status', 'purpose', 'card_price', 'order_amount',
        'storefront_order_id', 'storefront_line_key', 'requested_by', 'attempts', 'last_error',
        'next_poll_at', 'completed_at', 'consumed_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'delivered_quantity' => 'integer',
        'attempts' => 'integer',
        'next_poll_at' => 'datetime',
        'completed_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CardCategory::class, 'card_category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(NextLoadOrderItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETE;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_SEND, self::STATUS_IN_PROGRESS], true);
    }

    /** Items not yet turned into a Card row. */
    public function unconvertedItems(): HasMany
    {
        return $this->items()->whereDoesntHave('card');
    }
}
