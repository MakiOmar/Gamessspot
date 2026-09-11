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
     * ITU calling codes, longest first so +966 is not parsed as +96.
     */
    public static function callingCodesLongestFirst(): array
    {
        static $sorted = null;

        if ($sorted !== null) {
            return $sorted;
        }

        $sorted = explode(',', '358,359,370,371,372,373,374,375,376,377,378,380,381,382,383,385,386,387,389,420,421,423,500,501,502,503,504,505,506,507,508,509,590,591,592,593,594,595,596,597,598,599,670,672,673,674,675,676,677,678,679,680,681,682,683,685,686,687,688,689,690,691,692,850,852,853,855,856,880,886,960,961,962,963,964,965,966,967,968,970,971,972,973,974,975,976,977,992,993,994,995,996,998,211,212,213,216,218,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245,246,248,249,250,251,252,253,254,255,256,257,258,260,261,262,263,264,265,266,267,268,269,290,291,297,298,299,350,351,352,353,354,355,356,357,20,27,30,31,32,33,34,36,39,40,41,43,44,45,46,47,48,49,51,52,53,54,55,56,57,58,60,61,62,63,64,65,66,81,82,84,86,90,91,92,93,94,95,98,7,1');
        usort($sorted, fn ($a, $b) => strlen($b) <=> strlen($a));

        return $sorted;
    }

    /**
     * Match a calling code at the start of a digit string.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function matchCallingCode(string $digits, bool $allowNanp = false): ?array
    {
        foreach (self::callingCodesLongestFirst() as $code) {
            if ($code === '1' && !$allowNanp) {
                continue;
            }

            if (!str_starts_with($digits, $code)) {
                continue;
            }

            $national = substr($digits, strlen($code));
            if (strlen($national) >= 8) {
                return ['+' . $code, $national];
            }
        }

        return null;
    }

    /**
     * Split a submitted phone into country code and national number.
     * Works with any calling code, with or without + / 00 / a leading 0.
     *
     * @return array{0: string, 1: string}
     */
    public static function parsePhoneSearch(string $phoneNumber): array
    {
        $normalized = preg_replace('/[\s\-()]/', '', trim($phoneNumber)) ?? '';
        $digits = preg_replace('/\D+/', '', $normalized) ?? '';
        $hadInternationalPrefix = str_starts_with($normalized, '+') || str_starts_with($digits, '00');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $allowNanp = $hadInternationalPrefix || (strlen($digits) === 11 && str_starts_with($digits, '1'));
        $matched = self::matchCallingCode($digits, $allowNanp);
        [$countryCode, $national] = $matched ?? ['+20', $digits];

        if (str_starts_with($national, '0')) {
            $national = substr($national, 1);
        }

        return [$countryCode, $national];
    }

    /**
     * Stored-phone variants so search works with or without the country code.
     */
    public static function phoneSearchVariants(string $rawPhone): array
    {
        $trimmed = trim($rawPhone);
        $stripped = preg_replace('/[\s\-()]/', '', $trimmed) ?? '';
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        [$countryCode, $national] = self::parsePhoneSearch($trimmed);
        $countryDigits = ltrim($countryCode, '+');

        return array_values(array_unique(array_filter([
            $trimmed,
            $stripped,
            $digits,
            $national,
            $national !== '' ? '0' . $national : null,
            $countryDigits . $national,
            $countryCode . $national,
            '00' . $countryDigits . $national,
            $digits !== '' ? '+' . $digits : null,
        ], fn ($value) => $value !== null && $value !== '')));
    }

    /**
     * Find repairs for a phone across all statuses, newest first.
     */
    public static function findByPhone(string $rawPhone)
    {
        $variants = self::phoneSearchVariants($rawPhone);
        [, $national] = self::parsePhoneSearch($rawPhone);
        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';

        return self::with(['user', 'deviceModel', 'storeProfile'])
            ->whereHas('user', function ($query) use ($variants, $national, $digits) {
                $query->where(function ($phoneQuery) use ($variants, $national, $digits) {
                    $phoneQuery->whereIn('phone', $variants);

                    foreach (array_unique([$national, $digits]) as $suffix) {
                        if (strlen($suffix) >= 8) {
                            $phoneQuery->orWhere('phone', 'like', '%' . $suffix);
                        }
                    }
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Public tracking payload for API clients.
     */
    public function toTrackingArray(): array
    {
        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'status' => $this->status,
            'status_display' => $this->status_display,
            'device_serial_number' => $this->device_serial_number,
            'notes' => $this->notes,
            'submitted_at' => optional($this->submitted_at)->toIso8601String(),
            'status_updated_at' => optional($this->status_updated_at)->toIso8601String(),
            'client_name' => $this->client_name,
            'phone' => $this->full_phone_number,
            'device_model' => $this->deviceModel ? [
                'id' => $this->deviceModel->id,
                'name' => $this->deviceModel->name,
                'brand' => $this->deviceModel->brand,
                'full_name' => $this->deviceModel->full_name,
            ] : null,
            'store_profile' => $this->storeProfile ? [
                'id' => $this->storeProfile->id,
                'name' => $this->storeProfile->name,
            ] : null,
        ];
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
