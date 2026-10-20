<?php

namespace App\Services\NextLoad;

use App\Models\CardCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cached supplier view (balance + product prices) used by listings and stock checks,
 * so public pages never wait on a live Next Load call more than once per cache window.
 */
class NextLoadCatalog
{
    public const BALANCE_CACHE_KEY = 'nextload:balance';

    private const BALANCE_TTL_SECONDS = 900;

    private const OUTAGE_CACHE_KEY = 'nextload:outage';

    private const OUTAGE_TTL_SECONDS = 120;

    /** Upper bound reported as "stock" for supplier-backed categories. */
    public const SUPPLIER_STOCK_CAP = 99;

    public function __construct(private readonly NextLoadClient $client)
    {
    }

    public function balance(bool $fresh = false): ?string
    {
        if (! $fresh) {
            $cached = Cache::get(self::BALANCE_CACHE_KEY);
            if (is_string($cached)) {
                return $cached;
            }
            if (Cache::has(self::OUTAGE_CACHE_KEY)) {
                return null;
            }
        }

        try {
            $balance = $this->client->balance();
        } catch (NextLoadException $e) {
            Log::warning('Next Load balance unavailable', ['reason' => $e->reason]);
            // Listings must not wait on a down supplier for every request.
            Cache::put(self::OUTAGE_CACHE_KEY, true, self::OUTAGE_TTL_SECONDS);

            return null;
        }
        Cache::forget(self::OUTAGE_CACHE_KEY);

        Cache::put(self::BALANCE_CACHE_KEY, $balance, self::BALANCE_TTL_SECONDS);

        return $balance;
    }

    /** @return array{product_id:int,product:string,price:string}|null */
    public function product(int $productId): ?array
    {
        if (Cache::has(self::OUTAGE_CACHE_KEY) && ! Cache::has(NextLoadClient::PRODUCTS_CACHE_KEY)) {
            return null;
        }

        try {
            return $this->client->product($productId);
        } catch (NextLoadException $e) {
            Log::warning('Next Load products unavailable', ['reason' => $e->reason]);
            Cache::put(self::OUTAGE_CACHE_KEY, true, self::OUTAGE_TTL_SECONDS);

            return null;
        }
    }

    /**
     * How many codes Next Load can deliver right now for this category (0 when not supplier-backed).
     */
    public function supplierStock(CardCategory $category): int
    {
        if (! NextLoadClient::enabled() || ! $category->usesSupplier()) {
            return 0;
        }

        $product = $this->product((int) $category->next_load_product_id);
        $balance = $this->balance();
        if ($product === null || $balance === null || ! $this->withinLossGuard($category, $product['price'])) {
            return 0;
        }

        $price = Money::toMillis($product['price']);
        if ($price <= 0) {
            return self::SUPPLIER_STOCK_CAP;
        }

        return (int) min(self::SUPPLIER_STOCK_CAP, intdiv(max(0, Money::toMillis($balance)), $price));
    }

    /** Supplier price converted to local currency (card cost). */
    public function localCost(string $supplierPrice): string
    {
        return Money::toLocal($supplierPrice, (string) config('services.nextload.exchange_rate', '1'));
    }

    public function withinLossGuard(CardCategory $category, string $supplierPrice): bool
    {
        if (config('services.nextload.allow_loss')) {
            return true;
        }

        return Money::compare($this->localCost($supplierPrice), (string) $category->price) <= 0;
    }
}
