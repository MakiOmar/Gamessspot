<?php

namespace App\Services;

use App\Imports\AccountsImport;
use App\Models\Account;
use App\Models\Order;
use App\Models\TraderPurchaseOrder;
use App\Models\TraderPurchaseOrderItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Creates (and removes wrongly imported) accounts against a purchase order line while enforcing the line's remaining quantity.
 */
class PurchaseOrderAccountService
{
    public function __construct(
        private PurchaseOrderService $purchaseOrders,
        private SystemActivityLogger $activityLogger
    ) {
    }

    /**
     * Undo a wrong import: delete an unsold account that came from a purchase order line, freeing that slot.
     * The order total (what the trader is owed) is unchanged; the reason and source go to the activity log.
     */
    public function remove(Account $account, string $reason): void
    {
        $meta = DB::transaction(function () use ($account, $reason) {
            if ($account->purchase_order_id === null) {
                throw ValidationException::withMessages(array('account' => 'This account is not linked to a purchase order.'));
            }

            // Same lock order as imports (order row first) so a concurrent import cannot reuse the slot mid-removal.
            $order = TraderPurchaseOrder::query()->lockForUpdate()->with('trader:id,name')->findOrFail($account->purchase_order_id);
            $account = Account::query()->lockForUpdate()->findOrFail($account->id);

            if (! $account->hasPurchaseSource()) {
                throw ValidationException::withMessages(array('account' => 'This account is not linked to a purchase order.'));
            }

            $sales = Order::query()->where('account_id', $account->id)->count();
            if ($sales > 0) {
                throw ValidationException::withMessages(array(
                    'account' => "This account has {$sales} sale(s) and cannot be removed from purchase order {$order->po_number}.",
                ));
            }

            $meta = array(
                'account_id' => $account->id,
                'reason' => $reason,
                'mail' => $account->mail,
                'game_id' => $account->game_id,
                'trader_id' => $order->trader_id,
                'trader_name' => $order->trader?->name,
                'purchase_order_id' => $order->id,
                'po_number' => $order->po_number,
                'purchase_order_item_id' => $account->purchase_order_item_id,
                'original_cost' => $account->original_cost !== null ? (string) $account->original_cost : null,
            );

            // Unlink first: AccountObserver refuses to delete accounts that still carry a purchase source.
            $account->forceFill(array_fill_keys(Account::SOURCE_FIELDS, null))->saveQuietly();
            $account->delete();

            return $meta;
        });

        CacheManager::invalidateAccounts();
        CacheManager::invalidateGames();

        $this->activityLogger->log(
            'account.removed_from_purchase_order',
            'account',
            (int) $meta['account_id'],
            $meta['mail'] ?: ('Account #' . $meta['account_id']),
            $meta
        );
    }

    /**
     * @param  array<string, mixed>  $attributes  account fields (mail, password, stocks, ...) without source fields
     */
    public function createForLine(int $itemId, array $attributes): Account
    {
        return DB::transaction(function () use ($itemId, $attributes) {
            [$item, $remaining] = $this->purchaseOrders->lockItemForImport($itemId);

            if ($remaining < 1) {
                throw ValidationException::withMessages(array(
                    'purchase_order_item_id' => $this->fullMessage($item),
                ));
            }

            $safe = array_diff_key($attributes, array_flip(array_merge(Account::SOURCE_FIELDS, array('game_id', 'cost'))));

            return Account::create(array_merge($safe, Account::sourceAttributesFromItem($item)));
        });
    }

    /**
     * @return array{imported: int, skipped: int, line_imported: int, line_quantity: int}
     */
    public function importForLine(int $itemId, UploadedFile $file): array
    {
        $rowCount = $this->countRows($file);

        if ($rowCount === 0) {
            throw ValidationException::withMessages(array('file' => 'The file has no account rows.'));
        }

        return DB::transaction(function () use ($itemId, $file, $rowCount) {
            [$item, $remaining] = $this->purchaseOrders->lockItemForImport($itemId);

            if ($rowCount > $remaining) {
                throw ValidationException::withMessages(array(
                    'file' => "The file has {$rowCount} row(s) but this line has only {$remaining} remaining slot(s)"
                        . " ({$item->game?->title}, {$item->purchaseOrder->po_number}).",
                ));
            }

            $before = Account::where('purchase_order_item_id', $item->id)->count();
            $import = new AccountsImport($item);
            Excel::import($import, $file);
            $after = Account::where('purchase_order_item_id', $item->id)->count();

            return array(
                'imported' => $after - $before,
                'skipped' => count($import->errors()),
                'line_imported' => $after,
                'line_quantity' => (int) $item->quantity,
            );
        });
    }

    private function countRows(UploadedFile $file): int
    {
        $sheets = Excel::toArray(new class implements WithHeadingRow {
        }, $file);

        return collect($sheets[0] ?? array())
            ->filter(fn (array $row) => collect($row)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
            ->count();
    }

    private function fullMessage(TraderPurchaseOrderItem $item): string
    {
        return "This purchase order line is full ({$item->quantity} / {$item->quantity} imported). "
            . 'Increase the quantity on the purchase order to add more accounts.';
    }
}
