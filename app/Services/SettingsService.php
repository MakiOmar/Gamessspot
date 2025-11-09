<?php

namespace App\Services;

use App\Models\StoresProfile;
use Rawilk\Settings\Facades\Settings;

class SettingsService
{
    /**
     * Get application name from settings or fallback to config.
     */
    public static function getAppName(): string
    {
        return Settings::get('app.name', config('app.name'));
    }

    /**
     * Get company name from settings or fallback to config.
     */
    public static function getCompanyName(): string
    {
        return Settings::get('business.company_name', config('app.company_name', 'Games Spot'));
    }

    /**
     * Get business phone number.
     */
    public static function getBusinessPhone(): ?string
    {
        return Settings::get('business.phone');
    }

    /**
     * Get business email.
     */
    public static function getBusinessEmail(): ?string
    {
        return Settings::get('business.email');
    }

    /**
     * Get business address.
     */
    public static function getBusinessAddress(): ?string
    {
        return Settings::get('business.address');
    }

    /**
     * Check if orders should be auto-approved.
     */
    public static function isAutoApproveEnabled(): bool
    {
        return Settings::get('orders.auto_approve', false);
    }

    /**
     * Get order notification email.
     */
    public static function getOrderNotificationEmail(): ?string
    {
        return Settings::get('orders.notification_email');
    }

    /**
     * Get maximum order amount.
     */
    public static function getMaxOrderAmount(): float
    {
        return Settings::get('orders.max_order_amount', 10000);
    }

    /**
     * Check if email notifications are enabled.
     */
    public static function isEmailNotificationEnabled(): bool
    {
        return Settings::get('notifications.email_enabled', true);
    }

    /**
     * Check if SMS notifications are enabled.
     */
    public static function isSmsNotificationEnabled(): bool
    {
        return Settings::get('notifications.sms_enabled', false);
    }

    /**
     * Check if order notifications are enabled.
     */
    public static function isOrderNotificationEnabled(): bool
    {
        return Settings::get('notifications.order_notifications', true);
    }

    /**
     * Get application timezone.
     */
    public static function getTimezone(): string
    {
        return Settings::get('app.timezone', config('app.timezone', 'Africa/Cairo'));
    }

    /**
     * Get application locale.
     */
    public static function getLocale(): string
    {
        return Settings::get('app.locale', config('app.locale', 'en'));
    }

    /**
     * Get all business settings as an array.
     */
    public static function getBusinessSettings(): array
    {
        return [
            'company_name' => self::getCompanyName(),
            'phone' => self::getBusinessPhone(),
            'email' => self::getBusinessEmail(),
            'address' => self::getBusinessAddress(),
        ];
    }

    /**
     * Get all order settings as an array.
     */
    public static function getOrderSettings(): array
    {
        return [
            'auto_approve' => self::isAutoApproveEnabled(),
            'notification_email' => self::getOrderNotificationEmail(),
            'max_order_amount' => self::getMaxOrderAmount(),
        ];
    }

    /**
     * Get all notification settings as an array.
     */
    public static function getNotificationSettings(): array
    {
        return [
            'email_enabled' => self::isEmailNotificationEnabled(),
            'sms_enabled' => self::isSmsNotificationEnabled(),
            'order_notifications' => self::isOrderNotificationEnabled(),
        ];
    }

    /**
     * Validate order amount against maximum allowed.
     */
    public static function validateOrderAmount(float $amount): bool
    {
        $maxAmount = self::getMaxOrderAmount();
        return $amount <= $maxAmount;
    }

    /**
     * Get order validation error message.
     */
    public static function getOrderAmountErrorMessage(float $amount): string
    {
        $maxAmount = self::getMaxOrderAmount();
        return "Order amount ({$amount}) exceeds maximum allowed amount ({$maxAmount}).";
    }

    /**
     * Get POS SKU codes.
     */
    public static function getPosSkus(): array
    {
        return [
            'offline' => Settings::get('pos.offline_sku', '0140'),
            'secondary' => Settings::get('pos.secondary_sku', '0141'),
            'primary' => Settings::get('pos.primary_sku', '0139'),
            'card' => Settings::get('pos.card_sku', '0142'),
        ];
    }

    /**
     * Get POS IDs.
     */
    public static function getPosIds(): array
    {
        return [
            'offline' => Settings::get('pos.offline_id', '140'),
            'secondary' => Settings::get('pos.secondary_id', '141'),
            'primary' => Settings::get('pos.primary_id', '139'),
            'card' => Settings::get('pos.card_id', '142'),
        ];
    }

    /**
     * Get default POS location map keyed by store profile identifier.
     */
    public static function getDefaultPosLocationMap(): array
    {
        return [
            'profile_13' => 1, // New Cairo
            'profile_14' => 8, // Beverly Hills
            'profile_15' => 4, // Elserag mall
            'profile_16' => 5, // City stars
            'profile_17' => 6, // WooCommerce
            'profile_18' => 7, // El Shorouk city
        ];
    }

    /**
     * Retrieve POS location map with dynamic store profiles.
     *
     * @return array<string, int|null>
     */
    public static function getPosLocationMap(): array
    {
        $defaultMap = self::getDefaultPosLocationMap();
        $configuredMap = Settings::get('pos.location_map', []);
        if (!is_array($configuredMap)) {
            $configuredMap = [];
        }

        $merged = array_merge($defaultMap, $configuredMap);

        return StoresProfile::query()
            ->pluck('name', 'id')
            ->mapWithKeys(function ($name, $id) use ($merged, $defaultMap) {
                $key = 'profile_' . $id;
                $value = $merged[$key] ?? ($defaultMap[$key] ?? null);

                return [$key => $value !== null ? (int) $value : null];
            })
            ->toArray();
    }

    /**
     * Get POS location id for a specific store profile.
     */
    public static function getPosLocationForStoreProfile(?int $storeProfileId): ?int
    {
        if (!$storeProfileId) {
            return null;
        }

        $map = self::getPosLocationMap();
        $key = 'profile_' . $storeProfileId;

        if (array_key_exists($key, $map) && $map[$key] !== null) {
            return (int) $map[$key];
        }

        $defaultMap = self::getDefaultPosLocationMap();

        return $defaultMap[$key] ?? null;
    }

    /**
     * Get specific POS SKU.
     */
    public static function getPosSku(string $type): string
    {
        return Settings::get("pos.{$type}_sku", match($type) {
            'offline' => '0140',
            'secondary' => '0141',
            'primary' => '0139',
            'card' => '0142',
            default => ''
        });
    }

    /**
     * Get specific POS ID.
     */
    public static function getPosId(string $type): string
    {
        return Settings::get("pos.{$type}_id", match($type) {
            'offline' => '140',
            'secondary' => '141',
            'primary' => '139',
            'card' => '142',
            default => ''
        });
    }

    /**
     * Get POS username.
     */
    public static function getPosUsername(): string
    {
        return Settings::get('pos.username', 'admin');
    }

    /**
     * Get POS password.
     */
    public static function getPosPassword(): string
    {
        return Settings::get('pos.password', 'pos@123');
    }

    /**
     * Get POS base URL.
     */
    public static function getPosBaseUrl(): string
    {
        return Settings::get('pos.base_url', 'https://pos.gamesspoteg.com');
    }

    /**
     * Get all POS credentials.
     */
    public static function getPosCredentials(): array
    {
        return [
            'username' => self::getPosUsername(),
            'password' => self::getPosPassword(),
            'base_url' => self::getPosBaseUrl(),
        ];
    }
}
