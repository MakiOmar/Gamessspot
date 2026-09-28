<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Trader;
use App\Models\TraderPurchaseOrder;
use App\Models\TraderPurchaseOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(private SystemActivityLogger $activityLogger)
    {
    }

    /**
     * @param  array{trader_id: int, purchase_date: string, notes?: string|null, items: array<int, array<string, mixed>>}  $data
     */
    public function create(array $data, ?int $actorId): TraderPurchaseOrder
    {
        $trader = Trader::findOrFail($data['trader_id']);

        if (! $trader->isActive()) {
            throw ValidationException::withMessages(array(
                'trader_id' => 'Purchase orders cannot be created for an inactive trader.',
            ));
        }

        $order = DB::transaction(function () use ($data, $actorId) {
            $order = TraderPurchaseOrder::create(array(
                'trader_id' => $data['trader_id'],
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
                'status' => TraderPurchaseOrder::STATUS_ACTIVE,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ));

            $order->po_number = $this->formatNumber('PO-', $order->id);

            foreach ($data['items'] as $line) {
                $order->items()->create($this->lineAttributes($line));
            }

            $this->refreshTotals($order);

            return $order;
        });

        $this->activityLogger->log(
            'purchase_order.created',
            'purchase_order',
            $order->id,
            $order->po_number,
            array(
                'trader_id' => $order->trader_id,
                'trader_name' => $trader->name,
                'total_quantity' => $order->total_quantity,
                'total_cost' => (string) $order->total_cost,
            )
        );

        return $order;
    }

    /**
     * @param  array{trader_id: int, purchase_date: string, notes?: string|null, items: array<int, array<string, mixed>>}  $data
     */
    public function update(TraderPurchaseOrder $order, array $data, ?int $actorId): TraderPurchaseOrder
    {
        $order = DB::transaction(function () use ($order, $data, $actorId) {
            $order = TraderPurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->isCancelled()) {
                throw ValidationException::withMessages(array('order' => 'A cancelled purchase order cannot be edited.'));
            }

            $existing = $order->items()->withCount('accounts')->get()->keyBy('id');
            $hasImported = $existing->sum('accounts_count') > 0;

            $this->assertHeaderEditable($order, $data, $hasImported);

            $submittedIds = collect($data['items'])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $removed = $existing->except($submittedIds);
            $this->assertRemovable($removed);
            TraderPurchaseOrderItem::whereIn('id', $removed->keys())->delete();

            $this->syncLines($order, $existing, $data['items']);

            $order->fill(array(
                'trader_id' => $data['trader_id'],
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actorId,
            ));

            $this->refreshTotals($order);

            return $order;
        });

        $this->activityLogger->log(
            'purchase_order.updated',
            'purchase_order',
            $order->id,
            $order->po_number,
            array(
                'total_quantity' => $order->total_quantity,
                'total_cost' => (string) $order->total_cost,
            )
        );

        return $order;
    }

    public function void(TraderPurchaseOrder $order, string $reason, ?int $actorId): TraderPurchaseOrder
    {
        $order = DB::transaction(function () use ($order, $reason, $actorId) {
            $order = TraderPurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->isCancelled()) {
                throw ValidationException::withMessages(array('order' => 'This purchase order is already cancelled.'));
            }

            if ($order->accounts()->exists()) {
                throw ValidationException::withMessages(array(
                    'order' => 'This purchase order has imported accounts and cannot be voided.',
                ));
            }

            $order->update(array(
                'status' => TraderPurchaseOrder::STATUS_CANCELLED,
                'cancelled_by' => $actorId,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'updated_by' => $actorId,
            ));

            return $order;
        });

        $this->activityLogger->log(
            'purchase_order.voided',
            'purchase_order',
            $order->id,
            $order->po_number,
            array('reason' => $reason, 'total_cost' => (string) $order->total_cost)
        );

        return $order;
    }

    public function remainingForItem(TraderPurchaseOrderItem $item): int
    {
        $imported = Account::where('purchase_order_item_id', $item->id)->count();

        return max(0, (int) $item->quantity - $imported);
    }

    /**
     * Lock the line row and return it with its remaining quantity; throws when the order is not usable.
     *
     * @return array{0: TraderPurchaseOrderItem, 1: int}
     */
    public function lockItemForImport(int $itemId): array
    {
        $item = TraderPurchaseOrderItem::query()->lockForUpdate()->with('purchaseOrder.trader')->findOrFail($itemId);
        $order = $item->purchaseOrder;

        if ($order->isCancelled()) {
            throw ValidationException::withMessages(array(
                'purchase_order_item_id' => 'The selected purchase order is cancelled.',
            ));
        }

        return array($item, $this->remainingForItem($item));
    }

    /**
     * Active purchase orders of a trader with lines and remaining quantities (for account forms).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function linesForTrader(Trader $trader): Collection
    {
        return $trader->purchaseOrders()
            ->active()
            ->with(array('items' => fn ($q) => $q->withCount('accounts')->with('game:id,title')))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TraderPurchaseOrder $order) => array(
                'id' => $order->id,
                'po_number' => $order->po_number,
                'purchase_date' => $order->purchase_date?->toDateString(),
                'items' => $order->items->map(fn (TraderPurchaseOrderItem $item) => array(
                    'id' => $item->id,
                    'game_id' => $item->game_id,
                    'game_title' => $item->game?->title,
                    'quantity' => $item->quantity,
                    'imported' => $item->importedCount(),
                    'remaining' => $item->remaining(),
                    'cost_per_account' => (string) $item->cost_per_account,
                ))->values(),
            ));
    }

    public function formatNumber(string $prefix, int $id): string
    {
        return $prefix . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function lineAttributes(array $line): array
    {
        $quantity = (int) $line['quantity'];
        $cost = round((float) $line['cost_per_account'], 2);

        return array(
            'game_id' => (int) $line['game_id'],
            'quantity' => $quantity,
            'cost_per_account' => $cost,
            'total_cost' => round($quantity * $cost, 2),
        );
    }

    private function refreshTotals(TraderPurchaseOrder $order): void
    {
        $items = $order->items()->get();

        $order->total_quantity = (int) $items->sum('quantity');
        $order->total_cost = round((float) $items->sum('total_cost'), 2);
        $order->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertHeaderEditable(TraderPurchaseOrder $order, array $data, bool $hasImported): void
    {
        if (! $hasImported) {
            return;
        }

        if ((int) $data['trader_id'] !== (int) $order->trader_id) {
            throw ValidationException::withMessages(array(
                'trader_id' => 'The trader cannot be changed after accounts were imported.',
            ));
        }

        if ($order->purchase_date?->toDateString() !== date('Y-m-d', strtotime($data['purchase_date']))) {
            throw ValidationException::withMessages(array(
                'purchase_date' => 'The purchase date cannot be changed after accounts were imported.',
            ));
        }
    }

    /**
     * @param  Collection<int, TraderPurchaseOrderItem>  $existing
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(TraderPurchaseOrder $order, Collection $existing, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $attributes = $this->lineAttributes($line);
            $itemId = ! empty($line['id']) ? (int) $line['id'] : null;

            if ($itemId === null) {
                $order->items()->create($attributes);
                continue;
            }

            $item = $existing->get($itemId);

            if ($item === null) {
                throw ValidationException::withMessages(array("items.$index.id" => 'Invalid purchase order line.'));
            }

            $this->assertLineEditable($item, $attributes, $index);
            $item->fill($attributes)->save();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertLineEditable(TraderPurchaseOrderItem $item, array $attributes, int $index): void
    {
        $imported = (int) $item->accounts_count;

        if ($imported === 0) {
            return;
        }

        if ($attributes['game_id'] !== (int) $item->game_id
            || round($attributes['cost_per_account'], 2) !== round((float) $item->cost_per_account, 2)) {
            throw ValidationException::withMessages(array(
                "items.$index.game_id" => 'Game and cost cannot change on a line that already has imported accounts.',
            ));
        }

        if ($attributes['quantity'] < $imported) {
            throw ValidationException::withMessages(array(
                "items.$index.quantity" => "Quantity cannot be lower than the {$imported} account(s) already imported.",
            ));
        }
    }

    /**
     * @param  Collection<int, TraderPurchaseOrderItem>  $removed
     */
    private function assertRemovable(Collection $removed): void
    {
        $blocked = $removed->first(fn (TraderPurchaseOrderItem $item) => (int) $item->accounts_count > 0);

        if ($blocked !== null) {
            throw ValidationException::withMessages(array(
                'items' => 'A line with imported accounts cannot be removed.',
            ));
        }
    }
}
