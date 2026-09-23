<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CacheManager;
use App\Services\SystemActivityLogger;

class OrderObserver
{
    public function __construct(
        protected SystemActivityLogger $activityLogger
    ) {
    }

    public function created(Order $order)
    {
        $this->invalidateOrderCaches('created');
    }

    /**
     * Detect account_id / card_id cleared after having a value.
     */
    public function updating(Order $order)
    {
        if ($order->isDirty('account_id')) {
            $original = $order->getOriginal('account_id');
            if ($original !== null && $order->account_id === null) {
                $this->activityLogger->log(
                    'order.account_id_nulled',
                    'order',
                    (int) $order->id,
                    'Order #' . $order->id,
                    array(
                        'previous_account_id' => $original,
                        'cause' => 'eloquent_update',
                    )
                );
            }
        }

        if ($order->isDirty('card_id')) {
            $original = $order->getOriginal('card_id');
            if ($original !== null && $order->card_id === null) {
                $this->activityLogger->log(
                    'order.card_id_nulled',
                    'order',
                    (int) $order->id,
                    'Order #' . $order->id,
                    array(
                        'previous_card_id' => $original,
                        'cause' => 'eloquent_update',
                    )
                );
            }
        }
    }

    public function updated(Order $order)
    {
        $this->invalidateOrderCaches('updated');
    }

    public function deleted(Order $order)
    {
        $this->activityLogger->log(
            'order.deleted',
            'order',
            (int) $order->id,
            'Order #' . $order->id,
            array(
                'account_id' => $order->account_id,
                'card_id' => $order->card_id,
                'sold_item' => $order->sold_item,
                'buyer_name' => $order->buyer_name,
                'buyer_phone' => $order->buyer_phone,
                'price' => $order->price,
            )
        );

        $this->invalidateOrderCaches('deleted');
    }

    public function restored(Order $order)
    {
        $this->invalidateOrderCaches('restored');
    }

    protected function invalidateOrderCaches(string $event)
    {
        try {
            CacheManager::invalidateOrders();
            CacheManager::invalidateAccounts();
            CacheManager::invalidateGames();
        } catch (\Exception $e) {
            // Silently fail - cache invalidation should not break the application
        }
    }
}
