<?php

namespace App\Services\NextLoad;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Models\NextLoadOrderItem;
use App\Services\SystemActivityLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Buys codes from Next Load without ever paying twice for the same sale:
 * the reference is stored before CreateOrder, timeouts are retried with the same reference,
 * and "reference is existing" is recovered through Orders + FetchOrder instead of buying again.
 */
class NextLoadPurchaseService
{
    /** Seconds between FetchOrder polls for InProgress orders (grows with attempts). */
    private const POLL_BASE_SECONDS = 60;

    public function __construct(
        private readonly NextLoadClient $client,
        private readonly NextLoadCatalog $catalog,
        private readonly SystemActivityLogger $activityLogger,
    ) {
    }

    /**
     * Supplier order for one sale. Storefront/POS lines reuse their open or completed-unused order on retry.
     * A consumed order (its code was sold and the sale undone, or released to stock) opens a new generation.
     */
    public function purchaseForSale(CardCategory $category, ?string $storefrontOrderId, ?string $lineKey, ?int $userId): NextLoadOrder
    {
        if ($storefrontOrderId !== null && $storefrontOrderId !== '') {
            $existing = NextLoadOrder::query()
                ->where('storefront_order_id', $storefrontOrderId)
                ->where('storefront_line_key', (string) $lineKey)
                ->where('status', '!=', NextLoadOrder::STATUS_FAILED)
                ->whereNull('consumed_at')
                ->latest('id')
                ->first();
            if ($existing) {
                return $this->send($existing);
            }
        }

        $order = $this->open($category, 1, NextLoadOrder::PURPOSE_SALE, $userId, $storefrontOrderId, $lineKey);

        return $this->send($order);
    }

    /**
     * Pre-buy codes into local stock (they become available cards once delivered).
     */
    public function restock(CardCategory $category, int $quantity, ?int $userId): NextLoadOrder
    {
        $order = $this->send($this->open($category, max(1, $quantity), NextLoadOrder::PURPOSE_RESTOCK, $userId));
        if ($order->isComplete()) {
            $this->releaseToStock($order);
        }

        return $order;
    }

    /**
     * Send (or resume) the CreateOrder call for an order row. Safe to call repeatedly.
     */
    public function send(NextLoadOrder $order): NextLoadOrder
    {
        if ($order->isComplete() || $order->isFailed()) {
            return $order;
        }
        if ($order->status === NextLoadOrder::STATUS_IN_PROGRESS && $order->next_load_order_id) {
            return $this->refresh($order);
        }

        $order->increment('attempts');

        try {
            $data = $this->client->createOrder($order->reference, $order->product_id, $order->quantity);
            $this->apply($order, $data);
            $this->trackBalance($order);
        } catch (NextLoadException $e) {
            $this->handleSendError($order, $e);
        }

        return $order->refresh();
    }

    /**
     * Keep the cached balance in step with what CreateOrder just charged (or re-read it after a rejection,
     * which is often an empty balance).
     */
    private function trackBalance(NextLoadOrder $order): void
    {
        if ($order->isFailed()) {
            $this->catalog->forgetBalance();

            return;
        }

        $amount = Money::toMillis($order->order_amount) > 0
            ? (string) $order->order_amount
            : Money::fromMillis(Money::toMillis($order->card_price) * (int) $order->quantity);
        $this->catalog->deductBalance($amount);
    }

    /**
     * Follow an InProgress order (or recover a sent order) with FetchOrder.
     */
    public function refresh(NextLoadOrder $order): NextLoadOrder
    {
        if (! $order->next_load_order_id) {
            return $this->recover($order);
        }

        $order->increment('attempts');

        try {
            $data = $this->client->fetchOrder($order->next_load_order_id);
        } catch (NextLoadException $e) {
            $order->update(['last_error' => $e->getMessage(), 'next_poll_at' => $this->nextPollAt($order)]);

            return $order->refresh();
        }

        if ($data === null) {
            $order->update(['last_error' => 'Order not found at Next Load yet.', 'next_poll_at' => $this->nextPollAt($order)]);

            return $order->refresh();
        }

        $this->apply($order, $data);

        return $order->refresh();
    }

    /**
     * Turn delivered, unused codes into available stock cards (restock or abandoned sale orders).
     */
    public function releaseToStock(NextLoadOrder $order): int
    {
        return DB::transaction(function () use ($order) {
            $locked = NextLoadOrder::whereKey($order->id)->lockForUpdate()->first();
            $released = 0;
            foreach ($locked->unconvertedItems()->get() as $item) {
                $this->createCard($locked, $item, true);
                $released++;
            }
            $locked->update(['consumed_at' => now()]);

            return $released;
        });
    }

    /**
     * Sold card for one delivered item, created inside the caller's transaction.
     */
    public function createCard(NextLoadOrder $order, NextLoadOrderItem $item, bool $available): Card
    {
        return Card::create([
            'code' => $item->pin,
            'cost' => $this->catalog->localCost((string) $item->price),
            'card_category_id' => $order->card_category_id,
            'status' => $available,
            'source' => Card::SOURCE_NEXT_LOAD,
            'next_load_order_item_id' => $item->id,
        ]);
    }

    private function open(
        CardCategory $category,
        int $quantity,
        string $purpose,
        ?int $userId,
        ?string $storefrontOrderId = null,
        ?string $lineKey = null,
    ): NextLoadOrder {
        if (! NextLoadClient::enabled() || ! $category->usesSupplier()) {
            throw new NextLoadException(NextLoadException::CONFIG, 'This category is not linked to Next Load.');
        }

        $product = $this->catalog->product((int) $category->next_load_product_id);
        if ($product === null) {
            throw new NextLoadException(NextLoadException::FAILED, 'The linked Next Load product is not available.');
        }
        if (! NextLoadCatalog::inStock($product)) {
            throw new NextLoadException(NextLoadException::FAILED, 'The linked Next Load product is out of stock at Next Load.');
        }
        if (! $this->catalog->withinLossGuard($category, $product['price'])) {
            throw new NextLoadException(NextLoadException::FAILED, 'The Next Load price is above the sale price.');
        }
        $limitLeft = $purpose === NextLoadOrder::PURPOSE_SALE ? $this->catalog->dailyLimitLeft($category) : null;
        if ($limitLeft !== null && $limitLeft < $quantity) {
            throw new NextLoadException(NextLoadException::FAILED, 'The daily Next Load limit for this category has been reached.');
        }

        $attributes = [
            'card_category_id' => $category->id,
            'product_id' => (int) $category->next_load_product_id,
            'product_name' => $product['product'],
            'quantity' => $quantity,
            'purpose' => $purpose,
            'card_price' => $product['price'],
            'status' => NextLoadOrder::STATUS_PENDING_SEND,
            'storefront_order_id' => $storefrontOrderId ?: null,
            'storefront_line_key' => $storefrontOrderId ? (string) $lineKey : null,
            'requested_by' => $userId,
            // Keep the poller away while the creating request is still talking to Next Load.
            'next_poll_at' => now()->addMinutes(2),
        ];

        if (! $storefrontOrderId) {
            return NextLoadOrder::create($attributes + ['reference' => 'acc-'.Str::lower((string) Str::ulid())]);
        }

        // Deterministic per line + generation: a retry after a Failed order gets a fresh reference.
        $generation = NextLoadOrder::where('storefront_order_id', $storefrontOrderId)
            ->where('storefront_line_key', (string) $lineKey)
            ->count() + 1;
        $reference = 'sf-'.substr(sha1($storefrontOrderId.'|'.$lineKey), 0, 24).'-'.$generation;

        try {
            return NextLoadOrder::create($attributes + ['reference' => $reference]);
        } catch (UniqueConstraintViolationException) {
            return NextLoadOrder::where('reference', $reference)->firstOrFail();
        }
    }

    /**
     * @param  array<string, mixed>  $data  CreateOrder `data` or FetchOrder `data[0]`
     */
    private function apply(NextLoadOrder $order, array $data): void
    {
        $status = strtolower((string) ($data['status'] ?? ''));
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        DB::transaction(function () use ($order, $data, $status, $items) {
            foreach ($items as $row) {
                $pin = is_array($row) ? trim((string) ($row['product_pin'] ?? '')) : '';
                if ($pin === '') {
                    continue;
                }
                $hash = Card::hashCode($pin);
                if (NextLoadOrderItem::where('pin_hash', $hash)->exists()) {
                    continue;
                }
                $order->items()->create([
                    'serial' => isset($row['product_sn']) ? (string) $row['product_sn'] : null,
                    'pin' => $pin,
                    'pin_hash' => $hash,
                    'price' => Money::normalize($row['price'] ?? ($data['card_price'] ?? $data['price'] ?? '0')),
                ]);
            }

            $delivered = $order->items()->count();
            $complete = $status === 'complete' && $delivered > 0;

            $order->fill([
                'next_load_order_id' => isset($data['order_id']) ? (string) $data['order_id'] : $order->next_load_order_id,
                'product_name' => (string) ($data['product'] ?? $order->product_name),
                'card_price' => Money::normalize($data['card_price'] ?? $data['price'] ?? $order->card_price),
                'order_amount' => Money::normalize($data['order_amount'] ?? $data['totalamount'] ?? $order->order_amount),
                'delivered_quantity' => $delivered,
            ]);

            if ($status === 'failed') {
                $order->fill(['status' => NextLoadOrder::STATUS_FAILED, 'last_error' => 'Next Load could not fulfil the order (not charged).', 'next_poll_at' => null]);
            } elseif ($complete) {
                $order->fill(['status' => NextLoadOrder::STATUS_COMPLETE, 'completed_at' => now(), 'last_error' => null, 'next_poll_at' => null]);
            } else {
                // InProgress, or Complete without codes yet: keep polling FetchOrder.
                $order->fill(['status' => NextLoadOrder::STATUS_IN_PROGRESS, 'next_poll_at' => $this->nextPollAt($order)]);
            }
            $order->save();
        });

        if ($order->isFailed()) {
            $this->logEvent('next_load.order_failed', $order, ['category_id' => $order->card_category_id]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta  never include pins or serials
     */
    private function logEvent(string $action, NextLoadOrder $order, array $meta = []): void
    {
        try {
            $this->activityLogger->log($action, 'next_load_order', (int) $order->id, $order->reference, $meta);
        } catch (\Throwable) {
            // Audit logging must never break a sale.
        }
    }

    private function handleSendError(NextLoadOrder $order, NextLoadException $e): void
    {
        if ($e->reason === NextLoadException::DUPLICATE_REFERENCE) {
            $this->recover($order);

            return;
        }

        if ($e->isRetryable()) {
            // The order may exist at Next Load: keep the reference and retry later.
            $order->update(['last_error' => $e->getMessage(), 'next_poll_at' => $this->nextPollAt($order)]);

            return;
        }

        $order->update([
            'status' => NextLoadOrder::STATUS_FAILED,
            'last_error' => mb_substr($e->getMessage(), 0, 500),
            'next_poll_at' => null,
        ]);
        $this->catalog->forgetBalance();
        $this->logEvent('next_load.order_rejected', $order, ['reason' => $e->reason]);
    }

    /**
     * The reference was already used: find the original order by reference and load its codes.
     */
    private function recover(NextLoadOrder $order): NextLoadOrder
    {
        $from = Carbon::parse($order->created_at ?? now())->subDay()->toDateString();
        $to = now()->addDay()->toDateString();

        try {
            $match = collect($this->client->orders($from, $to))
                ->first(fn ($row) => is_array($row) && (string) ($row['reference'] ?? '') === $order->reference);
        } catch (NextLoadException $e) {
            $order->update(['last_error' => $e->getMessage(), 'next_poll_at' => $this->nextPollAt($order)]);

            return $order->refresh();
        }

        if (! $match || empty($match['order_id'])) {
            $order->update(['last_error' => 'Reference already used; waiting for it to appear in Next Load orders.', 'next_poll_at' => $this->nextPollAt($order)]);

            return $order->refresh();
        }

        $order->update(['next_load_order_id' => (string) $match['order_id'], 'status' => NextLoadOrder::STATUS_IN_PROGRESS]);

        return $this->refresh($order->refresh());
    }

    private function nextPollAt(NextLoadOrder $order): Carbon
    {
        $attempts = max(1, (int) $order->attempts);

        return now()->addSeconds(min(900, self::POLL_BASE_SECONDS * $attempts));
    }
}
