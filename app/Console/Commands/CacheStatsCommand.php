<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheManager;

class CacheStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display cache statistics';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('📊 Cache Statistics');
        $this->line('');
        
        $stats = CacheManager::getStats();
        
        if (isset($stats['error'])) {
            $this->error('❌ Error getting cache stats: ' . $stats['error']);
            return 1;
        }
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Keys', $stats['total_keys']],
                ['Driver', strtoupper($stats['driver'])],
                ['Registry Enabled', $stats['registry_enabled'] ? 'YES' : 'NO'],
            ]
        );
        
        $this->line('');
        $this->info('📦 Keys by Prefix:');
        $this->table(
            ['Prefix', 'Key Count'],
            array_map(
                fn($prefix, $count) => [ucfirst($prefix), $count],
                array_keys($stats['keys_by_prefix']),
                array_values($stats['keys_by_prefix'])
            )
        );
        
        return 0;
    }
}
