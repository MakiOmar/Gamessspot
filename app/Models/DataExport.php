<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A background spreadsheet export requested by a manager; the file lives on the private local disk.
 */
class DataExport extends Model
{
    public const TYPE_ORDERS = 'orders';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /** Files and rows older than this are pruned by `exports:prune`. */
    public const RETENTION_HOURS = 24;

    /** An unfinished export older than this is treated as abandoned (worker died) and no longer blocks new requests. */
    public const STALE_AFTER_MINUTES = 30;

    public const DISK = 'local';

    protected $fillable = array(
        'user_id',
        'type',
        'filters',
        'status',
        'file_path',
        'file_name',
        'row_count',
        'error',
        'completed_at',
    );

    protected $casts = array(
        'filters'      => 'array',
        'row_count'    => 'integer',
        'completed_at' => 'datetime',
    );

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->file_path !== null;
    }

    /**
     * Queued or processing exports that are recent enough to still be running.
     */
    public function scopeInFlight(Builder $query): Builder
    {
        return $query->whereIn('status', array(self::STATUS_QUEUED, self::STATUS_PROCESSING))
            ->where('created_at', '>=', now()->subMinutes(self::STALE_AFTER_MINUTES));
    }
}
