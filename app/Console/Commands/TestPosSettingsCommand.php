<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SettingsService;

class TestPosSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:test-pos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test POS Settings functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing POS Settings...');

        // Test POS SKUs
        $this->info('1. Testing POS SKUs:');
        $posSkus = SettingsService::getPosSkus();
        foreach ($posSkus as $type => $sku) {
            $this->line("   ✓ {$type} SKU: {$sku}");
        }

        // Test POS IDs
        $this->info('2. Testing POS IDs:');
        $posIds = SettingsService::getPosIds();
        foreach ($posIds as $type => $id) {
            $this->line("   ✓ {$type} ID: {$id}");
        }

        // Test individual SKU retrieval
        $this->info('3. Testing individual SKU retrieval:');
        $this->line("   ✓ Offline SKU: " . SettingsService::getPosSku('offline'));
        $this->line("   ✓ Secondary SKU: " . SettingsService::getPosSku('secondary'));
        $this->line("   ✓ Primary SKU: " . SettingsService::getPosSku('primary'));
        $this->line("   ✓ Card SKU: " . SettingsService::getPosSku('card'));

        // Test individual ID retrieval
        $this->info('4. Testing individual ID retrieval:');
        $this->line("   ✓ Offline ID: " . SettingsService::getPosId('offline'));
        $this->line("   ✓ Secondary ID: " . SettingsService::getPosId('secondary'));
        $this->line("   ✓ Primary ID: " . SettingsService::getPosId('primary'));
        $this->line("   ✓ Card ID: " . SettingsService::getPosId('card'));

        $this->info('POS Settings test completed successfully!');
    }
}
