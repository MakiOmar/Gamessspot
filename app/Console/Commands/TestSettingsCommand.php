<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Rawilk\Settings\Facades\Settings;
use App\Services\SettingsService;

class TestSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Laravel Settings functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Laravel Settings...');

        // Test basic settings functionality
        $this->info('1. Testing basic settings operations:');
        
        // Set a test setting
        Settings::set('test.value', 'Hello from Laravel Settings!');
        $this->line('   ✓ Set test.value: ' . Settings::get('test.value'));
        
        // Test with default value
        $defaultValue = Settings::get('test.nonexistent', 'Default Value');
        $this->line('   ✓ Default value: ' . $defaultValue);

        // Test SettingsService
        $this->info('2. Testing SettingsService:');
        $this->line('   ✓ Company Name: ' . SettingsService::getCompanyName());
        $this->line('   ✓ App Name: ' . SettingsService::getAppName());
        $this->line('   ✓ Timezone: ' . SettingsService::getTimezone());
        $this->line('   ✓ Locale: ' . SettingsService::getLocale());
        $this->line('   ✓ Max Order Amount: ' . SettingsService::getMaxOrderAmount());
        $this->line('   ✓ Auto Approve: ' . (SettingsService::isAutoApproveEnabled() ? 'Yes' : 'No'));
        $this->line('   ✓ Email Notifications: ' . (SettingsService::isEmailNotificationEnabled() ? 'Yes' : 'No'));

        // Test business settings
        $this->info('3. Testing business settings:');
        $businessSettings = SettingsService::getBusinessSettings();
        foreach ($businessSettings as $key => $value) {
            $this->line("   ✓ {$key}: " . ($value ?? 'Not set'));
        }

        // Test order validation
        $this->info('4. Testing order validation:');
        $testAmount = 5000;
        $isValid = SettingsService::validateOrderAmount($testAmount);
        $this->line("   ✓ Order amount {$testAmount}: " . ($isValid ? 'Valid' : 'Invalid'));

        $testAmount = 15000;
        $isValid = SettingsService::validateOrderAmount($testAmount);
        $this->line("   ✓ Order amount {$testAmount}: " . ($isValid ? 'Valid' : 'Invalid'));

        // Clean up test setting
        Settings::forget('test.value');
        $this->info('5. Cleaned up test settings.');

        $this->info('Laravel Settings test completed successfully!');
    }
}
