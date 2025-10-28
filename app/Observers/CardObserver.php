<?php

namespace App\Observers;

use App\Models\Card;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

class CardObserver
{
    /**
     * Handle the Card "created" event.
     *
     * @param  \App\Models\Card  $card
     * @return void
     */
    public function created(Card $card)
    {
        $this->invalidateCardCaches('created');
    }

    /**
     * Handle the Card "updated" event.
     *
     * @param  \App\Models\Card  $card
     * @return void
     */
    public function updated(Card $card)
    {
        $this->invalidateCardCaches('updated');
    }

    /**
     * Handle the Card "deleted" event.
     *
     * @param  \App\Models\Card  $card
     * @return void
     */
    public function deleted(Card $card)
    {
        $this->invalidateCardCaches('deleted');
    }

    /**
     * Handle the Card "restored" event.
     *
     * @param  \App\Models\Card  $card
     * @return void
     */
    public function restored(Card $card)
    {
        $this->invalidateCardCaches('restored');
    }

    /**
     * Invalidate card-related caches
     *
     * @param string $event
     * @return void
     */
    protected function invalidateCardCaches(string $event)
    {
        try {
            CacheManager::invalidateCards();
            
            Log::debug('Card cache invalidated', [
                'event' => $event,
                'observer' => 'CardObserver'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate card cache', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}

