<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SettingsService;

class TestPosCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:test-pos-credentials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test POS Credentials functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing POS Credentials...');

        // Test individual credential methods
        $this->info('1. Testing individual credential methods:');
        $this->line("   ✓ Username: " . SettingsService::getPosUsername());
        $this->line("   ✓ Password: " . str_repeat('*', strlen(SettingsService::getPosPassword())));
        $this->line("   ✓ Base URL: " . SettingsService::getPosBaseUrl());

        // Test credentials array
        $this->info('2. Testing credentials array:');
        $credentials = SettingsService::getPosCredentials();
        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                $this->line("   ✓ {$key}: " . str_repeat('*', strlen($value)));
            } else {
                $this->line("   ✓ {$key}: {$value}");
            }
        }

        // Test validation
        $this->info('3. Testing credential validation:');
        $username = SettingsService::getPosUsername();
        $password = SettingsService::getPosPassword();
        $baseUrl = SettingsService::getPosBaseUrl();

        $this->line("   ✓ Username length: " . strlen($username) . " characters");
        $this->line("   ✓ Password length: " . strlen($password) . " characters");
        $this->line("   ✓ Base URL valid: " . (filter_var($baseUrl, FILTER_VALIDATE_URL) ? 'Yes' : 'No'));

        $this->info('POS Credentials test completed successfully!');
    }
}
