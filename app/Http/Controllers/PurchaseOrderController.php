<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Requests\VoidTraderTransactionRequest;
use App\Models\Game;
use App\Models\Trader;
use App\Models\TraderPurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderService $purchaseOrders)
    {
    }

    public function create(Request $request)
    {
        return view('manager.purchase_orders.form', array(
            'order' => null,
            'lines' => collect(),
            'selectedTraderId' => (int) $request->query('trader') ?: null,
            'traders' => Trader::active()->orderBy('name')->get(array('id', 'name')),
            'games' => Game::orderBy('title')->get(array('id', 'title')),
        ));
    }

    public function store(PurchaseOrderRequest $request): JsonResponse
    {
        $order = $this->purchaseOrders->create($request->validated(), $request->user('admin')?->id);

        return response()->json(array(
            'message' => "Purchase order {$order->po_number} created.",
            'redirect' => route('manager.purchase-orders.show', $order),
        ));
    }

    public function show(Request $request, TraderPurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(array(
            'trader:id,name',
            'creator:id,name',
            'updater:id,name',
            'canceller:id,name',
            'items' => fn ($q) => $q->withCount('accounts')->with('game:id,title'),
        ));

        $accounts = $purchaseOrder->accounts()
            ->with('game:id,title')
            ->when($request->filled('email'), function ($q) use ($request) {
                $q->where('mail', 'like', '%' . str_replace(array('%', '_'), array('\\%', '\\_'), (string) $request->input('email')) . '%');
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('manager.purchase_orders.show', array(
            'order' => $purchaseOrder,
            'accounts' => $accounts,
            'importedTotal' => (int) $purchaseOrder->items->sum('accounts_count'),
        ));
    }

    public function edit(TraderPurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->isCancelled()) {
            return redirect()->route('manager.purchase-orders.show', $purchaseOrder);
        }

        $purchaseOrder->load(array('items' => fn ($q) => $q->withCount('accounts')));

        return view('manager.purchase_orders.form', array(
            'order' => $purchaseOrder,
            'lines' => $purchaseOrder->items->map(fn ($line) => array(
                'id' => $line->id,
                'game_id' => $line->game_id,
                'quantity' => $line->quantity,
                'cost_per_account' => (string) $line->cost_per_account,
                'imported' => (int) $line->accounts_count,
            ))->values(),
            'selectedTraderId' => $purchaseOrder->trader_id,
            'traders' => Trader::query()
                ->where(fn ($q) => $q->active()->orWhere('id', $purchaseOrder->trader_id))
                ->orderBy('name')
                ->get(array('id', 'name')),
            'games' => Game::orderBy('title')->get(array('id', 'title')),
        ));
    }

    public function update(PurchaseOrderRequest $request, TraderPurchaseOrder $purchaseOrder): JsonResponse
    {
        $order = $this->purchaseOrders->update($purchaseOrder, $request->validated(), $request->user('admin')?->id);

        return response()->json(array(
            'message' => "Purchase order {$order->po_number} updated.",
            'redirect' => route('manager.purchase-orders.show', $order),
        ));
    }

    public function void(VoidTraderTransactionRequest $request, TraderPurchaseOrder $purchaseOrder): JsonResponse
    {
        $order = $this->purchaseOrders->void($purchaseOrder, $request->validated('reason'), $request->user('admin')?->id);

        return response()->json(array('message' => "Purchase order {$order->po_number} voided."));
    }
}
