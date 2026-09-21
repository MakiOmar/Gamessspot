<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Game;
use App\Services\CacheManager;
use Illuminate\Console\Command;

class EnableFullFeatureCommand extends Command
{
    protected $signature = 'accounts:enable-full-feature
                            {--dry-run : Show how many accounts would be updated without saving}
                            {--game= : Limit to a game ID or exact title}';

    protected $description = 'Enable the full-sell feature on pristine accounts (nothing sold yet)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $gameOpt = $this->option('game');

        $query = Account::query()->where('is_full', false);

        if ($gameOpt !== null && $gameOpt !== '') {
            if (ctype_digit((string) $gameOpt)) {
                $query->where('game_id', (int) $gameOpt);
            } else {
                $game = Game::where('title', $gameOpt)->first();
                if (!$game) {
                    $this->error("Game not found: {$gameOpt}");

                    return self::FAILURE;
                }
                $query->where('game_id', $game->id);
            }
        }

        // Dual pristine OR PS5-only pristine
        $query->where(function ($q) {
            $q->where(function ($dual) {
                $dual->where('ps4_primary_stock', 1)
                    ->where('ps4_secondary_stock', 1)
                    ->where('ps4_offline_stock', 2)
                    ->where('ps5_primary_stock', 1)
                    ->where('ps5_secondary_stock', 1)
                    ->where('ps5_offline_stock', 1);
            })->orWhere(function ($ps5Only) {
                $ps5Only->where('ps4_primary_stock', 0)
                    ->where('ps4_secondary_stock', 0)
                    ->where('ps4_offline_stock', 0)
                    ->where('ps5_primary_stock', 1)
                    ->where('ps5_secondary_stock', 1)
                    ->where('ps5_offline_stock', 2);
            });
        });

        $count = (clone $query)->count();
        $this->info(($dryRun ? '[dry-run] Would enable' : 'Enabling') . " full-sell feature on {$count} account(s).");

        if ($count === 0) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        $updated = $query->update(['is_full' => true]);

        CacheManager::invalidateAccounts();
        CacheManager::invalidateGames();

        $this->info("Updated {$updated} account(s).");

        return self::SUCCESS;
    }
}
