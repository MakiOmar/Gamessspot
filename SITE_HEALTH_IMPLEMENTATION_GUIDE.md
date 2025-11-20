# Site Health Check Implementation Guide

This guide explains how to implement a comprehensive system health check feature from this Laravel application into another Laravel application.

## Overview

The Site Health Check feature provides a comprehensive overview of your Laravel application's health, including:

- **PHP Configuration** - Version, memory limits, execution time
- **Laravel Configuration** - Version, environment, debug mode, timezone
- **Database Status** - Connection status, driver, database name
- **Cache Systems** - Redis, Memcached, File cache status and statistics
- **Session Configuration** - Driver, lifetime, security settings
- **Queue Configuration** - Default connection and available connections
- **Storage** - Disk space, writable status
- **PHP Extensions** - Required and optional extensions status

## Prerequisites

- Laravel 8.x or higher
- Admin authentication middleware (or your own auth system)
- Font Awesome icons (for the UI)
- Bootstrap 4/5 (for styling)

## Step-by-Step Implementation

### Step 1: Create the Health Check Controller Method

Add the `healthCheck()` method to your controller (e.g., `app/Http/Controllers/ManagerController.php` or create a dedicated `HealthCheckController.php`).

**File:** `app/Http/Controllers/HealthCheckController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Show the system health check page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $healthData = [];
        
        // PHP Information
        $healthData['php'] = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];

        // Laravel Configuration
        $healthData['laravel'] = [
            'version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];

        // Cache Configuration
        $healthData['cache'] = [
            'default_driver' => config('cache.default'),
            'stores' => array_keys(config('cache.stores')),
        ];

        // Session Configuration
        $healthData['session'] = [
            'driver' => config('session.driver'),
            'lifetime' => config('session.lifetime'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
        ];

        // Queue Configuration
        $healthData['queue'] = [
            'default' => config('queue.default'),
            'connections' => array_keys(config('queue.connections')),
        ];

        // Database Check
        $healthData['database'] = [
            'default' => config('database.default'),
            'connection' => 'not_checked',
        ];
        
        try {
            DB::connection()->getPdo();
            $healthData['database']['connection'] = 'working';
            $healthData['database']['driver'] = DB::connection()->getDriverName();
            $healthData['database']['database'] = DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            $healthData['database']['connection'] = 'error';
            $healthData['database']['message'] = $e->getMessage();
        }

        // Redis Check
        $healthData['redis'] = [
            'configured' => in_array(config('cache.default'), ['redis']) || in_array(config('session.driver'), ['redis']),
            'extension_loaded' => extension_loaded('redis'),
            'status' => 'not_checked',
        ];

        if ($healthData['redis']['extension_loaded']) {
            try {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $redis->ping();
                $healthData['redis']['status'] = 'working';
                $healthData['redis']['host'] = config('database.redis.default.host');
                $healthData['redis']['port'] = config('database.redis.default.port');
            } catch (\Exception $e) {
                $healthData['redis']['status'] = 'error';
                $healthData['redis']['message'] = $e->getMessage();
            }
        } else {
            $healthData['redis']['status'] = 'not_available';
            $healthData['redis']['message'] = 'PHP Redis extension not loaded';
        }

        // File Cache Statistics (if using file driver)
        if (config('cache.default') === 'file') {
            $healthData['file_cache'] = [
                'configured' => true,
                'status' => 'working',
                'path' => config('cache.stores.file.path'),
            ];
            
            try {
                $cachePath = config('cache.stores.file.path');
                
                // Count cache files
                $files = glob($cachePath . '/*');
                $fileCount = is_array($files) ? count($files) : 0;
                
                // Calculate total size
                $totalSize = 0;
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            $totalSize += filesize($file);
                        }
                    }
                }
                
                $healthData['file_cache']['statistics'] = [
                    'total_files' => $fileCount,
                    'total_size' => $totalSize,
                    'total_size_formatted' => $this->formatBytes($totalSize),
                    'writable' => is_writable($cachePath),
                ];
            } catch (\Exception $e) {
                $healthData['file_cache']['error'] = $e->getMessage();
            }
        }
        
        // Memcached Check (only if configured as cache driver or session driver)
        $memcachedConfigured = in_array(config('cache.default'), ['memcached']) 
                            || in_array(config('session.driver'), ['memcached']);
        
        $healthData['memcached'] = [
            'configured' => $memcachedConfigured,
            'extension_loaded' => extension_loaded('memcached'),
            'status' => 'not_checked',
            'host' => config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
            'port' => config('cache.stores.memcached.servers.0.port', 11211),
        ];
        
        // Only check Memcached if it's actually being used
        if (!$memcachedConfigured) {
            $healthData['memcached']['status'] = 'not_configured';
            $healthData['memcached']['message'] = 'Memcached is not configured as cache or session driver';
        }

        if ($memcachedConfigured && $healthData['memcached']['extension_loaded']) {
            try {
                $memcached = new \Memcached();
                $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 2000);
                $memcached->setOption(\Memcached::OPT_SEND_TIMEOUT, 2000);
                $memcached->setOption(\Memcached::OPT_RECV_TIMEOUT, 2000);
                $memcached->setOption(\Memcached::OPT_RETRY_TIMEOUT, 1);
                
                $memcached->addServer(
                    config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
                    config('cache.stores.memcached.servers.0.port', 11211)
                );
                
                // Get server stats to check if server is reachable
                $stats = $memcached->getStats();
                $serverKey = $healthData['memcached']['host'] . ':' . $healthData['memcached']['port'];
                
                if (empty($stats) || !isset($stats[$serverKey])) {
                    $healthData['memcached']['status'] = 'error';
                    $healthData['memcached']['message'] = 'Cannot connect to Memcached server. Please check if Memcached service is running.';
                    $healthData['memcached']['solution'] = 'Start Memcached service or check host/port configuration.';
                } else {
                    $serverStats = $stats[$serverKey];
                    
                    // Try to set and get a value
                    $testKey = 'health_check_' . time();
                    $setResult = $memcached->set($testKey, 'test', 5);
                    
                    if (!$setResult) {
                        $resultCode = $memcached->getResultCode();
                        $healthData['memcached']['status'] = 'error';
                        $healthData['memcached']['message'] = 'Failed to write to Memcached. Result code: ' . $resultCode;
                        $healthData['memcached']['result_message'] = $memcached->getResultMessage();
                    } else {
                        $getValue = $memcached->get($testKey);
                        $memcached->delete($testKey);
                        
                        if ($getValue === 'test') {
                            $healthData['memcached']['status'] = 'working';
                            
                            // Add memory and performance statistics
                            $healthData['memcached']['memory'] = [
                                'used_bytes' => $serverStats['bytes'],
                                'used_formatted' => $this->formatBytes($serverStats['bytes']),
                                'max_bytes' => $serverStats['limit_maxbytes'],
                                'max_formatted' => $this->formatBytes($serverStats['limit_maxbytes']),
                                'usage_percent' => round(($serverStats['bytes'] / $serverStats['limit_maxbytes']) * 100, 2),
                                'free_bytes' => $serverStats['limit_maxbytes'] - $serverStats['bytes'],
                                'free_formatted' => $this->formatBytes($serverStats['limit_maxbytes'] - $serverStats['bytes']),
                            ];
                            
                            $totalOps = $serverStats['get_hits'] + $serverStats['get_misses'];
                            $healthData['memcached']['performance'] = [
                                'curr_items' => $serverStats['curr_items'],
                                'total_items' => $serverStats['total_items'],
                                'evictions' => $serverStats['evictions'],
                                'get_hits' => $serverStats['get_hits'],
                                'get_misses' => $serverStats['get_misses'],
                                'hit_rate' => $totalOps > 0 
                                    ? round(($serverStats['get_hits'] / $totalOps) * 100, 2) 
                                    : 0,
                            ];
                        } else {
                            $healthData['memcached']['status'] = 'error';
                            $healthData['memcached']['message'] = 'Memcached not responding correctly (read test failed)';
                            $healthData['memcached']['result_code'] = $memcached->getResultCode();
                            $healthData['memcached']['result_message'] = $memcached->getResultMessage();
                        }
                    }
                }
            } catch (\Exception $e) {
                $healthData['memcached']['status'] = 'error';
                $healthData['memcached']['message'] = $e->getMessage();
                $healthData['memcached']['exception'] = get_class($e);
            }
        } else {
            $healthData['memcached']['status'] = 'not_available';
            $healthData['memcached']['message'] = 'PHP Memcached extension not loaded';
        }

        // Storage Check
        $healthData['storage'] = [
            'disk' => config('filesystems.default'),
            'writable' => is_writable(storage_path()),
            'free_space' => $this->formatBytes(disk_free_space(storage_path())),
            'total_space' => $this->formatBytes(disk_total_space(storage_path())),
        ];

        // PHP Extensions Check
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'json', 'tokenizer', 'xml', 'ctype', 'fileinfo'];
        $optionalExtensions = ['redis', 'memcached', 'imagick', 'gd', 'zip', 'curl'];
        
        $healthData['extensions'] = [
            'required' => [],
            'optional' => [],
        ];

        foreach ($requiredExtensions as $ext) {
            $healthData['extensions']['required'][$ext] = extension_loaded($ext);
        }

        foreach ($optionalExtensions as $ext) {
            $healthData['extensions']['optional'][$ext] = extension_loaded($ext);
        }

        // Cache Test
        try {
            $testKey = 'health_check_cache_' . time();
            Cache::put($testKey, 'test_value', 60);
            $testValue = Cache::get($testKey);
            Cache::forget($testKey);
            
            $healthData['cache']['test'] = ($testValue === 'test_value') ? 'working' : 'error';
        } catch (\Exception $e) {
            $healthData['cache']['test'] = 'error';
            $healthData['cache']['test_message'] = $e->getMessage();
        }

        // Optional: Add Cache Statistics if you have CacheManager
        // Uncomment and adapt if you have a CacheManager service
        /*
        try {
            if (class_exists(\App\Services\CacheManager::class)) {
                $cacheStats = \App\Services\CacheManager::getStats();
                $healthData['cache']['stats'] = $cacheStats;
            }
        } catch (\Exception $e) {
            $healthData['cache']['stats'] = ['error' => $e->getMessage()];
        }
        */

        return view('admin.health-check', compact('healthData'));
    }

    /**
     * Helper function to format bytes to human-readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

### Step 2: Create the Route

Add the route in your routes file (e.g., `routes/web.php`).

**For AdminLTE or similar admin panels:**

```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/health-check', [HealthCheckController::class, 'index'])->name('health-check');
    });
});
```

**Or if using a different route structure:**

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/manager/health-check', [HealthCheckController::class, 'index'])
        ->name('manager.health-check');
});
```

### Step 3: Create the View File

Create the view file `resources/views/admin/health-check.blade.php` (or adjust the path according to your view structure).

**Note:** This is a large file. Copy the entire content from the original file at `resources/views/manager/health-check.blade.php`. The view includes:

- Card-based layout with status badges
- Color-coded status indicators (working, error, warning, not available)
- Sections for each health check category
- Responsive design with Bootstrap
- Auto-refresh functionality (optional)

**Key sections in the view:**
- Quick Status Overview (database, Redis, Memcached, File Cache)
- PHP Configuration
- Laravel Configuration
- Database Information
- Redis Information
- File Cache Information (if using file driver)
- Memcached Information (if configured)
- Cache Configuration
- Cache Statistics (if CacheManager is available)
- Session Configuration
- Queue Configuration
- Storage Information
- PHP Extensions (required and optional)

**Important:** Make sure your view extends the correct layout. Update the `@extends('layouts.admin')` line to match your layout structure.

### Step 4: Update Layout/Menu (Optional)

If you want to add a menu item for the health check page, add it to your admin menu configuration:

**Example for AdminLTE:**

```php
// In config/adminlte.php or your menu configuration
[
    'text' => 'Health Check',
    'route' => 'admin.health-check',
    'icon' => 'fas fa-heartbeat',
],
```

### Step 5: Adjust View Paths (if needed)

If your view structure is different, make sure to:

1. Update the controller's `return view()` call to match your view path
2. Update the route name in the view's refresh button if you changed it
3. Adjust the `@extends()` directive in the Blade template

## Optional: CacheManager Integration

If your application uses a custom `CacheManager` service (like the original application), you can integrate it to show cache statistics. 

**In the controller, add after the Cache Test section:**

```php
// Cache Statistics from CacheManager (if available)
try {
    if (class_exists(\App\Services\CacheManager::class)) {
        $cacheStats = \App\Services\CacheManager::getStats();
        $healthData['cache']['stats'] = $cacheStats;
    }
} catch (\Exception $e) {
    $healthData['cache']['stats'] = ['error' => $e->getMessage()];
}
```

The view already has a section to display cache statistics if `$healthData['cache']['stats']` is present.

## Customization

### Adding Custom Health Checks

You can add custom health checks to the `$healthData` array:

```php
// Custom Service Check
$healthData['custom_service'] = [
    'status' => 'working',
    'message' => 'Service is running',
];
```

Then add a corresponding section in the Blade view.

### Changing Status Colors

In the view CSS, you can customize the status badge colors:

```css
.status-working {
    background-color: #28a745; /* Green */
    color: white;
}

.status-error {
    background-color: #dc3545; /* Red */
    color: white;
}

.status-warning {
    background-color: #ffc107; /* Yellow */
    color: #333;
}
```

### Auto-Refresh

The view includes an auto-refresh script that reloads the page every 5 minutes. To disable or change the interval:

```javascript
// In the @push('scripts') section
setTimeout(function() {
    location.reload();
}, 300000); // 5 minutes = 300000ms
```

## Testing

1. **Access the route:** Visit `/admin/health-check` (or your configured route)
2. **Check database connection:** Verify the database status shows as "WORKING"
3. **Test cache:** Ensure the cache test passes
4. **Verify Redis/Memcached:** If configured, check their status
5. **Check PHP extensions:** Verify all required extensions are loaded

## Troubleshooting

### View Not Found Error

- Check that the view path matches what's returned in the controller
- Ensure the Blade file exists in the correct directory
- Clear view cache: `php artisan view:clear`

### Route Not Found

- Verify the route is registered: `php artisan route:list | grep health-check`
- Check middleware is not blocking access
- Ensure the route name matches if using `route()` helper

### Memcached/Redis Errors

- Verify the services are running
- Check `.env` configuration
- Ensure PHP extensions are installed
- The health check will show detailed error messages

## Security Considerations

**Important:** The health check page exposes sensitive system information. Make sure to:

1. **Protect with authentication middleware:** Only allow authenticated users
2. **Add authorization:** Restrict to admins or authorized users only
3. **Consider IP restrictions:** Optionally restrict to specific IPs in production
4. **Disable in production:** If needed, add an environment check:

```php
public function index()
{
    // Only allow in non-production environments
    if (app()->environment('production')) {
        abort(404);
    }
    
    // ... rest of the code
}
```

## File Checklist

- [ ] Controller file created: `app/Http/Controllers/HealthCheckController.php`
- [ ] Route added to `routes/web.php`
- [ ] View file created: `resources/views/admin/health-check.blade.php`
- [ ] Layout extends correct parent layout
- [ ] Middleware protection added
- [ ] Menu item added (optional)
- [ ] CacheManager integration (optional)

## Summary

The Site Health Check feature provides a comprehensive dashboard to monitor your Laravel application's health. It checks:

- ✅ PHP and Laravel configuration
- ✅ Database connectivity
- ✅ Cache systems (Redis, Memcached, File)
- ✅ Session and queue configuration
- ✅ Storage availability
- ✅ PHP extensions
- ✅ System resources

This is particularly useful for:
- Development environments
- Staging servers
- Production monitoring (with proper security)
- Debugging configuration issues
- System maintenance

## Support

If you encounter issues during implementation:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify all required PHP extensions are installed
3. Ensure cache/session drivers are properly configured
4. Check middleware is not blocking the route

## License

This implementation guide is provided as-is for use in other Laravel applications. Adapt as needed for your specific requirements.

---

# Cache Management and Invalidation Guide

## Overview

This application uses a custom `CacheManager` service that provides a centralized caching system with automatic invalidation. The system works with Redis, Memcached, and File cache drivers without relying on cache tags (which are not supported by all drivers).

## Architecture

### Key Components

1. **CacheManager Service** - Centralized cache management
2. **Cache Key Registry** - Tracks all cache keys for pattern-based invalidation
3. **Model Observers** - Automatically invalidate cache when models change
4. **Cache Metadata** - Tracks cache hits/misses for monitoring

### Key Design Principles

- **No Cache Tags**: Uses a key registry instead of cache tags for cross-driver compatibility
- **Automatic Invalidation**: Model observers handle cache clearing on data changes
- **Prefix-Based Organization**: Cache keys use prefixes for easy grouping
- **Fail-Safe**: If cache fails, the system falls back to direct database queries

## CacheManager Service

### Installation

Copy the `CacheManager` service to your application:

**File:** `app/Services/CacheManager.php`

The complete service is provided in the implementation guide above. Key features:

- Static methods for easy access
- Registry-based key tracking
- Metadata tracking for monitoring
- Pattern-based invalidation

### Cache Key Prefixes

The system uses prefixes to organize cache keys:

```php
const PREFIX_DASHBOARD = 'dashboard:';
const PREFIX_ORDERS = 'orders:';
const PREFIX_USERS = 'users:';
const PREFIX_ACCOUNTS = 'accounts:';
const PREFIX_CARDS = 'cards:';
const PREFIX_GAMES = 'games:';
const PREFIX_DEVICES = 'devices:';
```

### Cache TTL (Time To Live)

Predefined TTL constants:

```php
const TTL_SHORT = 60;       // 1 minute - for frequently changing data
const TTL_MEDIUM = 300;     // 5 minutes - for moderately changing data
const TTL_LONG = 600;       // 10 minutes - for slowly changing data
const TTL_VERY_LONG = 3600; // 1 hour - for rarely changing data
```

**Usage Guidelines:**
- **TTL_SHORT**: Paginated listings (games, users, orders, accounts)
- **TTL_MEDIUM**: Dashboard statistics (today's orders, device repairs)
- **TTL_LONG**: User counts, account costs, buyer counts
- **TTL_VERY_LONG**: Registry and configuration data

## How Caching Works

### 1. Caching Data with `remember()`

The `remember()` method is the primary way to cache data:

```php
use App\Services\CacheManager;

// Cache a value with automatic hit/miss tracking
$result = CacheManager::remember('cache_key', CacheManager::TTL_LONG, function() {
    // Expensive operation (database query, API call, etc.)
    return \App\Models\User::count();
});
```

**What happens:**
1. Checks if cache exists
2. If yes (cache hit): Returns cached value, records metadata
3. If no (cache miss): Executes callback, stores result, records metadata
4. Registers key in registry for tracking

### 2. Cache Key Patterns

Cache keys follow a consistent pattern:

```php
// Dashboard statistics
'dashboard:today_order_count'

// User listings (with role and page)
'users:list:role_5:page_1'

// Game listings (with platform, store, and page)
'games:list:platform_ps4:store_17:page_1'

// Order listings (with filter, user/store, and page)
'orders:list:filter_all:user_123:page_1'
```

### 3. Cache Metadata

Each cache operation stores metadata:

```php
$metadata = CacheManager::getCacheMetadata('cache_key');
// Returns:
// [
//     'was_hit' => true/false,
//     'timestamp' => 1234567890,
//     'datetime' => '2024-01-01 12:00:00',
//     'execution_time_ms' => 5.2,
//     'query_time_ms' => 150.5, // Only on cache miss
//     'cache_saving_ms' => 145.3, // Only on cache miss
// ]

$wasHit = CacheManager::wasCacheHit('cache_key'); // true/false
```

## Cache Invalidation

### Automatic Invalidation (Recommended)

The system uses **Model Observers** to automatically invalidate cache when models change.

#### Setup Observers

**File:** `app/Providers/AppServiceProvider.php`

```php
use App\Observers\GameObserver;
use App\Observers\OrderObserver;
use App\Observers\UserObserver;
use App\Observers\AccountObserver;
use App\Observers\CardObserver;
use App\Observers\DeviceRepairObserver;

public function boot()
{
    // Register observers for automatic cache invalidation
    \App\Models\Game::observe(GameObserver::class);
    \App\Models\Order::observe(OrderObserver::class);
    \App\Models\User::observe(UserObserver::class);
    \App\Models\Account::observe(AccountObserver::class);
    \App\Models\Card::observe(CardObserver::class);
    \App\Models\DeviceRepair::observe(DeviceRepairObserver::class);
}
```

#### Example Observer

**File:** `app/Observers/GameObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Game;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

class GameObserver
{
    public function created(Game $game)
    {
        $this->invalidateGameCaches('created');
    }

    public function updated(Game $game)
    {
        $this->invalidateGameCaches('updated');
    }

    public function deleted(Game $game)
    {
        $this->invalidateGameCaches('deleted');
    }

    protected function invalidateGameCaches(string $event)
    {
        try {
            CacheManager::invalidateGames();
        } catch (\Exception $e) {
            Log::error('Failed to invalidate game cache', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

**What happens:**
- When a Game is created/updated/deleted, the observer automatically calls `CacheManager::invalidateGames()`
- All game-related caches are cleared
- Next request will fetch fresh data and cache it

### Manual Invalidation

You can also invalidate cache manually:

#### Invalidate by Category

```php
use App\Services\CacheManager;

// Invalidate all dashboard caches
CacheManager::invalidateDashboard();

// Invalidate all order caches
CacheManager::invalidateOrders();

// Invalidate all user caches
CacheManager::invalidateUsers();

// Invalidate all account caches
CacheManager::invalidateAccounts();

// Invalidate all card caches
CacheManager::invalidateCards();

// Invalidate all game caches
CacheManager::invalidateGames();

// Invalidate device repair caches
CacheManager::invalidateDeviceRepairs();
```

#### Invalidate Specific Keys

```php
// Forget a single key
CacheManager::forget('dashboard:today_order_count');

// Forget by pattern (wildcard)
$count = CacheManager::forgetByPattern('orders:*');
echo "Cleared {$count} order cache keys";
```

#### Clear All Caches

```php
// ⚠️ Use with caution - clears ALL application cache
CacheManager::clearAll();
```

## Usage Examples

### Example 1: Caching Dashboard Statistics

**Controller:**

```php
use App\Services\CacheManager;

class DashboardController extends Controller
{
    public function index()
    {
        // These methods handle caching internally
        $totalUsers = CacheManager::getTotalUserCount();
        $todayOrders = CacheManager::getTodayOrderCount();
        $totalCost = CacheManager::getTotalCodeCost() + CacheManager::getTotalAccountCost();
        
        return view('dashboard', compact('totalUsers', 'todayOrders', 'totalCost'));
    }
}
```

**What happens:**
1. First call: Executes database query, caches result for 10 minutes
2. Subsequent calls: Returns cached value instantly
3. When Order/User/Account changes: Observer automatically clears cache
4. Next call: Fetches fresh data and re-caches

### Example 2: Caching Paginated Listings

**Controller:**

```php
use App\Services\CacheManager;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $platform = 'ps4'; // or 'ps5', 'all'
        $storeProfileId = auth()->user()->store_profile_id ?? null;
        
        // Get cache key
        $cacheKey = CacheManager::getGameListingKey($platform, $page, $storeProfileId);
        
        // Cache the listing
        $games = CacheManager::getGameListing($platform, $page, function() use ($platform) {
            // This closure only executes on cache miss
            return Game::where('platform', $platform)->paginate(20);
        }, $storeProfileId);
        
        // Get cache metadata for debugging
        $cacheMetadata = CacheManager::getCacheMetadata($cacheKey);
        $fromCache = CacheManager::wasCacheHit($cacheKey);
        
        return view('games.index', compact('games', 'cacheMetadata', 'fromCache'));
    }
}
```

### Example 3: Manual Cache Invalidation in Controller

```php
use App\Services\CacheManager;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $order = Order::create($request->validated());
        
        // Manual invalidation (if not using observers)
        CacheManager::invalidateOrders();
        CacheManager::invalidateDashboard(); // Dashboard shows order counts
        
        return redirect()->route('orders.index');
    }
}
```

### Example 4: Creating Custom Cache Methods

**In CacheManager:**

```php
// Add to CacheManager class

const PREFIX_PRODUCTS = 'products:';

public static function getProductListing(int $page, callable $callback)
{
    $cacheKey = self::PREFIX_PRODUCTS . "list:page_{$page}";
    return self::remember($cacheKey, self::TTL_SHORT, $callback);
}

public static function invalidateProducts(): int
{
    $count = self::forgetByPattern(self::PREFIX_PRODUCTS . '*');
    Log::info('Product cache invalidated', ['keys_cleared' => $count]);
    return $count;
}
```

**Create Observer:**

```php
// app/Observers/ProductObserver.php
class ProductObserver
{
    public function created(Product $product) { $this->invalidate(); }
    public function updated(Product $product) { $this->invalidate(); }
    public function deleted(Product $product) { $this->invalidate(); }
    
    protected function invalidate()
    {
        CacheManager::invalidateProducts();
    }
}
```

## Cache Invalidation Strategy

### When to Invalidate

**Automatic (via Observers):**
- ✅ Model created/updated/deleted
- ✅ Model restored (soft deletes)

**Manual:**
- ✅ Bulk operations (imports, migrations)
- ✅ External data changes (webhooks, API updates)
- ✅ Admin actions (cache clear commands)

### What Gets Invalidated

**When Game changes:**
- All game listings (all pages, all platforms)
- Dashboard statistics (if showing game counts)

**When Order changes:**
- All order listings (all filters, all pages)
- Dashboard statistics (today's orders, buyer counts)

**When User changes:**
- User listings
- Dashboard statistics (user counts)

**When Account changes:**
- Account listings
- Dashboard statistics (account costs)

### Invalidation Best Practices

1. **Use Observers**: Let the system handle automatic invalidation
2. **Invalidate Related Caches**: When invalidating one category, consider related categories
3. **Be Specific**: Use specific invalidation methods rather than `clearAll()`
4. **Log Invalidation**: The system logs all invalidations for debugging

**Example - Comprehensive Invalidation:**

```php
// When updating an account that affects games
public function updateAccount(Request $request, Account $account)
{
    $account->update($request->validated());
    
    // Account observer will handle invalidateAccounts()
    // But also invalidate games since account stock affects game listings
    CacheManager::invalidateGames();
    CacheManager::invalidateDashboard();
}
```

## Cache Statistics

### View Cache Stats

The health check page shows cache statistics. You can also get them programmatically:

```php
$stats = CacheManager::getStats();

// Returns:
// [
//     'total_keys' => 45,
//     'driver' => 'memcached',
//     'registry_enabled' => true,
//     'keys_by_prefix' => [
//         'dashboard' => 3,
//         'orders' => 15,
//         'users' => 8,
//         'accounts' => 5,
//         'cards' => 2,
//         'games' => 10,
//         'devices' => 2,
//     ]
// ]
```

## Troubleshooting

### Cache Not Updating

**Problem:** Changes to data don't reflect in the application

**Solutions:**
1. Check if observer is registered in `AppServiceProvider`
2. Verify observer method calls `CacheManager::invalidate*()`
3. Manually clear cache: `CacheManager::invalidateGames()` (or relevant category)
4. Check logs for invalidation errors

### High Cache Miss Rate

**Problem:** Cache is frequently missing (low hit rate)

**Solutions:**
1. Increase TTL for stable data: `TTL_LONG` → `TTL_VERY_LONG`
2. Check if cache is being invalidated too often
3. Verify cache driver is working (check health check page)

### Cache Keys Not Found

**Problem:** `getKeysByPattern()` returns empty array

**Solutions:**
1. Check if keys are registered (registry might be empty after flush)
2. Verify prefix is correct
3. Check if registry key exists: `Cache::get('cache:registry')`

### Performance Issues

**Problem:** Cache operations are slow

**Solutions:**
1. Use Redis or Memcached instead of File cache
2. Check cache driver status (health check page)
3. Monitor cache hit/miss rates
4. Consider increasing TTL to reduce cache churn

## Artisan Commands

### Custom Cache Clear Command

**File:** `app/Console/Commands/CacheClearCommand.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheManager;

class CacheClearCommand extends Command
{
    protected $signature = 'cache:clear-app {type?}';
    protected $description = 'Clear application caches';

    public function handle()
    {
        $type = $this->argument('type');

        if ($type === 'all') {
            CacheManager::clearAll();
            $this->info('All caches cleared');
        } elseif ($type === 'dashboard') {
            $count = CacheManager::invalidateDashboard();
            $this->info("Dashboard cache cleared ({$count} keys)");
        } elseif ($type === 'orders') {
            $count = CacheManager::invalidateOrders();
            $this->info("Order cache cleared ({$count} keys)");
        } else {
            $this->error('Invalid type. Use: all, dashboard, orders, users, accounts, cards, games, devices');
        }
    }
}
```

**Usage:**

```bash
php artisan cache:clear-app all
php artisan cache:clear-app dashboard
php artisan cache:clear-app orders
```

## Migration Checklist

To implement this caching system in another Laravel application:

- [ ] Copy `app/Services/CacheManager.php`
- [ ] Create model observers for each model you want to cache
- [ ] Register observers in `AppServiceProvider::boot()`
- [ ] Update controllers to use `CacheManager` methods
- [ ] Test cache invalidation works correctly
- [ ] Monitor cache hit/miss rates
- [ ] Adjust TTL values based on data change frequency
- [ ] Add cache statistics to health check page (optional)

## Best Practices Summary

1. ✅ **Use Observers**: Automatic invalidation is less error-prone
2. ✅ **Prefix Keys**: Organize cache keys with consistent prefixes
3. ✅ **Appropriate TTL**: Match TTL to data change frequency
4. ✅ **Monitor Performance**: Track cache hit rates
5. ✅ **Fail Gracefully**: Cache failures shouldn't break the application
6. ✅ **Invalidate Related**: Clear related caches when data changes
7. ✅ **Use Registry**: Helps track and debug cache keys
8. ✅ **Log Actions**: Monitor cache invalidations for debugging

