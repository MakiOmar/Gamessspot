<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Rawilk\Settings\Facades\Settings;
use Illuminate\Support\Facades\Validator;

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
        ];

        return view('manager.settings', compact('settings'));
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
        Settings::set('orders.auto_approve', $request->boolean('orders.auto_approve'));
        Settings::set('orders.notification_email', $request->input('orders.notification_email'));
        Settings::set('orders.max_order_amount', $request->input('orders.max_order_amount'));

        // Update notification settings
        Settings::set('notifications.email_enabled', $request->boolean('notifications.email_enabled'));
        Settings::set('notifications.sms_enabled', $request->boolean('notifications.sms_enabled'));
        Settings::set('notifications.order_notifications', $request->boolean('notifications.order_notifications'));

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
