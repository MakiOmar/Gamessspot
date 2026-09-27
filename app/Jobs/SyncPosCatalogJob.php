<?php

namespace App\Jobs;

use App\Models\CardCategory;
use App\Models\Game;
use App\Services\PosCatalogSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Pushes one game (all offers) or one card category to POS after the manager save commits.
 */
class SyncPosCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(
        public string $kind,
        public int $id,
        public bool $deleted = false,
        public ?string $title = null,
        public ?string $code = null,
    ) {
        $this->afterCommit();
    }

    public static function game(Game $game): self
    {
        return new self('game', (int) $game->id);
    }

    public static function deletedGame(Game $game): self
    {
        return new self('game', (int) $game->id, true, (string) $game->title, (string) $game->code);
    }

    public static function card(CardCategory $category, bool $deleted = false): self
    {
        return new self('card', (int) $category->id, $deleted, (string) $category->name);
    }

    public function handle(PosCatalogSync $sync): void
    {
        if (! PosCatalogSync::enabled()) {
            return;
        }

        try {
            $this->run($sync);
        } catch (\Throwable $e) {
            // Inline (sync) dispatch runs inside the manager request: log instead of failing the save.
            if ($this->job === null || $this->job->getConnectionName() === 'sync') {
                $this->failed($e);

                return;
            }
            throw $e;
        }
    }

    private function run(PosCatalogSync $sync): void
    {
        if ($this->kind === 'game') {
            if ($this->deleted) {
                $sync->deactivateGame($this->id, (string) $this->title, $this->code);

                return;
            }
            $game = Game::find($this->id);
            if ($game) {
                $sync->pushGame($game);
            }

            return;
        }

        $category = CardCategory::find($this->id);
        if ($category) {
            $sync->pushCardCategory($category);
        } elseif ($this->deleted) {
            $stub = new CardCategory(['name' => (string) $this->title, 'price' => 0]);
            $stub->id = $this->id;
            $sync->pushCardCategory($stub, false);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('POS catalog sync failed', [
            'kind' => $this->kind,
            'id' => $this->id,
            'deleted' => $this->deleted,
            'message' => $e->getMessage(),
        ]);
    }
}
