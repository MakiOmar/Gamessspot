<?php

namespace App\Observers;

use App\Models\Game;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

class GameObserver
{
    /**
     * Handle the Game "created" event.
     *
     * @param  \App\Models\Game  $game
     * @return void
     */
    public function created(Game $game)
    {
        $this->invalidateGameCaches('created');
    }

    /**
     * Handle the Game "updated" event.
     *
     * @param  \App\Models\Game  $game
     * @return void
     */
    public function updated(Game $game)
    {
        $this->invalidateGameCaches('updated');
    }

    /**
     * Handle the Game "deleted" event.
     *
     * @param  \App\Models\Game  $game
     * @return void
     */
    public function deleted(Game $game)
    {
        $this->invalidateGameCaches('deleted');
    }

    /**
     * Handle the Game "restored" event.
     *
     * @param  \App\Models\Game  $game
     * @return void
     */
    public function restored(Game $game)
    {
        $this->invalidateGameCaches('restored');
    }

    /**
     * Invalidate game-related caches
     *
     * @param string $event
     * @return void
     */
    protected function invalidateGameCaches(string $event)
    {
        try {
            // Invalidate all game caches (PS4 and PS5 pages)
            CacheManager::invalidateGames();
        } catch (\Exception $e) {
            Log::error('Failed to invalidate game cache', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}

