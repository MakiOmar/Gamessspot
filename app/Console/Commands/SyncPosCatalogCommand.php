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
        $onlyGames = (bool) $this->option('games');
        $onlyCards = (bool) $this->option('cards');
        $ids = array_filter(array_map('intval', (array) $this->option('id')));
        $failed = 0;

        if (! $onlyCards) {
            $failed += $this->syncModels(
                Game::query()->when($ids, fn ($q) => $q->whereIn('id', $ids)),
                fn (Game $game) => $sync->pushGame($game),
                'game'
            );
        }

        if (! $onlyGames) {
            $failed += $this->syncModels(
                CardCategory::query()->when($ids, fn ($q) => $q->whereIn('id', $ids)),
                fn (CardCategory $category) => $sync->pushCardCategory($category),
                'card category'
            );
        }

        if ($failed > 0) {
            $this->warn($failed.' item(s) failed; see the log. Re-run to retry.');

            return self::FAILURE;
        }

        $this->info('POS catalog is in sync.');

        return self::SUCCESS;
    }

    private function syncModels($query, callable $push, string $label): int
    {
        $failed = 0;
        $done = 0;

        $query->orderBy('id')->chunkById(50, function ($models) use ($push, $label, &$failed, &$done) {
            foreach ($models as $model) {
                try {
                    $push($model);
                    $done++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('pos:sync-catalog failed', ['type' => $label, 'id' => $model->id, 'message' => $e->getMessage()]);
                    $this->error(ucfirst($label).' #'.$model->id.': '.$e->getMessage());
                }
            }
        });

        $this->line('Synced '.$done.' '.$label.'(s).');

        return $failed;
    }
}
