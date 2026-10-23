<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Services\NextLoad\NextLoadCatalog;
use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadException;
use App\Services\NextLoad\NextLoadSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Next Load supplier overview and product catalogue (manage-next-load).
 */
class NextLoadController extends Controller
{
    private const PRODUCTS_PER_PAGE = 50;

    public function index(NextLoadCatalog $catalog): View
    {
        $configured = NextLoadClient::configured();

        return view('manager.next-load.index', array(
            'enabled' => NextLoadClient::enabled(),
            'configured' => $configured,
            'baseUrl' => (string) config('services.nextload.base_url'),
            'balance' => $configured ? $catalog->balance() : null,
            'lowBalance' => (string) config('services.nextload.low_balance', '0'),
            'exchangeRate' => (string) config('services.nextload.exchange_rate', '1'),
            'lastProductSync' => Cache::get(NextLoadSyncService::LAST_PRODUCT_SYNC_KEY),
            'linkedCount' => CardCategory::whereNotNull('next_load_product_id')->count(),
            'linked' => $configured ? $this->linkedRows($catalog) : collect(),
            'statusCounts' => NextLoadOrder::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'recentOrders' => NextLoadOrder::with('category:id,name')->latest('id')->limit(10)->get(),
        ));
    }

    /**
     * Linked categories with local stock, today's supplier purchases and what the shared balance can still buy.
     */
    private function linkedRows(NextLoadCatalog $catalog)
    {
        return CardCategory::whereNotNull('next_load_product_id')
            ->withAvailableCount()
            ->orderBy('name')
            ->get()
            ->map(fn (CardCategory $category) => array(
                'category' => $category,
                'purchased_today' => $catalog->purchasedToday($category),
                'can_buy' => $catalog->supplierStock($category),
            ));
    }

    public function test(NextLoadCatalog $catalog): JsonResponse
    {
        if (! NextLoadClient::configured()) {
            return response()->json(array('success' => false, 'message' => 'Next Load credentials are not configured.'), 422);
        }

        $balance = $catalog->balance(true);
        if ($balance === null) {
            return response()->json(array('success' => false, 'message' => 'Could not reach Next Load. Check the credentials and the logs.'), 502);
        }

        return response()->json(array('success' => true, 'message' => 'Connected. Balance: '.$balance, 'balance' => $balance));
    }

    public function syncProducts(NextLoadSyncService $sync): JsonResponse
    {
        try {
            $result = $sync->syncProducts();
        } catch (NextLoadException $e) {
            return response()->json(array('success' => false, 'message' => $e->getMessage()), 502);
        }

        $message = 'Price list refreshed; '.$result['updated'].' linked categor'.($result['updated'] === 1 ? 'y' : 'ies').' updated.';
        if ($result['missing'] !== array()) {
            $message .= ' '.count($result['missing']).' linked product(s) are no longer listed.';
        }

        return response()->json(array('success' => true, 'message' => $message));
    }

    public function products(Request $request, NextLoadClient $client): View
    {
        $search = trim((string) $request->query('q', ''));
        $error = null;
        $products = array();

        try {
            $products = $client->products();
        } catch (NextLoadException $e) {
            $error = $e->getMessage();
        }

        $filtered = collect($products)
            ->when($search !== '', fn ($rows) => $rows->filter(
                fn ($row) => str_contains(mb_strtolower($row['product']), mb_strtolower($search)) || (string) $row['product_id'] === $search
            ))
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $filtered->forPage($page, self::PRODUCTS_PER_PAGE)->values(),
            $filtered->count(),
            self::PRODUCTS_PER_PAGE,
            $page,
            array('path' => $request->url(), 'query' => $request->query())
        );

        return view('manager.next-load.products', array(
            'products' => $paginator,
            'search' => $search,
            'error' => $error,
            'links' => CardCategory::whereNotNull('next_load_product_id')->get(array('id', 'name', 'next_load_product_id', 'supplier_mode'))->groupBy('next_load_product_id'),
            'exchangeRate' => (string) config('services.nextload.exchange_rate', '1'),
        ));
    }
}
