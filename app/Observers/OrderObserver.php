<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CacheManager;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function created(Order $order)
    {
        $this->invalidateOrderCaches('created');
    }

    /**
     * Handle the Order "updated" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function updated(Order $order)
    {
        $this->invalidateOrderCaches('updated');
    }

    /**
     * Handle the Order "deleted" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function deleted(Order $order)
    {
        $this->invalidateOrderCaches('deleted');
    }

    /**
     * Handle the Order "restored" event.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function restored(Order $order)
    {
        $this->invalidateOrderCaches('restored');
    }

    /**
     * Invalidate order-related caches
     *
     * @param string $event
     * @return void
     */
    protected function invalidateOrderCaches(string $event)
    {
        try {
            // Invalidate orders cache
            CacheManager::invalidateOrders();
            
            // Also invalidate accounts cache since orders affect account stock
            CacheManager::invalidateAccounts();
            
            // Invalidate games cache since game listings show account stock
            CacheManager::invalidateGames();
        } catch (\Exception $e) {
            // Silently fail - cache invalidation should not break the application
        }
    }
}

