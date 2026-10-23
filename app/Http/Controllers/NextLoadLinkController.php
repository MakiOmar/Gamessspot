<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardArtPreviewRequest;
use App\Http\Requests\NextLoadLinkRequest;
use App\Http\Requests\NextLoadRestockRequest;
use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Services\CardArt\CardArtCatalog;
use App\Services\CardArt\CardArtPresenter;
use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadException;
use App\Services\NextLoad\NextLoadPurchaseService;
use App\Services\SystemActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Card category <-> Next Load product links: create (new page), edit (own page), unlink and restock.
 */
class NextLoadLinkController extends Controller
{
    public function __construct(
        private readonly NextLoadClient $client,
        private readonly SystemActivityLogger $activityLogger,
    ) {
    }

    public function create(Request $request): View
    {
        $productId = (int) $request->query('product_id', 0);

        return view('manager.next-load.links.form', array(
            'category' => null,
            'productId' => $productId ?: null,
            'product' => $productId ? $this->findProduct($productId) : null,
            'products' => $this->safeProducts(),
            'categories' => CardCategory::whereNull('next_load_product_id')->orderBy('name')->get(array('id', 'name', 'price')),
            'modes' => CardCategory::SUPPLIER_MODES,
        ) + $this->artOptions());
    }

    public function store(NextLoadLinkRequest $request): JsonResponse
    {
        $data = $request->validated();
        $product = $this->findProduct((int) $data['next_load_product_id']);
        if ($product === null) {
            return response()->json(array('success' => false, 'message' => 'That product is not in the Next Load price list.'), 422);
        }

        $category = DB::transaction(function () use ($data, $product) {
            $category = ! empty($data['card_category_id'])
                ? CardCategory::lockForUpdate()->findOrFail($data['card_category_id'])
                : CardCategory::create(array('name' => $data['name'], 'price' => $data['price']));

            $category->forceFill($this->linkAttributes($product, $data))->save();

            return $category;
        });
        $this->audit('next_load.link_saved', $category, array('product_id' => $product['product_id'], 'mode' => $data['supplier_mode']));

        return response()->json(array(
            'success' => true,
            'message' => 'Linked "'.$category->name.'" to '.$product['product'].'.',
            'redirect' => route('manager.next-load.links.edit', $category),
        ));
    }

    public function edit(CardCategory $cardCategory): View
    {
        $cardCategory->loadCount(array('cards as available_count' => fn ($q) => $q->where('status', true)));

        return view('manager.next-load.links.form', array(
            'category' => $cardCategory,
            'productId' => $cardCategory->next_load_product_id,
            'product' => $cardCategory->next_load_product_id ? $this->findProduct((int) $cardCategory->next_load_product_id) : null,
            'products' => $this->safeProducts(),
            'categories' => collect(),
            'modes' => CardCategory::SUPPLIER_MODES,
            'recentOrders' => $cardCategory->nextLoadOrders()->latest('id')->limit(10)->get(),
            'maxRestock' => NextLoadRestockRequest::MAX_QUANTITY,
        ) + $this->artOptions());
    }

    public function update(NextLoadLinkRequest $request, CardCategory $cardCategory): JsonResponse
    {
        $data = $request->validated();
        $product = $this->findProduct((int) $data['next_load_product_id']);
        if ($product === null) {
            return response()->json(array('success' => false, 'message' => 'That product is not in the Next Load price list.'), 422);
        }

        $cardCategory->forceFill($this->linkAttributes($product, $data))->save();
        $this->audit('next_load.link_saved', $cardCategory, array('product_id' => $product['product_id'], 'mode' => $data['supplier_mode']));

        return response()->json(array('success' => true, 'message' => 'Supplier settings saved.'));
    }

    public function destroy(CardCategory $cardCategory): JsonResponse
    {
        $cardCategory->forceFill(array(
            'next_load_product_id' => null,
            'supplier_mode' => CardCategory::MODE_LOCAL_ONLY,
            'supplier_cost' => null,
            'supplier_synced_at' => null,
            'supplier_product_name' => null,
        ))->save();
        $this->audit('next_load.link_removed', $cardCategory);

        return response()->json(array(
            'success' => true,
            'message' => '"'.$cardCategory->name.'" now sells local codes only.',
            'redirect' => route('manager.next-load.products'),
        ));
    }

    public function artPreview(CardArtPreviewRequest $request, CardArtPresenter $presenter): JsonResponse
    {
        $data = $request->validated();
        $art = $presenter->build($data['name'], $data['art_brand'] ?? null, $data['art_country'] ?? null, $data['art_value'] ?? null);

        return response()->json(array(
            'success' => true,
            'card_art' => $art,
            'html' => $art ? Blade::render('<x-card-art :art="$art" />', array('art' => $art)) : null,
        ));
    }

    public function restock(NextLoadRestockRequest $request, CardCategory $cardCategory, NextLoadPurchaseService $purchases): JsonResponse
    {
        try {
            $order = $purchases->restock($cardCategory, (int) $request->validated('quantity'), (int) $request->user('admin')->id);
        } catch (NextLoadException $e) {
            return response()->json(array('success' => false, 'message' => $e->getMessage()), 422);
        }

        $message = match ($order->status) {
            NextLoadOrder::STATUS_COMPLETE => $order->delivered_quantity.' code(s) added to stock.',
            NextLoadOrder::STATUS_FAILED => 'Next Load could not fulfil the order (not charged).',
            default => 'Next Load is processing the order; codes are added to stock when delivered.',
        };

        return response()->json(array(
            'success' => ! $order->isFailed(),
            'message' => $message,
            'redirect' => route('manager.next-load.orders.show', $order),
        ), $order->isFailed() ? 422 : 200);
    }

    /**
     * Brand and country choices for the artwork override selects.
     */
    private function artOptions(): array
    {
        return array(
            'artBrands' => array_map(fn (array $brand) => $brand['label'], CardArtCatalog::BRANDS),
            'artCountries' => CardArtCatalog::COUNTRIES,
        );
    }

    /**
     * @param  array{product_id:int,product:string,price:string}  $product
     */
    private function linkAttributes(array $product, array $data): array
    {
        return array(
            'next_load_product_id' => $product['product_id'],
            'supplier_mode' => $data['supplier_mode'],
            'supplier_cost' => $product['price'],
            'supplier_synced_at' => now(),
            'supplier_product_name' => $product['product'],
            'art_brand' => $data['art_brand'] ?? null,
            'art_country' => $data['art_country'] ?? null,
            'art_value' => filled($data['art_value'] ?? null) ? trim($data['art_value']) : null,
        );
    }

    /**
     * @return array{product_id:int,product:string,price:string}|null
     */
    private function findProduct(int $productId): ?array
    {
        try {
            return $this->client->product($productId);
        } catch (NextLoadException) {
            return null;
        }
    }

    /**
     * @return list<array{product_id:int,product:string,price:string}>
     */
    private function safeProducts(): array
    {
        try {
            return $this->client->products();
        } catch (NextLoadException) {
            return array();
        }
    }

    private function audit(string $action, CardCategory $category, array $meta = array()): void
    {
        try {
            $this->activityLogger->log($action, 'card_category', (int) $category->id, $category->name, $meta);
        } catch (\Throwable) {
            // Audit logging must never block a settings save.
        }
    }
}
