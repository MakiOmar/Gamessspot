<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'stars',
        'comment',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'stars' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Public-facing review payload (no phone).
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'stars' => $this->stars,
            'comment' => $this->comment,
            'reviewer_name' => $this->user?->name ?: 'Customer',
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }

    /**
     * Aggregate approved reviews for a product.
     */
    public static function summaryFor($reviewable, int $limit = 20): array
    {
        $items = static::query()
            ->with('user:id,name')
            ->where('reviewable_type', $reviewable->getMorphClass())
            ->where('reviewable_id', $reviewable->getKey())
            ->approved()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $count = static::query()
            ->where('reviewable_type', $reviewable->getMorphClass())
            ->where('reviewable_id', $reviewable->getKey())
            ->approved()
            ->count();

        $average = $count > 0
            ? round((float) static::query()
                ->where('reviewable_type', $reviewable->getMorphClass())
                ->where('reviewable_id', $reviewable->getKey())
                ->approved()
                ->avg('stars'), 1)
            : 0;

        return [
            'average' => $average,
            'count' => $count,
            'items' => $items->map->toPublicArray()->values(),
        ];
    }
}
