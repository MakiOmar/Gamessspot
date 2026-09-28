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
        if ($account->hasPurchaseSource()) {
            throw new \DomainException("Account #{$account->id} is linked to a trader purchase order and cannot be deleted.");
        }

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
            array_merge(array(
                'game_id' => $account->game_id,
                'orders_affected' => $orders->count(),
            ), $this->purchaseSourceMeta($account))
        );
    }

    /**
     * Keeps the supplier traceable after the account row is gone.
     *
     * @return array<string, mixed>
     */
    protected function purchaseSourceMeta(Account $account): array
    {
        if (! $account->hasPurchaseSource()) {
            return array();
        }

        $account->loadMissing(array('trader:id,name', 'purchaseOrder:id,po_number'));

        return array(
            'trader_id' => $account->trader_id,
            'trader_name' => $account->trader?->name,
            'purchase_order_id' => $account->purchase_order_id,
            'po_number' => $account->purchaseOrder?->po_number,
            'purchase_date' => $account->purchase_date?->toDateString(),
            'original_cost' => $account->original_cost !== null ? (string) $account->original_cost : null,
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
