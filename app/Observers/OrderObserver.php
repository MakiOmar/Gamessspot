<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

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
            CacheManager::invalidateOrders();
            
            Log::debug('Order cache invalidated', [
                'event' => $event,
                'observer' => 'OrderObserver'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate order cache', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}

