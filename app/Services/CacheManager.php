<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized Cache Manager
 * 
 * Handles all caching operations with support for Redis, Memcached, and File drivers.
 * Uses a key registry approach instead of cache tags for compatibility with all drivers.
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
    
    /**
     * Cache key registry - stores all active cache keys
     * This allows us to invalidate groups without cache tags
     */
    const REGISTRY_KEY = 'cache:registry';
    
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
            // Register this key
            self::registerKey($key);
            
            // Check if cache exists (for hit/miss tracking)
            $cacheExists = Cache::has($key);
            
            $result = Cache::remember($key, $ttl, $callback);
            
            // Store cache metadata for debugging
            self::storeCacheMetadata($key, $cacheExists);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Cache remember failed: ' . $e->getMessage(), [
                'key' => $key,
                'exception' => get_class($e)
            ]);
            
            // If cache fails, execute callback directly
            return $callback();
        }
    }
    
    /**
     * Store cache metadata (hit/miss info)
     *
     * @param string $key
     * @param bool $wasHit
     * @return void
     */
    protected static function storeCacheMetadata(string $key, bool $wasHit): void
    {
        try {
            $metadata = [
                'was_hit' => $wasHit,
                'timestamp' => now()->timestamp,
                'datetime' => now()->toDateTimeString(),
            ];
            
            // Store metadata with a short TTL
            Cache::put("meta:{$key}", $metadata, 120); // 2 minutes
        } catch (\Exception $e) {
            // Silently fail - metadata is optional
            Log::debug('Failed to store cache metadata', ['key' => $key]);
        }
    }
    
    /**
     * Get cache metadata for a key
     *
     * @param string $key
     * @return array|null
     */
    public static function getCacheMetadata(string $key): ?array
    {
        try {
            return Cache::get("meta:{$key}");
        } catch (\Exception $e) {
            return null;
        }
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
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::error('Cache get failed: ' . $e->getMessage(), ['key' => $key]);
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
            self::registerKey($key);
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error('Cache put failed: ' . $e->getMessage(), ['key' => $key]);
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
            self::unregisterKey($key);
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error('Cache forget failed: ' . $e->getMessage(), ['key' => $key]);
            return false;
        }
    }
    
    /**
     * Forget multiple cache keys by pattern
     *
     * @param string $pattern
     * @return int Number of keys deleted
     */
    public static function forgetByPattern(string $pattern): int
    {
        try {
            $keys = self::getKeysByPattern($pattern);
            $count = 0;
            
            foreach ($keys as $key) {
                if (self::forget($key)) {
                    $count++;
                }
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::error('Cache forgetByPattern failed: ' . $e->getMessage(), ['pattern' => $pattern]);
            return 0;
        }
    }
    
    /**
     * Register a cache key in the registry
     *
     * @param string $key
     * @return void
     */
    protected static function registerKey(string $key): void
    {
        try {
            $registry = Cache::get(self::REGISTRY_KEY, []);
            
            if (!in_array($key, $registry)) {
                $registry[] = $key;
                Cache::put(self::REGISTRY_KEY, $registry, self::TTL_VERY_LONG);
            }
        } catch (\Exception $e) {
            // Silently fail - registry is optional
            Log::debug('Cache registry registration failed', ['key' => $key]);
        }
    }
    
    /**
     * Unregister a cache key from the registry
     *
     * @param string $key
     * @return void
     */
    protected static function unregisterKey(string $key): void
    {
        try {
            $registry = Cache::get(self::REGISTRY_KEY, []);
            $registry = array_filter($registry, fn($k) => $k !== $key);
            Cache::put(self::REGISTRY_KEY, array_values($registry), self::TTL_VERY_LONG);
        } catch (\Exception $e) {
            Log::debug('Cache registry unregistration failed', ['key' => $key]);
        }
    }
    
    /**
     * Get all keys matching a pattern
     *
     * @param string $pattern
     * @return array
     */
    protected static function getKeysByPattern(string $pattern): array
    {
        try {
            $registry = Cache::get(self::REGISTRY_KEY, []);
            
            // Convert wildcard pattern to regex
            $regex = '/^' . str_replace(['*', ':'], ['.*', '\:'], preg_quote($pattern, '/')) . '$/';
            
            return array_filter($registry, fn($key) => preg_match($regex, $key));
        } catch (\Exception $e) {
            Log::debug('Cache getKeysByPattern failed', ['pattern' => $pattern]);
            return [];
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
            self::PREFIX_DASHBOARD . 'today_order_count',
            self::TTL_MEDIUM,
            fn() => \App\Models\Order::whereDate('created_at', now()->toDateString())->count()
        );
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
     * @param string $filter Filter type ('all', 'has_problem', 'needs_return', 'solved')
     * @param int $page Page number
     * @param callable $callback Query callback
     * @return mixed
     */
    public static function getOrderListing(string $filter, int $page, callable $callback)
    {
        $cacheKey = self::PREFIX_ORDERS . "list:filter_{$filter}:page_{$page}";
        
        return self::remember($cacheKey, self::TTL_SHORT, $callback);
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
    
    /**
     * Invalidate all dashboard caches
     *
     * @return int Number of keys invalidated
     */
    public static function invalidateDashboard(): int
    {
        $count = 0;
        $count += self::forgetByPattern(self::PREFIX_DASHBOARD . '*');
        $count += self::forget(self::PREFIX_ORDERS . 'unique_buyer_phone_count') ? 1 : 0;
        $count += self::forget(self::PREFIX_DEVICES . 'repair_stats') ? 1 : 0;
        $count += self::forget(self::PREFIX_USERS . 'new_users_role_5_count') ? 1 : 0;
        
        Log::info('Dashboard cache invalidated', ['keys_cleared' => $count]);
        return $count;
    }
    
    /**
     * Invalidate order-related caches
     *
     * @return int Number of keys invalidated
     */
    public static function invalidateOrders(): int
    {
        $count = 0;
        $count += self::forgetByPattern(self::PREFIX_ORDERS . '*');
        $count += self::forget(self::PREFIX_DASHBOARD . 'today_order_count') ? 1 : 0;
        
        Log::info('Order cache invalidated', ['keys_cleared' => $count]);
        return $count;
    }
    
    /**
     * Invalidate user-related caches
     *
     * @return int Number of keys invalidated
     */
    public static function invalidateUsers(): int
    {
        $count = 0;
        $count += self::forgetByPattern(self::PREFIX_USERS . '*');
        
        Log::info('User cache invalidated', ['keys_cleared' => $count]);
        return $count;
    }
    
    /**
     * Invalidate account-related caches
     *
     * @return int Number of keys invalidated
     */
    public static function invalidateAccounts(): int
    {
        $count = 0;
        $count += self::forgetByPattern(self::PREFIX_ACCOUNTS . '*');
        
        Log::info('Account cache invalidated', ['keys_cleared' => $count]);
        return $count;
    }
    
    /**
     * Invalidate card-related caches
     *
     * @return int Number of keys invalidated
     */
    public static function invalidateCards(): int
    {
        $count = 0;
        $count += self::forgetByPattern(self::PREFIX_CARDS . '*');
        
        Log::info('Card cache invalidated', ['keys_cleared' => $count]);
        return $count;
    }
    
    /**
     * Invalidate game-related caches
     *
     * @param int|null $page Optional page number to invalidate specific page
     * @return int Number of keys invalidated
     */
    public static function invalidateGames(?int $page = null): int
    {
        $count = 0;
        
        if ($page !== null) {
            // Invalidate specific pages
            $count += self::forget(self::PREFIX_GAMES . "ps4_page_{$page}") ? 1 : 0;
            $count += self::forget(self::PREFIX_GAMES . "ps5_page_{$page}") ? 1 : 0;
        } else {
            // Invalidate all game caches
            $count += self::forgetByPattern(self::PREFIX_GAMES . '*');
        }
        
        Log::info('Game cache invalidated', ['keys_cleared' => $count, 'page' => $page]);
        return $count;
    }
    
    /**
     * Invalidate device repair caches
     *
     * @return int Number of keys invalidated
     */
    public static function invalidateDeviceRepairs(): int
    {
        $count = 0;
        $count += self::forgetByPattern(self::PREFIX_DEVICES . '*');
        
        Log::info('Device repair cache invalidated', ['keys_cleared' => $count]);
        return $count;
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
            Log::warning('All application cache cleared');
            return true;
        } catch (\Exception $e) {
            Log::error('Cache clear all failed: ' . $e->getMessage());
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
            $registry = Cache::get(self::REGISTRY_KEY, []);
            
            return [
                'total_keys' => count($registry),
                'driver' => config('cache.default'),
                'registry_enabled' => true,
                'keys_by_prefix' => [
                    'dashboard' => count(array_filter($registry, fn($k) => str_starts_with($k, self::PREFIX_DASHBOARD))),
                    'orders' => count(array_filter($registry, fn($k) => str_starts_with($k, self::PREFIX_ORDERS))),
                    'users' => count(array_filter($registry, fn($k) => str_starts_with($k, self::PREFIX_USERS))),
                    'accounts' => count(array_filter($registry, fn($k) => str_starts_with($k, self::PREFIX_ACCOUNTS))),
                    'cards' => count(array_filter($registry, fn($k) => str_starts_with($k, self::PREFIX_CARDS))),
                    'games' => count(array_filter($registry, fn($k) => str_starts_with($k, self::PREFIX_GAMES))),
                    'devices' => count(array_filter($registry, fn($k) => str_starts_with($k, self::PREFIX_DEVICES))),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Cache stats failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

