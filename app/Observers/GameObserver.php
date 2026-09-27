<?php

namespace App\Observers;

use App\Jobs\SyncPosCatalogJob;
use App\Models\Account;
use App\Models\Game;
use App\Models\Order;
use App\Services\CacheManager;
use App\Services\PosCatalogSync;
use App\Services\SystemActivityLogger;
use Illuminate\Support\Facades\Log;

class GameObserver
{
    public function __construct(
        protected SystemActivityLogger $activityLogger
    ) {
    }

    /**
     * Columns that change a POS offer product (name, price, or sold/not sold).
     */
    private const POS_FIELDS = [
        'title', 'code', 'full_price',
        'ps4_primary_price', 'ps4_secondary_price', 'ps4_offline_price',
        'ps5_primary_price', 'ps5_secondary_price', 'ps5_offline_price',
        'ps4_primary_status', 'ps4_secondary_status', 'ps4_offline_status',
        'ps5_primary_status', 'ps5_secondary_status', 'ps5_offline_status',
    ];

    public function created(Game $game)
    {
        $this->invalidateGameCaches('created');
        $this->queuePosSync(SyncPosCatalogJob::game($game));
    }

    public function updated(Game $game)
    {
        $this->invalidateGameCaches('updated');
        if ($game->wasChanged(self::POS_FIELDS)) {
            $this->queuePosSync(SyncPosCatalogJob::game($game));
        }
    }

    /**
     * DB cascade deletes accounts without Eloquent events — pre-log here.
     */
    public function deleting(Game $game)
    {
        $accounts = Account::query()->where('game_id', $game->id)->get();

        foreach ($accounts as $account) {
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
                        'cause' => 'game_deleted_cascade',
                        'game_id' => $game->id,
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
                    'game_id' => $game->id,
                    'cause' => 'game_deleted_cascade',
                    'orders_affected' => $orders->count(),
                )
            );
        }

        $this->activityLogger->log(
            'game.deleted',
            'game',
            (int) $game->id,
            $game->title ?: ('Game #' . $game->id),
            array(
                'product_type' => $game->product_type ?? null,
                'accounts_cascaded' => $accounts->count(),
            )
        );
    }

    public function deleted(Game $game)
    {
        $this->invalidateGameCaches('deleted');
        $this->queuePosSync(SyncPosCatalogJob::deletedGame($game));
    }

    /**
     * A queue or POS outage must never fail the manager save.
     */
    protected function queuePosSync(SyncPosCatalogJob $job): void
    {
        if (! PosCatalogSync::enabled()) {
            return;
        }
        try {
            dispatch($job);
        } catch (\Throwable $e) {
            Log::warning('POS catalog sync could not be queued', ['kind' => $job->kind, 'id' => $job->id, 'message' => $e->getMessage()]);
        }
    }

    public function restored(Game $game)
    {
        $this->invalidateGameCaches('restored');
    }

    protected function invalidateGameCaches(string $event)
    {
        try {
            CacheManager::invalidateGames();
        } catch (\Exception $e) {
            // Silently fail - cache invalidation should not break the application
        }
    }
}
