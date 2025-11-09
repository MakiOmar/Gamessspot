<?php

namespace App\Http\Controllers;

use App\Models\StoresProfile;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Rawilk\Settings\Facades\Settings;

class SettingsController extends Controller
{
    /**
     * Display the settings management page.
     */
    public function index(): View
    {
        $settings = [
            'app' => [
                'name' => Settings::get('app.name', config('app.name')),
                'timezone' => Settings::get('app.timezone', config('app.timezone')),
                'locale' => Settings::get('app.locale', config('app.locale')),
            ],
            'business' => [
                'company_name' => Settings::get('business.company_name', config('app.company_name')),
                'phone' => Settings::get('business.phone'),
                'email' => Settings::get('business.email'),
                'address' => Settings::get('business.address'),
            ],
            'orders' => [
                'auto_approve' => Settings::get('orders.auto_approve', false),
                'notification_email' => Settings::get('orders.notification_email'),
                'max_order_amount' => Settings::get('orders.max_order_amount', 10000),
            ],
            'notifications' => [
                'email_enabled' => Settings::get('notifications.email_enabled', true),
                'sms_enabled' => Settings::get('notifications.sms_enabled', false),
                'order_notifications' => Settings::get('notifications.order_notifications', true),
            ],
            'pos' => [
                'offline_sku' => Settings::get('pos.offline_sku', '0140'),
                'secondary_sku' => Settings::get('pos.secondary_sku', '0141'),
                'primary_sku' => Settings::get('pos.primary_sku', '0139'),
                'card_sku' => Settings::get('pos.card_sku', '0142'),
                'offline_id' => Settings::get('pos.offline_id', '140'),
                'secondary_id' => Settings::get('pos.secondary_id', '141'),
                'primary_id' => Settings::get('pos.primary_id', '139'),
                'card_id' => Settings::get('pos.card_id', '142'),
                'username' => Settings::get('pos.username', 'admin'),
                'password' => Settings::get('pos.password', 'pos@123'),
                'base_url' => Settings::get('pos.base_url', 'https://pos.gamesspoteg.com'),
            ],
        ];

        $storeProfiles = StoresProfile::orderBy('id')->get(['id', 'name']);
        $defaultLocationMap = SettingsService::getDefaultPosLocationMap();
        $configuredLocationMap = Settings::get('pos.location_map', []);
        if (! is_array($configuredLocationMap)) {
            $configuredLocationMap = [];
        }

        $posLocationProfiles = $storeProfiles->map(function ($profile) use ($defaultLocationMap, $configuredLocationMap) {
            $key = 'profile_' . $profile->id;
            $defaultValue = $defaultLocationMap[$key] ?? null;
            $currentValue = $configuredLocationMap[$key] ?? $defaultValue;

            return [
                'id' => $profile->id,
                'name' => $profile->name,
                'key' => $key,
                'default' => $defaultValue,
                'value' => $currentValue,
            ];
        })->values()->toArray();

        if (empty($posLocationProfiles)) {
            $posLocationProfiles = collect($defaultLocationMap)->map(function ($value, $key) {
                return [
                    'id' => (int) str_replace('profile_', '', $key),
                    'name' => 'Store Profile ' . str_replace('profile_', '#', $key),
                    'key' => $key,
                    'default' => $value,
                    'value' => $value,
                ];
            })->values()->toArray();
        }

        return view('manager.settings', compact('settings', 'posLocationProfiles'));
    }

    /**
     * Update application settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'app.name' => 'required|string|max:255',
            'app.timezone' => 'required|string',
            'app.locale' => 'required|string',
            'business.company_name' => 'required|string|max:255',
            'business.phone' => 'nullable|string|max:20',
            'business.email' => 'nullable|email|max:255',
            'business.address' => 'nullable|string|max:500',
            'orders.auto_approve' => 'boolean',
            'orders.notification_email' => 'nullable|email|max:255',
            'orders.max_order_amount' => 'nullable|numeric|min:0',
            'notifications.email_enabled' => 'boolean',
            'notifications.sms_enabled' => 'boolean',
            'notifications.order_notifications' => 'boolean',
            'pos.offline_sku' => 'required|string|max:10',
            'pos.secondary_sku' => 'required|string|max:10',
            'pos.primary_sku' => 'required|string|max:10',
            'pos.card_sku' => 'required|string|max:10',
            'pos.offline_id' => 'required|string|max:10',
            'pos.secondary_id' => 'required|string|max:10',
            'pos.primary_id' => 'required|string|max:10',
            'pos.card_id' => 'required|string|max:10',
            'pos.username' => 'required|string|max:50',
            'pos.password' => 'required|string|max:100',
            'pos.base_url' => 'required|url|max:255',
            'pos_location' => 'array',
            'pos_location.*' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update app settings
        Settings::set('app.name', $request->input('app.name'));
        Settings::set('app.timezone', $request->input('app.timezone'));
        Settings::set('app.locale', $request->input('app.locale'));

        // Update business settings
        Settings::set('business.company_name', $request->input('business.company_name'));
        Settings::set('business.phone', $request->input('business.phone'));
        Settings::set('business.email', $request->input('business.email'));
        Settings::set('business.address', $request->input('business.address'));

        // Update order settings
        Settings::set('orders.auto_approve', $request->has('orders.auto_approve'));
        Settings::set('orders.notification_email', $request->input('orders.notification_email'));
        Settings::set('orders.max_order_amount', $request->input('orders.max_order_amount'));

        // Update notification settings
        Settings::set('notifications.email_enabled', $request->has('notifications.email_enabled'));
        Settings::set('notifications.sms_enabled', $request->has('notifications.sms_enabled'));
        Settings::set('notifications.order_notifications', $request->has('notifications.order_notifications'));

        // Update POS settings
        Settings::set('pos.offline_sku', $request->input('pos.offline_sku'));
        Settings::set('pos.secondary_sku', $request->input('pos.secondary_sku'));
        Settings::set('pos.primary_sku', $request->input('pos.primary_sku'));
        Settings::set('pos.card_sku', $request->input('pos.card_sku'));
        Settings::set('pos.offline_id', $request->input('pos.offline_id'));
        Settings::set('pos.secondary_id', $request->input('pos.secondary_id'));
        Settings::set('pos.primary_id', $request->input('pos.primary_id'));
        Settings::set('pos.card_id', $request->input('pos.card_id'));
        Settings::set('pos.username', $request->input('pos.username'));
        Settings::set('pos.password', $request->input('pos.password'));
        Settings::set('pos.base_url', $request->input('pos.base_url'));

        $posLocationInput = $request->input('pos_location', []);
        $posLocationMap = [];
        foreach (StoresProfile::pluck('id') as $storeProfileId) {
            $key = 'profile_' . $storeProfileId;
            if (! array_key_exists($key, $posLocationInput)) {
                continue;
            }

            $value = $posLocationInput[$key];
            if ($value === null || $value === '') {
                continue;
            }

            $posLocationMap[$key] = (int) $value;
        }

        Settings::set('pos.location_map', $posLocationMap);

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully!');
    }

    /**
     * Reset settings to default values.
     */
    public function reset(): RedirectResponse
    {
        // Reset to default values
        Settings::forget('app.name');
        Settings::forget('app.timezone');
        Settings::forget('app.locale');
        Settings::forget('business.company_name');
        Settings::forget('business.phone');
        Settings::forget('business.email');
        Settings::forget('business.address');
        Settings::forget('orders.auto_approve');
        Settings::forget('orders.notification_email');
        Settings::forget('orders.max_order_amount');
        Settings::forget('notifications.email_enabled');
        Settings::forget('notifications.sms_enabled');
        Settings::forget('notifications.order_notifications');
        Settings::forget('pos.offline_sku');
        Settings::forget('pos.secondary_sku');
        Settings::forget('pos.primary_sku');
        Settings::forget('pos.card_sku');
        Settings::forget('pos.offline_id');
        Settings::forget('pos.secondary_id');
        Settings::forget('pos.primary_id');
        Settings::forget('pos.card_id');
        Settings::forget('pos.username');
        Settings::forget('pos.password');
        Settings::forget('pos.base_url');
        Settings::forget('pos.location_map');

        return redirect()->route('settings.index')
            ->with('success', 'Settings reset to default values!');
    }

    /**
     * Get a specific setting value.
     */
    public function get(string $key)
    {
        return Settings::get($key);
    }

    /**
     * Set a specific setting value.
     */
    public function set(Request $request, string $key)
    {
        $value = $request->input('value');
        Settings::set($key, $value);

        return response()->json(['success' => true, 'value' => $value]);
    }
}
