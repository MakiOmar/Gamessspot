<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncAccountSecondaryStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:sync-secondary-stock {--limit=100 : Number of accounts to process per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync secondary stock: if ps4_secondary_stock is 0, set ps5_secondary_stock to 0, and vice versa (excludes PS5 Only accounts)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $cacheKey = 'sync_secondary_stock_last_id';
        
        $this->info("Starting secondary stock synchronization (limit: {$limit}, excluding PS5 Only accounts)...");

        // Get the last processed account ID from cache (start from 0 if not set)
        $lastProcessedId = Cache::get($cacheKey, 0);

        // Find accounts where either ps4_secondary_stock or ps5_secondary_stock is 0
        // Exclude "PS5 Only" accounts (where all PS4 stocks are 0)
        // Process in batches starting from the last processed ID
        $accounts = Account::where(function ($query) {
            $query->where('ps4_secondary_stock', 0)
                  ->orWhere('ps5_secondary_stock', 0);
        })
        ->where(function ($query) {
            // Exclude PS5 Only accounts (accounts where ALL PS4 stocks are 0)
            $query->where('ps4_primary_stock', '!=', 0)
                  ->orWhere('ps4_secondary_stock', '!=', 0)
                  ->orWhere('ps4_offline_stock', '!=', 0);
        })
        ->where('id', '>', $lastProcessedId)
        ->orderBy('id', 'asc')
        ->limit($limit)
        ->get();

        if ($accounts->isEmpty()) {
            $this->info('No accounts found to process. Resetting offset for next cycle.');
            // Reset to start from beginning for next cycle
            Cache::forget($cacheKey);
            return Command::SUCCESS;
        }

        $updatedCount = 0;
        $lastId = $lastProcessedId;

        foreach ($accounts as $account) {
            // Store before values for logging
            $before = [
                'ps4_secondary_stock' => $account->ps4_secondary_stock,
                'ps5_secondary_stock' => $account->ps5_secondary_stock,
            ];

            $needsUpdate = false;
            $updateData = [];

            // If ps4_secondary_stock is 0, set ps5_secondary_stock to 0
            if ($account->ps4_secondary_stock == 0 && $account->ps5_secondary_stock != 0) {
                $updateData['ps5_secondary_stock'] = 0;
                $needsUpdate = true;
            }

            // If ps5_secondary_stock is 0, set ps4_secondary_stock to 0
            if ($account->ps5_secondary_stock == 0 && $account->ps4_secondary_stock != 0) {
                $updateData['ps4_secondary_stock'] = 0;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                // Log before update
                Log::info('Account secondary stock sync - BEFORE', [
                    'account_id' => $account->id,
                    'mail' => $account->mail,
                    'game_id' => $account->game_id,
                    'ps4_secondary_stock' => $before['ps4_secondary_stock'],
                    'ps5_secondary_stock' => $before['ps5_secondary_stock'],
                ]);

                // Update the account
                $account->update($updateData);
                
                // Refresh to get updated values
                $account->refresh();
                
                // Log after update
                Log::info('Account secondary stock sync - AFTER', [
                    'account_id' => $account->id,
                    'mail' => $account->mail,
                    'game_id' => $account->game_id,
                    'ps4_secondary_stock' => $account->ps4_secondary_stock,
                    'ps5_secondary_stock' => $account->ps5_secondary_stock,
                    'changes' => $updateData,
                ]);

                $updatedCount++;
            }

            // Track the last processed ID
            $lastId = $account->id;
        }

        // Store the last processed ID for next run
        Cache::put($cacheKey, $lastId, now()->addDays(7)); // Cache for 7 days

        $this->info("Secondary stock synchronization completed. Updated {$updatedCount} account(s). Last processed ID: {$lastId}");

        return Command::SUCCESS;
    }
}

