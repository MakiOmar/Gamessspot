<?php

namespace App\Services;

use App\Models\Trader;
use App\Models\TraderPayment;
use App\Models\TraderPurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TraderLedgerService
{
    /**
     * Attach aggregate totals as subqueries so trader listings avoid N+1 queries.
     */
    public function withTotals(Builder $query): Builder
    {
        return $query
            ->withSum(array('purchaseOrders as purchases_total' => fn ($q) => $q->active()), 'total_cost')
            ->withSum(array('payments as payments_total' => fn ($q) => $q->active()), 'amount')
            ->withCount(array('purchaseOrders as purchase_orders_count' => fn ($q) => $q->active()))
            ->withCount('accounts as accounts_count');
    }

    /**
     * @return array{opening_balance: float, purchases: float, payments: float, balance: float, purchase_orders: int, accounts: int}
     */
    public function totals(Trader $trader): array
    {
        if (! array_key_exists('purchases_total', $trader->getAttributes())) {
            $trader = $this->withTotals(Trader::query())->findOrFail($trader->id);
        }

        return $this->totalsFromAggregates($trader);
    }

    /**
     * @return array{opening_balance: float, purchases: float, payments: float, balance: float, purchase_orders: int, accounts: int}
     */
    public function totalsFromAggregates(Trader $trader): array
    {
        $opening = (float) $trader->opening_balance;
        $purchases = (float) ($trader->purchases_total ?? 0);
        $payments = (float) ($trader->payments_total ?? 0);

        return array(
            'opening_balance' => $opening,
            'purchases' => $purchases,
            'payments' => $payments,
            'balance' => round($opening + $purchases - $payments, 2),
            'purchase_orders' => (int) ($trader->purchase_orders_count ?? 0),
            'accounts' => (int) ($trader->accounts_count ?? 0),
        );
    }

    /**
     * Chronological statement rows with a running balance.
     * Cancelled entries are included for visibility but do not affect the balance.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function statement(Trader $trader): Collection
    {
        $entries = collect();

        if ((float) $trader->opening_balance !== 0.0) {
            $entries->push(array(
                'date' => $trader->opening_balance_date ?? $trader->created_at,
                'sort_key' => '0',
                'type' => 'opening',
                'type_label' => 'Opening Balance',
                'reference' => null,
                'url' => null,
                'description' => 'Opening balance',
                'debit' => max(0.0, (float) $trader->opening_balance),
                'credit' => max(0.0, -(float) $trader->opening_balance),
                'cancelled' => false,
            ));
        }

        $trader->purchaseOrders()
            ->withCount('items')
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get()
            ->each(function (TraderPurchaseOrder $order) use ($entries) {
                $entries->push(array(
                    'date' => $order->purchase_date,
                    'sort_key' => '1-' . $order->created_at?->format('YmdHis') . '-' . str_pad((string) $order->id, 10, '0', STR_PAD_LEFT),
                    'type' => 'purchase_order',
                    'type_label' => 'Purchase Order',
                    'reference' => $order->po_number,
                    'url' => route('manager.purchase-orders.show', $order),
                    'description' => $order->total_quantity . ' account(s)' . ($order->notes ? ' - ' . $order->notes : ''),
                    'debit' => (float) $order->total_cost,
                    'credit' => 0.0,
                    'cancelled' => $order->isCancelled(),
                ));
            });

        $trader->payments()
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(function (TraderPayment $payment) use ($entries) {
                $entries->push(array(
                    'date' => $payment->payment_date,
                    'sort_key' => '2-' . $payment->created_at?->format('YmdHis') . '-' . str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT),
                    'type' => 'payment',
                    'type_label' => 'Payment',
                    'reference' => $payment->payment_number,
                    'url' => null,
                    'description' => trim($payment->methodLabel() . ' ' . ($payment->reference_number ? '#' . $payment->reference_number : '')),
                    'debit' => 0.0,
                    'credit' => (float) $payment->amount,
                    'cancelled' => $payment->isCancelled(),
                ));
            });

        $balance = 0.0;

        return $entries
            ->sortBy(fn ($row) => $row['date']?->format('Y-m-d') . '|' . $row['sort_key'])
            ->values()
            ->map(function (array $row) use (&$balance) {
                if (! $row['cancelled']) {
                    $balance = round($balance + $row['debit'] - $row['credit'], 2);
                }
                $row['balance'] = $balance;

                return $row;
            });
    }
}
