<?php

namespace App\Console\Commands;

use App\Models\CardCategory;
use App\Models\Game;
use App\Services\PosCatalogSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Backfill / repair: push every game offer and card category to POS. Safe to re-run.
 */
class SyncPosCatalogCommand extends Command
{
    protected $signature = 'pos:sync-catalog
        {--games : Only sync games}
        {--cards : Only sync card categories}
        {--id=* : Limit to these game (or card category) ids}';

    protected $description = 'Create or update one POS product per game offer and per card category';

    public function handle(PosCatalogSync $sync): int
    {
        $sync->withRetries();
        $onlyGames = (bool) $this->option('games');
        $onlyCards = (bool) $this->option('cards');
        $ids = array_filter(array_map('intval', (array) $this->option('id')));
        $failed = 0;

        if (! $onlyCards) {
            $failed += $this->syncGames($sync, $ids);
        }

        if (! $onlyGames) {
            $failed += $this->syncCardCategories($sync, $ids);
        }

        if ($failed > 0) {
            $this->warn($failed.' item(s) failed; see the log. Re-run to retry.');

            return self::FAILURE;
        }

        $this->info('POS catalog is in sync.');

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $ids
     */
    private function syncGames(PosCatalogSync $sync, array $ids): int
    {
        $synced = 0;
        $skipped = 0;
        $failed = 0;

        Game::query()->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->chunkById(50, function ($games) use ($sync, &$synced, &$skipped, &$failed) {
                foreach ($games as $game) {
                    try {
                        $sync->pushGame($game) ? $synced++ : $skipped++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->reportFailure('game', (int) $game->id, $e);
                    }
                }
            });

        $this->line("Synced {$synced} game(s), {$skipped} with no offers to push, {$failed} failed.");

        return $failed;
    }

    /**
     * @param  list<int>  $ids
     */
    private function syncCardCategories(PosCatalogSync $sync, array $ids): int
    {
        $synced = 0;
        $failed = 0;

        CardCategory::query()->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->chunkById(PosCatalogSync::BATCH_MAX, function ($categories) use ($sync, &$synced, &$failed) {
                try {
                    $synced += $sync->pushCardCategories($categories);
                } catch (\Throwable $e) {
                    $failed += $categories->count();
                    $this->reportFailure('card categories', $categories->pluck('id')->implode(','), $e);
                }
            });

        $this->line("Synced {$synced} card category(s), {$failed} failed.");

        return $failed;
    }

    private function reportFailure(string $label, int|string $id, \Throwable $e): void
    {
        Log::warning('pos:sync-catalog failed', ['type' => $label, 'id' => $id, 'message' => $e->getMessage()]);
        $this->error(ucfirst($label).' #'.$id.': '.$e->getMessage());
    }
}
