<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForceDeleteTraderRequest;
use App\Http\Requests\TraderRequest;
use App\Http\Requests\UpdateTraderOpeningBalanceRequest;
use App\Models\Trader;
use App\Models\TraderPayment;
use App\Services\PurchaseOrderService;
use App\Services\TraderForceDeleteService;
use App\Services\TraderLedgerService;
use App\Services\TraderProfileService;
use App\Services\TraderService;
use App\Support\LikePattern;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TraderController extends Controller
{
    public function __construct(
        private TraderService $traderService,
        private TraderLedgerService $ledger,
        private TraderProfileService $profile,
        private TraderForceDeleteService $forceDeleteService
    ) {
    }

    public function index(Request $request)
    {
        $query = $this->ledger->withTotals(Trader::query());

        if ($request->filled('search')) {
            $term = LikePattern::contains((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('whatsapp', 'like', $term);
            });
        }

        if (in_array($request->input('status'), Trader::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        $traders = $query->orderBy('name')->paginate(20)->withQueryString();
        $ledgerTotals = $traders->getCollection()
            ->mapWithKeys(fn (Trader $trader) => array($trader->id => $this->ledger->totalsFromAggregates($trader)));

        return view('manager.traders.index', compact('traders', 'ledgerTotals'));
    }

    public function show(Request $request, Trader $trader)
    {
        $tab = $this->profile->resolveTab($request->query('tab'));
        $trader->load(array('creator:id,name', 'updater:id,name'));

        $data = array(
            'trader' => $trader,
            'tab' => $tab,
            'totals' => $this->ledger->totals($trader),
            'paymentMethods' => TraderPayment::METHODS,
        );

        if ($tab === 'purchase-orders') {
            $data['purchaseOrders'] = $this->profile->purchaseOrders($trader);
        } elseif ($tab === 'accounts') {
            $data['accounts'] = $this->profile->accounts($trader, $request);
            $data['filterOptions'] = $this->profile->accountFilterOptions($trader);
        } elseif ($tab === 'payments') {
            $data['payments'] = $this->profile->payments($trader);
        } elseif ($tab === 'statement') {
            $data['statement'] = $this->ledger->statement($trader);
        }

        return view('manager.traders.show', $data);
    }

    public function store(TraderRequest $request): JsonResponse
    {
        $trader = $this->traderService->create($request->validated(), $request->user('admin')?->id);

        return response()->json(array(
            'message' => 'Trader created successfully.',
            'trader' => $trader,
            'redirect' => route('manager.traders.show', $trader),
        ));
    }

    public function update(TraderRequest $request, Trader $trader): JsonResponse
    {
        $trader = $this->traderService->update($trader, $request->validated(), $request->user('admin')?->id);

        return response()->json(array('message' => 'Trader updated successfully.', 'trader' => $trader));
    }

    public function destroy(Request $request, Trader $trader): JsonResponse
    {
        $blockers = $this->traderService->delete($trader);

        if ($blockers !== array()) {
            $response = array(
                'message' => 'Trader "' . $trader->name . '" cannot be deleted.',
                'reasons' => $blockers,
            );

            if ($request->user('admin')?->can('void-trader-transactions')) {
                $response['force_delete'] = array(
                    'url' => route('manager.traders.force-destroy', $trader),
                    'summary' => $this->forceDeleteService->summary($trader),
                );
            }

            return response()->json($response, 422);
        }

        return response()->json(array(
            'message' => 'Trader deleted.',
            'redirect' => route('manager.traders.index'),
        ));
    }

    public function forceDestroy(ForceDeleteTraderRequest $request, Trader $trader): JsonResponse
    {
        $this->forceDeleteService->forceDelete($trader, $request->validated('reason'), $request->user('admin')?->id);

        return response()->json(array(
            'message' => 'Trader "' . $trader->name . '" and all related data were deleted.',
            'redirect' => route('manager.traders.index'),
        ));
    }

    public function updateOpeningBalance(UpdateTraderOpeningBalanceRequest $request, Trader $trader): JsonResponse
    {
        $this->traderService->updateOpeningBalance(
            $trader,
            (float) $request->validated('opening_balance'),
            $request->validated('opening_balance_date'),
            $request->user('admin')?->id
        );

        return response()->json(array('message' => 'Opening balance updated.'));
    }

    /**
     * Active purchase orders and their game lines, used by the account add / import forms.
     */
    public function purchaseOrderLines(Trader $trader, PurchaseOrderService $purchaseOrders): JsonResponse
    {
        abort_unless(Gate::any(array('view-traders', 'manage-accounts')), 403);

        return response()->json(array(
            'trader' => array('id' => $trader->id, 'name' => $trader->name),
            'purchase_orders' => $purchaseOrders->linesForTrader($trader),
        ));
    }
}
