<?php

namespace App\Services\NextLoad;

use App\Models\CardCategory;
use App\Models\NextLoadOrder;
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

    /** @return array{product_id:int,product:string,price:string,in_stock:bool}|null */
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
        if ($product === null || ! self::inStock($product) || $balance === null || ! $this->withinLossGuard($category, $product['price'])) {
            return 0;
        }

        $price = Money::toMillis($product['price']);
        $affordable = $price <= 0 ? self::SUPPLIER_STOCK_CAP : intdiv(max(0, Money::toMillis($balance)), $price);
        $limitLeft = $this->dailyLimitLeft($category);

        return (int) min(self::SUPPLIER_STOCK_CAP, $affordable, $limitLeft ?? PHP_INT_MAX);
    }

    /**
     * Next Load's own stock flag. Price lists cached before the flag was read have no key and count as in stock.
     *
     * @param  array<string, mixed>  $product
     */
    public static function inStock(array $product): bool
    {
        return (bool) ($product['in_stock'] ?? true);
    }

    /**
     * Codes bought from Next Load today for sales of this category (restocks excluded; failed orders were not charged).
     */
    public function purchasedToday(CardCategory $category): int
    {
        return (int) NextLoadOrder::query()
            ->where('card_category_id', $category->id)
            ->where('purpose', NextLoadOrder::PURPOSE_SALE)
            ->where('created_at', '>=', today())
            ->where('status', '!=', NextLoadOrder::STATUS_FAILED)
            ->sum('quantity');
    }

    /** Remaining sale purchases allowed today, or null when the category has no daily limit. */
    public function dailyLimitLeft(CardCategory $category): ?int
    {
        if (! $category->supplier_daily_limit) {
            return null;
        }

        return max(0, (int) $category->supplier_daily_limit - $this->purchasedToday($category));
    }

    /**
     * Lower the cached balance right after a purchase, so listings stop offering codes the balance
     * no longer covers. Best effort: the scheduled nextload:balance replaces it with the real value.
     */
    public function deductBalance(string $amount): void
    {
        $cached = Cache::get(self::BALANCE_CACHE_KEY);
        if (! is_string($cached) || Money::toMillis($amount) <= 0) {
            return;
        }

        $left = max(0, Money::toMillis($cached) - Money::toMillis($amount));
        Cache::put(self::BALANCE_CACHE_KEY, Money::fromMillis($left), self::BALANCE_TTL_SECONDS);
    }

    /** Drop the cached balance (e.g. after a rejected order) so the next check asks Next Load. */
    public function forgetBalance(): void
    {
        Cache::forget(self::BALANCE_CACHE_KEY);
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
