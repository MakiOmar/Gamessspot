<?php

namespace App\Services\NextLoad;

use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Services\CardSourcingService;
use App\Services\SystemActivityLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Background upkeep for the Next Load integration (driven by the nextload:* scheduled commands).
 */
class NextLoadSyncService
{
    private const LOW_BALANCE_ALERT_KEY = 'nextload:low_balance_alerted';

    private const LOW_BALANCE_ALERT_TTL = 21600;

    public const LAST_PRODUCT_SYNC_KEY = 'nextload:products_synced_at';

    public function __construct(
        private readonly NextLoadClient $client,
        private readonly NextLoadCatalog $catalog,
        private readonly NextLoadPurchaseService $purchases,
        private readonly CardSourcingService $sales,
        private readonly SystemActivityLogger $activityLogger,
    ) {
    }

    /**
     * Resend timed-out orders and poll InProgress ones; record delayed manager sales and deliver
     * orphaned codes to stock.
     *
     * @return array{checked:int, completed:int, failed:int, recorded:int, released:int, stuck:int}
     */
    public function pollDue(int $limit = 50): array
    {
        $stats = ['checked' => 0, 'completed' => 0, 'failed' => 0, 'recorded' => 0, 'released' => 0, 'stuck' => 0];

        $orders = NextLoadOrder::query()
            ->whereIn('status', [NextLoadOrder::STATUS_PENDING_SEND, NextLoadOrder::STATUS_IN_PROGRESS])
            ->where(fn ($q) => $q->whereNull('next_poll_at')->orWhere('next_poll_at', '<=', now()))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($orders as $order) {
            if ($order->attempts >= NextLoadOrder::MAX_POLL_ATTEMPTS) {
                $this->flagStuck($order);
                $stats['stuck']++;

                continue;
            }

            $stats['checked']++;
            $order = $this->purchases->send($order);
            if ($order->isFailed()) {
                $stats['failed']++;
            } elseif ($order->isComplete()) {
                $stats['completed']++;
                $this->settleCompleted($order, $stats);
            }
        }

        // Manager orders completed outside the poll (e.g. Refresh status, reconcile) that are still unused.
        NextLoadOrder::query()
            ->where('status', NextLoadOrder::STATUS_COMPLETE)
            ->whereNull('consumed_at')
            ->whereNull('storefront_order_id')
            ->where('updated_at', '<=', now()->subMinutes(5))
            ->limit($limit)
            ->get()
            ->each(function (NextLoadOrder $order) use (&$stats) {
                $this->settleCompleted($order, $stats);
            });

        return $stats;
    }

    /**
     * A delivered manager sale becomes that sale; restock orders, old rows without sale details and sales that
     * cannot be recorded go to stock.
     *
     * @param  array<string, int>  $stats
     */
    public function settleCompleted(NextLoadOrder $order, array &$stats): void
    {
        if ($order->awaitsManagerSale() && $this->sales->recordPendingSale($order)) {
            $stats['recorded']++;

            return;
        }

        $stats['released'] += $this->releaseIfOrphaned($order->refresh());
    }

    /**
     * @return array{balance:?string, low:bool}
     */
    public function checkBalance(): array
    {
        $balance = $this->catalog->balance(true);
        $threshold = (string) config('services.nextload.low_balance', '0');
        $low = $balance !== null && Money::toMillis($threshold) > 0 && Money::compare($balance, $threshold) < 0;

        if ($low && ! Cache::has(self::LOW_BALANCE_ALERT_KEY)) {
            Cache::put(self::LOW_BALANCE_ALERT_KEY, true, self::LOW_BALANCE_ALERT_TTL);
            Log::warning('Next Load balance is low', ['balance' => $balance, 'threshold' => $threshold]);
            $this->audit('next_load.low_balance', null, null, ['balance' => $balance, 'threshold' => $threshold]);
        } elseif (! $low) {
            Cache::forget(self::LOW_BALANCE_ALERT_KEY);
        }

        return ['balance' => $balance, 'low' => $low];
    }

    /**
     * Compare supplier orders with local rows for a date range.
     *
     * @return array{supplier:int, unknown:list<string>, mismatched:list<string>}
     */
    public function reconcile(string $from, string $to): array
    {
        $remote = collect($this->client->orders($from, $to))->filter(fn ($row) => is_array($row) && isset($row['reference']));
        $local = NextLoadOrder::whereIn('reference', $remote->pluck('reference')->map(fn ($r) => (string) $r)->all())
            ->get()
            ->keyBy('reference');

        $unknown = [];
        $mismatched = [];
        foreach ($remote as $row) {
            $reference = (string) $row['reference'];
            $order = $local->get($reference);
            if (! $order) {
                $unknown[] = $reference;

                continue;
            }

            $remoteStatus = strtolower((string) ($row['status'] ?? ''));
            $expected = match ($remoteStatus) {
                'complete' => NextLoadOrder::STATUS_COMPLETE,
                'failed' => NextLoadOrder::STATUS_FAILED,
                default => NextLoadOrder::STATUS_IN_PROGRESS,
            };
            if ($order->status !== $expected) {
                $mismatched[] = $reference;
                if (! $order->next_load_order_id && isset($row['order_id'])) {
                    $order->update(['next_load_order_id' => (string) $row['order_id']]);
                }
                if ($order->isOpen()) {
                    $this->purchases->refresh($order->refresh());
                }
            }
        }

        if ($unknown !== [] || $mismatched !== []) {
            Log::warning('Next Load reconciliation found differences', ['from' => $from, 'to' => $to, 'unknown' => $unknown, 'mismatched' => $mismatched]);
            $this->audit('next_load.reconcile_mismatch', null, null, ['from' => $from, 'to' => $to, 'unknown' => $unknown, 'mismatched' => $mismatched]);
        }

        return ['supplier' => $remote->count(), 'unknown' => $unknown, 'mismatched' => $mismatched];
    }

    /**
     * Refresh supplier_cost and product name on linked categories from the live price list.
     *
     * @return array{updated:int, missing:list<int>}
     */
    public function syncProducts(): array
    {
        $products = collect($this->client->products(true))->keyBy('product_id');
        $updated = 0;
        $missing = [];

        CardCategory::whereNotNull('next_load_product_id')->get()->each(function (CardCategory $category) use ($products, &$updated, &$missing) {
            $product = $products->get((int) $category->next_load_product_id);
            if (! $product) {
                $missing[] = (int) $category->id;

                return;
            }
            $category->forceFill([
                'supplier_cost' => $product['price'],
                'supplier_product_name' => $product['product'],
                'supplier_synced_at' => now(),
            ])->saveQuietly();
            $updated++;
        });

        Cache::forever(self::LAST_PRODUCT_SYNC_KEY, now()->toIso8601String());
        if ($missing !== []) {
            Log::warning('Next Load products no longer listed for linked categories', ['category_ids' => $missing]);
        }

        return ['updated' => $updated, 'missing' => $missing];
    }

    private function releaseIfOrphaned(NextLoadOrder $order): int
    {
        $orphaned = $order->purpose === NextLoadOrder::PURPOSE_RESTOCK || ! $order->storefront_order_id;
        if (! $orphaned || $order->consumed_at || ! $order->unconvertedItems()->exists()) {
            return 0;
        }

        $released = $this->purchases->releaseToStock($order);
        $this->audit('next_load.released_to_stock', 'next_load_order', (int) $order->id, ['reference' => $order->reference, 'count' => $released]);

        return $released;
    }

    private function flagStuck(NextLoadOrder $order): void
    {
        $order->update(['next_poll_at' => now()->addDay(), 'last_error' => 'Stopped polling after '.$order->attempts.' attempts; check this order with Next Load support.']);
        Log::warning('Next Load order stuck', ['reference' => $order->reference]);
    }

    private function audit(string $action, ?string $subjectType, ?int $subjectId, array $meta): void
    {
        try {
            $this->activityLogger->log($action, $subjectType, $subjectId, null, $meta);
        } catch (\Throwable) {
            // Audit logging must never stop background upkeep.
        }
    }
}
