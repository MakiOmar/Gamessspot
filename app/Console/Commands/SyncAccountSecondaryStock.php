<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAccountSecondaryStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:sync-secondary-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync secondary stock: if ps4_secondary_stock is 0, set ps5_secondary_stock to 0, and vice versa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting secondary stock synchronization...');

        // Find accounts where either ps4_secondary_stock or ps5_secondary_stock is 0
        $accounts = Account::where(function ($query) {
            $query->where('ps4_secondary_stock', 0)
                  ->orWhere('ps5_secondary_stock', 0);
        })->get();

        $updatedCount = 0;

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
        }

        $this->info("Secondary stock synchronization completed. Updated {$updatedCount} account(s).");

        return Command::SUCCESS;
    }
}

