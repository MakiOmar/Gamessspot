<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Centralized Cache Manager
 * 
 * Handles all caching operations with support for Redis, Memcached, and File drivers.
 * Groups ("games:", "orders:", ...) are invalidated by bumping a per-group version
 * counter that is part of every physical key, instead of tags or a shared key list.
 * A bump is atomic, and a value computed from pre-invalidation data is written under
 * the old version, which no reader uses any more.
 */
class CacheManager
{
    /**
     * Cache key prefixes for organization
     */
    const PREFIX_DASHBOARD = 'dashboard:';
    const PREFIX_ORDERS = 'orders:';
    const PREFIX_USERS = 'users:';
    const PREFIX_ACCOUNTS = 'accounts:';
    const PREFIX_CARDS = 'cards:';
    const PREFIX_GAMES = 'games:';
    const PREFIX_DEVICES = 'devices:';
    
    /**
     * Default cache TTL in seconds
     */
    const TTL_SHORT = 60;       // 1 minute
    const TTL_MEDIUM = 300;     // 5 minutes
    const TTL_LONG = 600;       // 10 minutes
    const TTL_VERY_LONG = 3600; // 1 hour
    
    /** Group prefixes (without the trailing colon) that are invalidated as a whole. */
    const GROUPS = ['dashboard', 'orders', 'users', 'accounts', 'cards', 'games', 'devices'];

    /** Holds the current version number of each group. */
    const VERSION_KEY_PREFIX = 'cache:version:';

    /** Keys that the cache indicator's "clear" button may target. */
    const CLEARABLE_LISTING_PREFIXES = ['users:list:', 'games:list:', 'accounts:list:', 'orders:list:', 'cards:list:'];

    /** Hit/miss info for the current request, read back by the cache indicator. */
    private const REQUEST_METADATA_LIMIT = 50;

    /** @var array<string, array<string, mixed>> */
    private static array $requestMetadata = [];
    
    /**
     * Remember a value in cache
     *
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        try {
            $physicalKey = self::physicalKey($key);
            $startTime = microtime(true);

            // Single read: has() + get() could straddle an expiry and return null as a "hit".
            $result = Cache::get($physicalKey);

            if ($result !== null) {
                self::storeCacheMetadata($key, true, (microtime(true) - $startTime) * 1000);

                return $result;
            }

            $callbackStartTime = microtime(true);
            $result = $callback();
            $callbackTime = (microtime(true) - $callbackStartTime) * 1000;

            Cache::put($physicalKey, $result, $ttl);
            self::storeCacheMetadata($key, false, (microtime(true) - $startTime) * 1000, $callbackTime);

            return $result;
        } catch (\Exception $e) {
            // If cache fails, execute callback directly
            return $callback();
        }
    }
    
    /**
     * Record hit/miss info for this request (no cache write on every hit).
     *
     * @param string $key
     * @param bool $wasHit
     * @param float $executionTime Execution time in milliseconds
     * @param float|null $queryTime Query/callback execution time in milliseconds (for cache miss)
     * @return void
     */
    protected static function storeCacheMetadata(string $key, bool $wasHit, float $executionTime = 0, ?float $queryTime = null): void
    {
        if (count(self::$requestMetadata) >= self::REQUEST_METADATA_LIMIT) {
            self::$requestMetadata = [];
        }

        self::$requestMetadata[$key] = [
            'was_hit' => $wasHit,
            'timestamp' => now()->timestamp,
            'datetime' => now()->toDateTimeString(),
            'execution_time_ms' => round($executionTime, 2),
            'query_time_ms' => $queryTime !== null ? round($queryTime, 2) : null,
            'cache_saving_ms' => $queryTime !== null ? round($queryTime - $executionTime, 2) : null,
        ];
    }
    
    /**
     * Get cache metadata for a key read earlier in this request
     *
     * @param string $key
     * @return array|null
     */
    public static function getCacheMetadata(string $key): ?array
    {
        return self::$requestMetadata[$key] ?? null;
    }

    /** True for listing keys the cache indicator may clear (never dashboard totals or versions). */
    public static function isClearableListingKey(string $key): bool
    {
        foreach (self::CLEARABLE_LISTING_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Group a logical key belongs to ("games:list:..." → "games"), or null. */
    protected static function groupOf(string $key): ?string
    {
        $group = strstr($key, ':', true);

        return $group !== false && in_array($group, self::GROUPS, true) ? $group : null;
    }

    /** Physical cache key: "games:list:page_1" → "games:v7:list:page_1". */
    protected static function physicalKey(string $key): string
    {
        $group = self::groupOf($key);
        if ($group === null) {
            return $key;
        }

        return $group.':v'.self::groupVersion($group).substr($key, strlen($group));
    }

    protected static function groupVersion(string $group): int
    {
        return (int) Cache::get(self::VERSION_KEY_PREFIX.$group, 0);
    }

    /** Atomically retire every key in a group. */
    protected static function bumpGroup(string $group): void
    {
        $versionKey = self::VERSION_KEY_PREFIX.$group;
        Cache::add($versionKey, 0);
        Cache::increment($versionKey);
    }

    /**
     * Run an invalidation once the surrounding transaction commits (immediately when none is open),
     * so a concurrent request cannot re-cache rows the transaction has not committed yet.
     */
    protected static function afterCommit(callable $invalidate): void
    {
        DB::afterCommit($invalidate);
    }
    
    /**
     * Check if a key was served from cache (cache hit)
     *
     * @param string $key
     * @return bool
     */
    public static function wasCacheHit(string $key): bool
    {
        $metadata = self::getCacheMetadata($key);
        return $metadata['was_hit'] ?? false;
    }
    
    /**
     * Get a value from cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        try {
            return Cache::get(self::physicalKey($key), $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
    
    /**
     * Put a value in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public static function put(string $key, $value, int $ttl): bool
    {
        try {
            return Cache::put(self::physicalKey($key), $value, $ttl);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Forget a single cache key
     *
     * @param string $key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        try {
            unset(self::$requestMetadata[$key]);

            return Cache::forget(self::physicalKey($key));
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Invalidate a whole group, e.g. "games:*". Only group-wide patterns are supported.
     *
     * @param string $pattern
     * @return int Number of groups invalidated
     */
    public static function forgetByPattern(string $pattern): int
    {
        $group = rtrim($pattern, ':*');
        if (! in_array($group, self::GROUPS, true) || $pattern !== $group.':*') {
            return 0;
        }

        try {
            self::bumpGroup($group);

            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    // ====================================================================
    // DASHBOARD CACHE METHODS
    // ====================================================================
    
    public static function getDashboardStats()
    {
        return [
            'total_code_cost' => self::getTotalCodeCost(),
            'total_account_cost' => self::getTotalAccountCost(),
            'never_sold_account_cost' => self::getNeverSoldAccountCost(),
            'in_stock_account_cost' => self::getInStockAccountCost(),
            'total_user_count' => self::getTotalUserCount(),
            'today_order_count' => self::getTodayOrderCount(),
            'unique_buyer_count' => self::getUniqueBuyerCount(),
            'device_repair_stats' => self::getDeviceRepairStats(),
            'new_users_count' => self::getNewUsersCount(),
        ];
    }
    
    public static function getTotalCodeCost()
    {
        return self::remember(
            self::PREFIX_CARDS . 'total_code_cost',
            self::TTL_LONG,
            fn() => \App\Models\Card::sum('cost')
        );
    }
    
    public static function getTotalAccountCost()
    {
        return self::remember(
            self::PREFIX_ACCOUNTS . 'total_account_cost',
            self::TTL_LONG,
            fn() => \App\Models\Account::sum('cost')
        );
    }

    /** Cost of accounts with no order at all. Sales and undos bump the "accounts" group (OrderObserver). */
    public static function getNeverSoldAccountCost()
    {
        return self::remember(
            self::PREFIX_ACCOUNTS . 'never_sold_account_cost',
            self::TTL_LONG,
            fn() => \App\Models\Account::neverSold()->sum('cost')
        );
    }

    /** Full cost of accounts that still have any slot to sell (partially sold included). */
    public static function getInStockAccountCost()
    {
        return self::remember(
            self::PREFIX_ACCOUNTS . 'in_stock_account_cost',
            self::TTL_LONG,
            fn() => \App\Models\Account::withStockLeft()->sum('cost')
        );
    }
    
    public static function getTotalUserCount()
    {
        return self::remember(
            self::PREFIX_USERS . 'total_user_count',
            self::TTL_LONG,
            fn() => \App\Models\User::count()
        );
    }
    
    public static function getTodayOrderCount()
    {
        return self::remember(
            self::todayOrderCountKey(),
            self::TTL_MEDIUM,
            fn() => \App\Models\Order::whereDate('created_at', now()->toDateString())->count()
        );
    }

    /** Date-scoped so yesterday's count is never served after midnight. */
    protected static function todayOrderCountKey(): string
    {
        return self::PREFIX_DASHBOARD . 'today_order_count:' . now()->toDateString();
    }
    
    public static function getUniqueBuyerCount()
    {
        return self::remember(
            self::PREFIX_ORDERS . 'unique_buyer_phone_count',
            self::TTL_LONG,
            fn() => \App\Models\Order::distinct('buyer_phone')->count('buyer_phone')
        );
    }
    
    public static function getDeviceRepairStats()
    {
        return self::remember(
            self::PREFIX_DEVICES . 'repair_stats',
            self::TTL_MEDIUM,
            function () {
                return [
                    'total_repairs' => \App\Models\DeviceRepair::count(),
                    'active_repairs' => \App\Models\DeviceRepair::active()->count(),
                    'delivered_today' => \App\Models\DeviceRepair::where('status', 'delivered')
                        ->whereDate('status_updated_at', today())
                        ->count(),
                    'processing_repairs' => \App\Models\DeviceRepair::where('status', 'processing')->count()
                ];
            }
        );
    }
    
    public static function getNewUsersCount()
    {
        return self::remember(
            self::PREFIX_USERS . 'new_users_role_5_count',
            self::TTL_LONG,
            function () {
                return \App\Models\User::whereHas('roles', function ($query) {
                    $query->where('role_id', 5);
                })
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
            }
        );
    }

    /**
     * Top 5 games by order count (dashboard widget).
     * Aggregates by game_id first to avoid scanning full game columns in GROUP BY.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getTopSellingGames()
    {
        return self::remember(
            self::PREFIX_DASHBOARD . 'top_selling_games',
            self::TTL_MEDIUM,
            function () {
                return collect(DB::select(
                    'SELECT g.id, g.title, g.code, g.ps4_image_url, g.ps5_image_url, s.total_sales
                     FROM (
                         SELECT a.game_id, COUNT(*) AS total_sales
                         FROM orders o
                         INNER JOIN accounts a ON o.account_id = a.id
                         WHERE o.account_id IS NOT NULL
                         GROUP BY a.game_id
                         ORDER BY total_sales DESC
                         LIMIT 5
                     ) s
                     INNER JOIN games g ON g.id = s.game_id
                     ORDER BY s.total_sales DESC'
                ));
            }
        );
    }

    /**
     * Top 5 buyers by order count (dashboard widget).
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getTopBuyers()
    {
        return self::remember(
            self::PREFIX_DASHBOARD . 'top_buyers',
            self::TTL_MEDIUM,
            function () {
                return \App\Models\Order::query()
                    ->select('buyer_phone', 'buyer_name', DB::raw('COUNT(*) as total_orders'))
                    ->groupBy('buyer_phone', 'buyer_name')
                    ->orderByDesc('total_orders')
                    ->limit(5)
                    ->get();
            }
        );
    }

    /**
     * Top 3 stores by revenue (dashboard widget).
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getTopSellingStores()
    {
        return self::remember(
            self::PREFIX_DASHBOARD . 'top_selling_stores',
            self::TTL_MEDIUM,
            function () {
                return \App\Models\StoresProfile::query()
                    ->leftJoin('users', 'stores_profile.id', '=', 'users.store_profile_id')
                    ->leftJoin('orders', 'users.id', '=', 'orders.seller_id')
                    ->select(
                        'stores_profile.*',
                        DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                        DB::raw('COALESCE(SUM(orders.price), 0) as orders_sum_price')
                    )
                    ->groupBy(
                        'stores_profile.id',
                        'stores_profile.name',
                        'stores_profile.phone_number',
                        'stores_profile.created_at',
                        'stores_profile.updated_at'
                    )
                    ->having('orders_sum_price', '>', 0)
                    ->orderByDesc('orders_sum_price')
                    ->limit(3)
                    ->get();
            }
        );
    }
    
    // ====================================================================
    // USER LISTING CACHE METHODS
    // ====================================================================
    
    /**
     * Cache user listing with pagination and role filter
     *
     * @param string $role Role filter ('any' or role ID)
     * @param int $page Page number
     * @param callable $callback Query callback
     * @return mixed
     */
    public static function getUserListing(string $role, int $page, callable $callback)
    {
        $cacheKey = self::getUserListingKey($role, $page);
        
        return self::remember($cacheKey, self::TTL_SHORT, $callback);
    }
    
    /**
     * Get cache key for user listing
     *
     * @param string $role
     * @param int $page
     * @return string
     */
    public static function getUserListingKey(string $role, int $page): string
    {
        return self::PREFIX_USERS . "list:role_{$role}:page_{$page}";
    }
    
    // ====================================================================
    // GAME LISTING CACHE METHODS
    // ====================================================================
    
    /**
     * Cache game listing with pagination and platform filter
     *
     * @param string $platform Platform filter ('all', 'ps4', 'ps5')
     * @param int $page Page number
     * @param callable $callback Query callback
     * @param int|null $storeProfileId Optional store profile ID for store-specific caching
     * @return mixed
     */
    public static function getGameListing(string $platform, int $page, callable $callback, ?int $storeProfileId = null)
    {
        $cacheKey = self::getGameListingKey($platform, $page, $storeProfileId);
        
        return self::remember($cacheKey, self::TTL_SHORT, $callback);
    }
    
    /**
     * Get cache key for game listing
     *
     * @param string $platform
     * @param int $page
     * @param int|null $storeProfileId Optional store profile ID for store-specific caching
     * @return string
     */
    public static function getGameListingKey(string $platform, int $page, ?int $storeProfileId = null): string
    {
        if ($storeProfileId !== null) {
            // Store-specific cache key
            return self::PREFIX_GAMES . "list:platform_{$platform}:store_{$storeProfileId}:page_{$page}";
        }
        
        // Global cache key (for admin view without store-specific prices)
        return self::PREFIX_GAMES . "list:platform_{$platform}:page_{$page}";
    }
    
    // ====================================================================
    // ACCOUNT LISTING CACHE METHODS
    // ====================================================================
    
    /**
     * Cache account listing with pagination
     *
     * @param int $page Page number
     * @param callable $callback Query callback
     * @return mixed
     */
    public static function getAccountListing(int $page, callable $callback)
    {
        $cacheKey = self::getAccountListingKey($page);
        
        return self::remember($cacheKey, self::TTL_SHORT, $callback);
    }
    
    /**
     * Get cache key for account listing
     *
     * @param int $page
     * @return string
     */
    public static function getAccountListingKey(int $page): string
    {
        return self::PREFIX_ACCOUNTS . "list:page_{$page}";
    }
    
    // ====================================================================
    // ORDER LISTING CACHE METHODS
    // ====================================================================
    
    /**
     * Cache order listing with pagination and filter
     * 
     * IMPORTANT: Orders are user-specific or role-specific!
     * - Admins see all orders
     * - Sales/Account managers see only their orders
     * - Accountants see only their store's orders
     *
     * @param string $filter Filter type ('all', 'has_problem', 'needs_return', 'solved', 'today')
     * @param int $page Page number
     * @param callable $callback Query callback
     * @param int|null $userId User ID for user-specific caching (sales/account manager)
     * @param int|null $storeProfileId Store ID for store-specific caching (accountant)
     * @param bool $isAdmin Whether user is admin (sees all orders)
     * @return mixed
     */
    public static function getOrderListing(string $filter, int $page, callable $callback, ?int $userId = null, ?int $storeProfileId = null, bool $isAdmin = false)
    {
        $cacheKey = self::getOrderListingKey($filter, $page, $userId, $storeProfileId, $isAdmin);
        
        return self::remember($cacheKey, self::TTL_SHORT, $callback);
    }
    
    /**
     * Get cache key for order listing
     *
     * @param string $filter
     * @param int $page
     * @param int|null $userId
     * @param int|null $storeProfileId
     * @param bool $isAdmin
     * @return string
     */
    public static function getOrderListingKey(string $filter, int $page, ?int $userId = null, ?int $storeProfileId = null, bool $isAdmin = false): string
    {
        if ($isAdmin && $storeProfileId !== null) {
            // Admin filtered by specific store
            return self::PREFIX_ORDERS . "list:filter_{$filter}:store_{$storeProfileId}:page_{$page}";
        } elseif ($isAdmin) {
            // Admin viewing all orders
            return self::PREFIX_ORDERS . "list:filter_{$filter}:admin:page_{$page}";
        } elseif ($userId !== null) {
            // User-specific orders (sales/account manager)
            return self::PREFIX_ORDERS . "list:filter_{$filter}:user_{$userId}:page_{$page}";
        } elseif ($storeProfileId !== null) {
            // Store-specific orders (accountant)
            return self::PREFIX_ORDERS . "list:filter_{$filter}:store_{$storeProfileId}:page_{$page}";
        }
        
        // Fallback (shouldn't happen)
        return self::PREFIX_ORDERS . "list:filter_{$filter}:page_{$page}";
    }
    
    // ====================================================================
    // CARD LISTING CACHE METHODS
    // ====================================================================
    
    /**
     * Cache card listing with pagination
     *
     * @param int $page Page number
     * @param callable $callback Query callback
     * @return mixed
     */
    public static function getCardListing(int $page, callable $callback)
    {
        $cacheKey = self::PREFIX_CARDS . "list:page_{$page}";
        
        return self::remember($cacheKey, self::TTL_SHORT, $callback);
    }
    
    // ====================================================================
    // CACHE INVALIDATION METHODS
    // ====================================================================
    
    // Every invalidate* method defers to after commit and returns the number of groups retired.

    /**
     * Invalidate all dashboard caches
     */
    public static function invalidateDashboard(): int
    {
        self::afterCommit(function () {
            self::forgetByPattern(self::PREFIX_DASHBOARD . '*');
            self::forget(self::PREFIX_ORDERS . 'unique_buyer_phone_count');
            self::forget(self::PREFIX_DEVICES . 'repair_stats');
            self::forget(self::PREFIX_USERS . 'new_users_role_5_count');
        });

        return 1;
    }
    
    /**
     * Invalidate order-related caches (plus the dashboard widgets that aggregate orders)
     */
    public static function invalidateOrders(): int
    {
        self::afterCommit(function () {
            self::forgetByPattern(self::PREFIX_ORDERS . '*');
            self::forget(self::todayOrderCountKey());
            self::forget(self::PREFIX_DASHBOARD . 'top_selling_games');
            self::forget(self::PREFIX_DASHBOARD . 'top_buyers');
            self::forget(self::PREFIX_DASHBOARD . 'top_selling_stores');
        });

        return 1;
    }
    
    /**
     * Invalidate user-related caches
     */
    public static function invalidateUsers(): int
    {
        self::afterCommit(fn () => self::forgetByPattern(self::PREFIX_USERS . '*'));

        return 1;
    }
    
    /**
     * Invalidate account-related caches
     */
    public static function invalidateAccounts(): int
    {
        self::afterCommit(fn () => self::forgetByPattern(self::PREFIX_ACCOUNTS . '*'));

        return 1;
    }
    
    /**
     * Invalidate card-related caches
     */
    public static function invalidateCards(): int
    {
        self::afterCommit(fn () => self::forgetByPattern(self::PREFIX_CARDS . '*'));

        return 1;
    }
    
    /**
     * Invalidate game-related caches (listings embed per-game account stock and prices)
     */
    public static function invalidateGames(): int
    {
        self::afterCommit(fn () => self::forgetByPattern(self::PREFIX_GAMES . '*'));

        return 1;
    }
    
    /**
     * Invalidate device repair caches
     */
    public static function invalidateDeviceRepairs(): int
    {
        self::afterCommit(fn () => self::forgetByPattern(self::PREFIX_DEVICES . '*'));

        return 1;
    }
    
    /**
     * Clear all application caches
     * Use with caution - only for maintenance or major updates
     *
     * @return bool
     */
    public static function clearAll(): bool
    {
        try {
            Cache::flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function getStats(): array
    {
        try {
            $versions = [];
            foreach (self::GROUPS as $group) {
                $versions[$group] = self::groupVersion($group);
            }

            return [
                'driver' => config('cache.default'),
                // How many times each group has been invalidated since its counter was created.
                'invalidations_by_group' => $versions,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

