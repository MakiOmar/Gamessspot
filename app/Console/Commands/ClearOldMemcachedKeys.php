<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearOldMemcachedKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memcached:clear-sessions {--force : Force flush all Memcached data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear PHP sessions and old data from Memcached';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('force')) {
            if (!$this->confirm('⚠️  This will flush ALL data from Memcached (including sessions). Continue?')) {
                $this->info('Cancelled.');
                return 0;
            }
            
            try {
                $memcached = new \Memcached();
                $memcached->addServer(
                    config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
                    config('cache.stores.memcached.servers.0.port', 11211)
                );
                
                // Get stats before flush
                $statsBefore = $memcached->getStats();
                $serverKey = config('cache.stores.memcached.servers.0.host') . ':' . config('cache.stores.memcached.servers.0.port');
                
                if (isset($statsBefore[$serverKey])) {
                    $beforeItems = $statsBefore[$serverKey]['curr_items'];
                    $beforeBytes = $statsBefore[$serverKey]['bytes'];
                    
                    $this->info("Before flush:");
                    $this->line("  Items: " . number_format($beforeItems));
                    $this->line("  Memory: " . $this->formatBytes($beforeBytes));
                }
                
                // Flush all data
                $memcached->flush();
                
                $this->info('');
                $this->info('✅ Memcached flushed successfully!');
                $this->info('');
                
                // Wait a moment for stats to update
                sleep(1);
                
                // Get stats after flush
                $statsAfter = $memcached->getStats();
                if (isset($statsAfter[$serverKey])) {
                    $afterItems = $statsAfter[$serverKey]['curr_items'];
                    $afterBytes = $statsAfter[$serverKey]['bytes'];
                    
                    $this->info("After flush:");
                    $this->line("  Items: " . number_format($afterItems));
                    $this->line("  Memory: " . $this->formatBytes($afterBytes));
                    
                    if (isset($beforeItems)) {
                        $freed = $beforeItems - $afterItems;
                        $this->info("  Freed: " . number_format($freed) . " keys");
                    }
                }
                
                $this->line('');
                $this->warn('⚠️  Note: This cleared ALL data including sessions.');
                $this->warn('   Users will need to log in again.');
                
                return 0;
                
            } catch (\Exception $e) {
                $this->error('❌ Failed to flush Memcached: ' . $e->getMessage());
                return 1;
            }
        }
        
        $this->warn('⚠️  Session clearing from Memcached requires --force flag');
        $this->line('');
        $this->line('💡 Recommended Actions:');
        $this->line('');
        $this->line('1. Change SESSION_DRIVER to avoid using Memcached:');
        $this->line('   SESSION_DRIVER=redis    (recommended)');
        $this->line('   SESSION_DRIVER=database');
        $this->line('   SESSION_DRIVER=file');
        $this->line('');
        $this->line('2. If you must use Memcached for sessions:');
        $this->line('   - Increase Memcached memory allocation');
        $this->line('   - Reduce SESSION_LIFETIME in .env');
        $this->line('   - Use different Memcached instances for cache vs sessions');
        $this->line('');
        $this->line('3. To flush Memcached immediately:');
        $this->line('   php artisan memcached:clear-sessions --force');
        
        return 0;
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

