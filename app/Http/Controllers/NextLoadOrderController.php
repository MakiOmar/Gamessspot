<?php

namespace App\Http\Controllers;

use App\Models\NextLoadOrder;
use App\Models\NextLoadOrderItem;
use App\Services\NextLoad\NextLoadPurchaseService;
use App\Services\SystemActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Supplier orders: list, details, status refresh, release to stock and logged pin reveal.
 */
class NextLoadOrderController extends Controller
{
    private const PER_PAGE = 25;

    private const STATUSES = array(
        NextLoadOrder::STATUS_PENDING_SEND,
        NextLoadOrder::STATUS_IN_PROGRESS,
        NextLoadOrder::STATUS_COMPLETE,
        NextLoadOrder::STATUS_FAILED,
    );

    public function __construct(
        private readonly NextLoadPurchaseService $purchases,
        private readonly SystemActivityLogger $activityLogger,
    ) {
    }

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;
        $purpose = in_array($request->query('purpose'), array(NextLoadOrder::PURPOSE_SALE, NextLoadOrder::PURPOSE_RESTOCK), true) ? $request->query('purpose') : null;
        $search = trim((string) $request->query('q', ''));

        $orders = NextLoadOrder::query()
            ->with('category:id,name')
            ->withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($purpose, fn ($q) => $q->where('purpose', $purpose))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', '%'.$search.'%')
                ->orWhere('next_load_order_id', $search)
                ->orWhere('storefront_order_id', 'like', '%'.$search.'%')))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('manager.next-load.orders.index', array(
            'orders' => $orders,
            'statuses' => self::STATUSES,
            'filters' => array('status' => $status, 'purpose' => $purpose, 'q' => $search),
        ));
    }

    public function show(NextLoadOrder $nextLoadOrder): View
    {
        $nextLoadOrder->load(array('category:id,name', 'requester:id,name', 'items.card:id,next_load_order_item_id,status'));

        return view('manager.next-load.orders.show', array('order' => $nextLoadOrder));
    }

    public function refresh(NextLoadOrder $nextLoadOrder): JsonResponse
    {
        if (! $nextLoadOrder->isOpen()) {
            return response()->json(array('success' => true, 'message' => 'This order is already '.str_replace('_', ' ', $nextLoadOrder->status).'.'));
        }

        $order = $this->purchases->send($nextLoadOrder);

        return response()->json(array(
            'success' => true,
            'message' => 'Status: '.str_replace('_', ' ', $order->status).($order->last_error ? ' ('.$order->last_error.')' : ''),
        ));
    }

    public function release(NextLoadOrder $nextLoadOrder): JsonResponse
    {
        if (! $nextLoadOrder->isComplete()) {
            return response()->json(array('success' => false, 'message' => 'Only completed orders can be released to stock.'), 422);
        }
        if (! $nextLoadOrder->unconvertedItems()->exists()) {
            return response()->json(array('success' => false, 'message' => 'Every code on this order is already in stock or sold.'), 422);
        }

        $released = $this->purchases->releaseToStock($nextLoadOrder);
        $this->audit('next_load.released_to_stock', $nextLoadOrder, array('count' => $released));

        return response()->json(array('success' => true, 'message' => $released.' code(s) added to stock.'));
    }

    public function reveal(NextLoadOrder $nextLoadOrder, NextLoadOrderItem $item): JsonResponse
    {
        abort_unless((int) $item->next_load_order_id === (int) $nextLoadOrder->id, 404);

        // No audit record, no reveal.
        $this->activityLogger->log('next_load.pin_revealed', 'next_load_order', (int) $nextLoadOrder->id, $nextLoadOrder->reference, array('item_id' => $item->id));

        return response()->json(array('success' => true, 'serial' => $item->serial, 'pin' => $item->pin));
    }

    private function audit(string $action, NextLoadOrder $order, array $meta = array()): void
    {
        try {
            $this->activityLogger->log($action, 'next_load_order', (int) $order->id, $order->reference, $meta);
        } catch (\Throwable) {
            // Audit logging must never block the action.
        }
    }
}
