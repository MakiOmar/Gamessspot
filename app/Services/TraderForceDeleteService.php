<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Order;
use App\Models\Trader;
use App\Models\TraderPayment;
use App\Models\TraderPurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Wipes a trader with all of its purchase orders, payments (and receipt files) and game accounts.
 * Customer sales (orders) are kept; their account link is nulled by the orders.account_id FK.
 */
class TraderForceDeleteService
{
    public function __construct(private SystemActivityLogger $activityLogger)
    {
    }

    /**
     * @return array{purchase_orders: int, payments: int, accounts: int, sold_accounts: int, sales: int}
     */
    public function summary(Trader $trader): array
    {
        $accountIds = $this->accountsQuery($trader)->pluck('id');

        return array(
            'purchase_orders' => $trader->purchaseOrders()->count(),
            'payments' => $trader->payments()->count(),
            'accounts' => $accountIds->count(),
            'sold_accounts' => Order::query()->whereIn('account_id', $accountIds)->distinct()->count('account_id'),
            'sales' => Order::query()->whereIn('account_id', $accountIds)->count(),
        );
    }

    /**
     * @return array<string, mixed> What was removed (also written to the activity log)
     */
    public function forceDelete(Trader $trader, string $reason, ?int $actorId): array
    {
        [$meta, $attachments] = DB::transaction(function () use ($trader) {
            $trader = Trader::query()->lockForUpdate()->findOrFail($trader->id);
            $summary = $this->summary($trader);

            $this->deleteAccounts($trader);

            $attachments = $trader->payments()->whereNotNull('attachment_path')->pluck('attachment_path')->all();
            $poNumbers = $trader->purchaseOrders()->pluck('po_number')->filter()->values()->all();

            TraderPayment::query()->where('trader_id', $trader->id)->delete();
            // Purchase order lines go with their order (cascadeOnDelete).
            TraderPurchaseOrder::query()->where('trader_id', $trader->id)->delete();
            $trader->delete();

            return array(array_merge($summary, array('trader_name' => $trader->name, 'po_numbers' => $poNumbers)), $attachments);
        });

        $this->deleteAttachments($trader->id, $attachments);
        CacheManager::invalidateAccounts();
        CacheManager::invalidateGames();

        $meta = array_merge($meta, array('reason' => $reason, 'actor_id' => $actorId));
        $this->activityLogger->log('trader.force_deleted', 'trader', $trader->id, $meta['trader_name'], $meta);

        return $meta;
    }

    private function accountsQuery(Trader $trader): Builder
    {
        return Account::query()->where(function (Builder $query) use ($trader) {
            $query->where('trader_id', $trader->id)
                ->orWhereIn('purchase_order_id', TraderPurchaseOrder::query()->select('id')->where('trader_id', $trader->id));
        });
    }

    private function deleteAccounts(Trader $trader): void
    {
        $accounts = $this->accountsQuery($trader)->lockForUpdate()->get();

        foreach ($accounts as $account) {
            // Unlink first: AccountObserver refuses to delete accounts that still carry a purchase source.
            $account->forceFill(array_fill_keys(Account::SOURCE_FIELDS, null))->saveQuietly();
            $account->delete();
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteAttachments(int $traderId, array $paths): void
    {
        $disk = Storage::disk(TraderPaymentService::ATTACHMENT_DISK);

        try {
            $disk->delete($paths);
            $disk->deleteDirectory(TraderPaymentService::ATTACHMENT_DIRECTORY . '/' . $traderId);
        } catch (\Throwable $e) {
            // Rows are already gone; orphaned files must not turn a completed delete into an error.
            Log::warning('Trader force delete: could not remove payment attachments', array(
                'trader_id' => $traderId,
                'error' => $e->getMessage(),
            ));
        }
    }
}
