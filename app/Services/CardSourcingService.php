<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Models\Order;
use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadException;
use App\Services\NextLoad\NextLoadPurchaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sells one gift card code for a category: local stock and/or Next Load depending on the category mode.
 * Response bodies keep the `/api/orders/receive` contract (`code`, `card_details.code`, `order_id`).
 */
class CardSourcingService
{
    public function __construct(private readonly NextLoadPurchaseService $purchases)
    {
    }

    /**
     * @param  array<string, mixed>  $data  validated sellCard input
     * @return array{status:int, body:array<string, mixed>}
     */
    public function sell(array $data, string $lineKey, bool $isStorefront, ?int $sellerId): array
    {
        if ($isStorefront && ($duplicate = $this->duplicateResponse($data, $lineKey))) {
            return $duplicate;
        }

        $category = CardCategory::findOrFail($data['card_category_id']);

        if ($category->usesLocalStock() && ($sold = $this->sellLocal($data, $lineKey, $isStorefront, $sellerId))) {
            return $sold;
        }

        if ($category->usesSupplier() && NextLoadClient::enabled()) {
            return $this->sellFromSupplier($category, $data, $lineKey, $isStorefront, $sellerId);
        }

        return $this->result(422, ['success' => false, 'message' => 'No active code found for this category.']);
    }

    /**
     * @return array{status:int, body:array<string, mixed>}|null null when no local code is left
     */
    private function sellLocal(array $data, string $lineKey, bool $isStorefront, ?int $sellerId): ?array
    {
        return DB::transaction(function () use ($data, $lineKey, $isStorefront, $sellerId) {
            $card = Card::where('card_category_id', $data['card_category_id'])
                ->where('status', true)
                ->lockForUpdate()
                ->first();
            if (! $card) {
                return null;
            }

            $order = Order::create($this->orderData($data, $lineKey, $isStorefront, $sellerId, $card->id));
            $card->update(['status' => false]);

            return $this->soldResponse($card, $order);
        });
    }

    /**
     * @return array{status:int, body:array<string, mixed>}
     */
    private function sellFromSupplier(CardCategory $category, array $data, string $lineKey, bool $isStorefront, ?int $sellerId): array
    {
        try {
            $supplierOrder = $this->purchases->purchaseForSale(
                $category,
                $isStorefront ? (string) $data['storefront_order_id'] : null,
                $lineKey,
                $sellerId,
            );
        } catch (NextLoadException $e) {
            Log::warning('Next Load purchase not started', ['category_id' => $category->id, 'reason' => $e->reason]);

            return $this->result(422, ['success' => false, 'message' => $e->getMessage()]);
        }

        if ($supplierOrder->isComplete()) {
            return $this->sellSupplierItem($supplierOrder, $data, $lineKey, $isStorefront, $sellerId);
        }

        if ($supplierOrder->isOpen()) {
            return $this->result(202, [
                'success' => false,
                'pending' => true,
                'message' => $isStorefront
                    ? 'The supplier is still processing this code. It will be retried automatically.'
                    : 'Next Load is still processing this code. It will be added to stock when delivered.',
                'supplier_reference' => $supplierOrder->reference,
            ]);
        }

        return $this->result(422, [
            'success' => false,
            'message' => 'The supplier could not deliver a code (not charged). Please try again.',
        ]);
    }

    /**
     * @return array{status:int, body:array<string, mixed>}
     */
    private function sellSupplierItem(NextLoadOrder $supplierOrder, array $data, string $lineKey, bool $isStorefront, ?int $sellerId): array
    {
        return DB::transaction(function () use ($supplierOrder, $data, $lineKey, $isStorefront, $sellerId) {
            $locked = NextLoadOrder::whereKey($supplierOrder->id)->lockForUpdate()->firstOrFail();

            // A concurrent retry for the same line may have finished the sale while we waited on the lock.
            if ($isStorefront && ($duplicate = $this->duplicateResponse($data, $lineKey))) {
                return $duplicate;
            }

            $item = $locked->unconvertedItems()->orderBy('id')->first();
            if (! $item) {
                return $this->result(422, ['success' => false, 'message' => 'No supplier code is left on this order.']);
            }

            $card = $this->purchases->createCard($locked, $item, false);
            $order = Order::create($this->orderData($data, $lineKey, $isStorefront, $sellerId, $card->id));
            if (! $locked->unconvertedItems()->exists()) {
                $locked->update(['consumed_at' => now()]);
            }

            return $this->soldResponse($card, $order);
        });
    }

    /**
     * @return array{status:int, body:array<string, mixed>}|null
     */
    private function duplicateResponse(array $data, string $lineKey): ?array
    {
        $existing = Order::where('storefront_order_id', $data['storefront_order_id'])
            ->where('storefront_line_key', $lineKey)
            ->first();
        if (! $existing) {
            return null;
        }

        if (empty($existing->pos_order_id) && ! empty($data['pos_transaction_id'])) {
            $existing->update(['pos_order_id' => (string) $data['pos_transaction_id']]);
            $existing->refresh();
        }
        $code = Card::find($existing->card_id)?->code;

        return $this->result(200, [
            'success' => true,
            'message' => 'Order already allocated.',
            'duplicate' => true,
            'code' => $code,
            'card_details' => ['code' => $code],
            'order_id' => $existing->id,
            'pos_order_id' => $existing->pos_order_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderData(array $data, string $lineKey, bool $isStorefront, ?int $sellerId, int $cardId): array
    {
        $orderData = [
            'seller_id' => $isStorefront ? null : $sellerId,
            'store_profile_id' => $data['store_profile_id'],
            'account_id' => null,
            'buyer_phone' => $data['buyer_phone'],
            'buyer_name' => $data['buyer_name'],
            'price' => $data['price'],
            'notes' => $data['notes'] ?? '',
            'sold_item' => 'card',
            'card_id' => $cardId,
        ];

        if ($isStorefront) {
            $orderData['storefront_order_id'] = $data['storefront_order_id'];
            $orderData['storefront_line_key'] = $lineKey;
            $orderData['pos_order_id'] = (string) $data['pos_transaction_id'];
        } elseif (! empty($data['pos_transaction_id'])) {
            $orderData['pos_order_id'] = (string) $data['pos_transaction_id'];
        }

        return $orderData;
    }

    /**
     * @return array{status:int, body:array<string, mixed>}
     */
    private function soldResponse(Card $card, Order $order): array
    {
        return $this->result(201, [
            'success' => true,
            'message' => 'Order created successfully!',
            'code' => $card->code,
            'card_details' => ['code' => $card->code],
            'order_id' => $order->id,
            'pos_order_id' => $order->pos_order_id,
        ]);
    }

    /**
     * @return array{status:int, body:array<string, mixed>}
     */
    private function result(int $status, array $body): array
    {
        return ['status' => $status, 'body' => $body];
    }
}
