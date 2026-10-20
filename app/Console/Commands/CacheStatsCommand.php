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
                ['Driver', strtoupper($stats['driver'])],
            ]
        );
        
        $this->line('');
        $this->info('📦 Invalidations by group:');
        $this->table(
            ['Group', 'Invalidations'],
            array_map(
                fn($group, $count) => [ucfirst($group), $count],
                array_keys($stats['invalidations_by_group']),
                array_values($stats['invalidations_by_group'])
            )
        );
        
        return 0;
    }
}
