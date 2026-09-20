<?php

namespace App\Support;

use App\Models\DeviceRepair;
use App\Models\Role;
use App\Models\User;

class PhoneUserResolver
{
    /**
     * Find a user by phone using country-code-tolerant variants.
     */
    public static function findByPhone(string $rawPhone): ?User
    {
        $variants = DeviceRepair::phoneSearchVariants($rawPhone);
        if (empty($variants)) {
            return null;
        }

        return User::query()->whereIn('phone', $variants)->first();
    }

    /**
     * Find or create a customer user for the given phone.
     */
    public static function findOrCreateCustomer(string $rawPhone, ?string $name = null): User
    {
        $existing = self::findByPhone($rawPhone);
        if ($existing) {
            return $existing;
        }

        [$countryCode, $national] = DeviceRepair::parsePhoneSearch($rawPhone);
        $phone = $countryCode . $national;
        if ($phone === '+' || $national === '') {
            $phone = preg_replace('/\s+/', '', trim($rawPhone)) ?: $rawPhone;
        }

        $user = User::create([
            'name' => $name ?: 'Customer',
            'email' => 'customer_' . md5($phone . microtime(true)) . '@reviews.local',
            'phone' => $phone,
            'password' => bcrypt('temp_password_' . uniqid()),
            'is_active' => true,
        ]);

        $customerRole = Role::where('name', 'customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole);
        }

        return $user;
    }
}
