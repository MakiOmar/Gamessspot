<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_NEXT_LOAD = 'next_load';

    protected $fillable = ['code', 'cost', 'card_category_id', 'status', 'source', 'next_load_order_item_id'];

    /** Codes are secrets: callers that must return one (sale responses) read `$card->code` explicitly. */
    protected $hidden = ['code', 'code_hash', 'cost'];

    protected $casts = [
        'code' => 'encrypted',
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Card $card) {
            if ($card->isDirty('code')) {
                $card->code_hash = self::hashCode((string) $card->code);
            }
        });
    }

    /**
     * Deterministic lookup key for an encrypted code (uniqueness + exact search).
     */
    public static function hashCode(string $code): string
    {
        return hash_hmac('sha256', trim($code), (string) config('app.key'));
    }

    public function isFromNextLoad(): bool
    {
        return $this->source === self::SOURCE_NEXT_LOAD;
    }

    public static function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return self::query()
            ->where('code_hash', self::hashCode($code))
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Get the category that the card belongs to.
     */
    public function category()
    {
        return $this->belongsTo(CardCategory::class, 'card_category_id');
    }

    public function nextLoadOrderItem()
    {
        return $this->belongsTo(NextLoadOrderItem::class, 'next_load_order_item_id');
    }
}
