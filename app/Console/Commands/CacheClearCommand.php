<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheManager;

class CacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-app {--all : Clear all caches} {--dashboard : Clear dashboard caches} {--orders : Clear order caches} {--users : Clear user caches} {--accounts : Clear account caches} {--cards : Clear card caches} {--games : Clear game caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application caches using CacheManager';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Clearing application caches...');
        
        if ($this->option('all')) {
            CacheManager::clearAll();
            $this->info('✅ All caches cleared successfully!');
            return 0;
        }
        
        $cleared = [];
        
        if ($this->option('dashboard')) {
            CacheManager::invalidateDashboard();
            $cleared[] = 'Dashboard';
        }
        
        if ($this->option('orders')) {
            CacheManager::invalidateOrders();
            $cleared[] = 'Orders';
        }
        
        if ($this->option('users')) {
            CacheManager::invalidateUsers();
            $cleared[] = 'Users';
        }
        
        if ($this->option('accounts')) {
            CacheManager::invalidateAccounts();
            $cleared[] = 'Accounts';
        }
        
        if ($this->option('cards')) {
            CacheManager::invalidateCards();
            $cleared[] = 'Cards';
        }
        
        if ($this->option('games')) {
            CacheManager::invalidateGames();
            $cleared[] = 'Games';
        }
        
        if (empty($cleared)) {
            $this->warn('❌ No cache type specified. Use --all or specify a cache type.');
            $this->line('');
            $this->line('Available options:');
            $this->line('  --all          Clear all application caches');
            $this->line('  --dashboard    Clear dashboard caches');
            $this->line('  --orders       Clear order caches');
            $this->line('  --users        Clear user caches');
            $this->line('  --accounts     Clear account caches');
            $this->line('  --cards        Clear card caches');
            $this->line('  --games        Clear game caches');
            return 1;
        }
        
        $this->info('✅ Cleared: ' . implode(', ', $cleared));
        return 0;
    }
}
