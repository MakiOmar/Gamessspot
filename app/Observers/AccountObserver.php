<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Order;
use App\Services\CacheManager;
use App\Services\SystemActivityLogger;

class AccountObserver
{
    public function __construct(
        protected SystemActivityLogger $activityLogger
    ) {
    }

    public function created(Account $account)
    {
        $this->invalidateAccountCaches('created');
    }

    public function updated(Account $account)
    {
        $this->invalidateAccountCaches('updated');
    }

    /**
     * Before DB delete: log order FK nulls (SET NULL) that Eloquent will not see.
     */
    public function deleting(Account $account)
    {
        $orders = Order::query()
            ->where('account_id', $account->id)
            ->get(array('id', 'account_id', 'buyer_name', 'buyer_phone'));

        foreach ($orders as $order) {
            $this->activityLogger->log(
                'order.account_id_nulled',
                'order',
                (int) $order->id,
                'Order #' . $order->id,
                array(
                    'previous_account_id' => $account->id,
                    'account_mail' => $account->mail,
                    'cause' => 'account_deleted',
                    'buyer_name' => $order->buyer_name,
                    'buyer_phone' => $order->buyer_phone,
                )
            );
        }

        $this->activityLogger->log(
            'account.deleted',
            'account',
            (int) $account->id,
            $account->mail ?: ('Account #' . $account->id),
            array(
                'game_id' => $account->game_id,
                'orders_affected' => $orders->count(),
            )
        );
    }

    public function deleted(Account $account)
    {
        $this->invalidateAccountCaches('deleted');
    }

    public function restored(Account $account)
    {
        $this->invalidateAccountCaches('restored');
    }

    protected function invalidateAccountCaches(string $event)
    {
        try {
            CacheManager::invalidateAccounts();
        } catch (\Exception $e) {
            // Silently fail - cache invalidation should not break the application
        }
    }
}
