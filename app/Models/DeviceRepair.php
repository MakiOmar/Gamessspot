<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceRepair extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_model',
        'device_serial_number',
        'notes',
        'status',
        'user_id',
        'tracking_code',
        'submitted_at',
        'status_updated_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'status_updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the device repair.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client name from the user relationship.
     */
    public function getClientNameAttribute(): string
    {
        return $this->user ? $this->user->name : '';
    }

    /**
     * Get the full phone number from the user relationship.
     */
    public function getFullPhoneNumberAttribute(): string
    {
        return $this->user ? $this->user->phone : '';
    }

    /**
     * Generate a unique tracking code.
     */
    public static function generateTrackingCode(): string
    {
        do {
            $code = 'DR' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Update status and set status_updated_at timestamp.
     */
    public function updateStatus(string $status): bool
    {
        return $this->update([
            'status' => $status,
            'status_updated_at' => now()
        ]);
    }

    /**
     * Get status badge class for display.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'received' => 'bg-primary',
            'processing' => 'bg-warning',
            'ready' => 'bg-info',
            'delivered' => 'bg-success',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status display text.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'received' => 'Received',
            'processing' => 'Processing',
            'ready' => 'Ready for Pickup',
            'delivered' => 'Delivered',
            default => 'Unknown'
        };
    }

    /**
     * Scope for active repairs (not delivered).
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'delivered');
    }

}
