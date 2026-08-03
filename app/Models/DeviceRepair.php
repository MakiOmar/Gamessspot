<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\StoresProfile;

class DeviceRepair extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_model_id',
        'device_model',
        'device_serial_number',
        'notes',
        'status',
        'user_id',
        'submitted_by_user_id',
        'store_profile_id',
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
     * Get the device model for this repair.
     */
    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    /**
     * Get the user (staff) who submitted this repair.
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /**
     * Get the store profile for this repair.
     */
    public function storeProfile(): BelongsTo
    {
        return $this->belongsTo(StoresProfile::class);
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
     * Normalize a device serial number for consistent comparison.
     */
    public static function normalizeSerial(string $serialNumber): string
    {
        return strtoupper(trim($serialNumber));
    }

    /**
     * Find a recently created duplicate repair (double-submit protection).
     */
    public static function findRecentDuplicate(
        int $userId,
        int $deviceModelId,
        string $serialNumber,
        ?int $storeProfileId = null,
        int $withinSeconds = 120
    ): ?self {
        $query = self::query()
            ->where('user_id', $userId)
            ->where('device_model_id', $deviceModelId)
            ->where('device_serial_number', self::normalizeSerial($serialNumber))
            ->where('created_at', '>=', now()->subSeconds($withinSeconds));

        if ($storeProfileId !== null) {
            $query->where('store_profile_id', $storeProfileId);
        }

        return $query->latest()->first();
    }

    /**
     * Find an active duplicate repair for the same device.
     */
    public static function findActiveDuplicate(
        int $userId,
        int $deviceModelId,
        string $serialNumber,
        ?int $storeProfileId = null
    ): ?self {
        $query = self::query()
            ->where('user_id', $userId)
            ->where('device_model_id', $deviceModelId)
            ->where('device_serial_number', self::normalizeSerial($serialNumber))
            ->active();

        if ($storeProfileId !== null) {
            $query->where('store_profile_id', $storeProfileId);
        }

        return $query->latest()->first();
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
