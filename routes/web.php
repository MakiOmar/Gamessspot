<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StoreProfileController;
use App\Http\Controllers\RoleAssignmentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SpecialPriceController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\CardCategoryController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceRepairController;
use App\Http\Controllers\PublicDeviceController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return view('welcome');
});

// Test session functionality - Access: /test-session
Route::get('/test-session', function () {
    $testValue = 'session_works_' . time();
    session(['test_key' => $testValue]);
    
    $diagnosis = array(
        'timestamp'         => now()->toDateTimeString(),
        'environment'       => app()->environment(),
        'session_driver'    => config('session.driver'),
        'session_lifetime'  => config('session.lifetime') . ' minutes',
        'csrf_token'        => csrf_token(),
        'session_id'        => session()->getId(),
        'session_test'      => session('test_key'),
        'session_all'       => session()->all(),
        'storage_path'      => storage_path('framework/sessions'),
        'permissions'       => is_writable(storage_path('framework/sessions')) ? 'writable ✓' : 'NOT writable ✗',
        'session_file_exists' => file_exists(storage_path('framework/sessions')),
        'config_cached'     => file_exists(base_path('bootstrap/cache/config.php')),
        'test_result'       => (session('test_key') === $testValue) ? '✓ Sessions working correctly' : '✗ Session save failed',
    );

    // Test write to session directory
    try {
        $testFile = storage_path('framework/sessions/test_write_' . time());
        file_put_contents($testFile, 'test');
        $canWrite = file_exists($testFile);
        unlink($testFile);
        $diagnosis['can_write_to_session_dir'] = $canWrite ? '✓ Can write to session directory' : '✗ Cannot write to session directory';
    } catch (\Exception $e) {
        $diagnosis['can_write_to_session_dir'] = '✗ Error: ' . $e->getMessage();
    }

    return response()->json($diagnosis, 200, array(), JSON_PRETTY_PRINT);
});

// Debug route for testing phone number handling
Route::get('/debug-phone', function () {
    $countryCode = '+20';
    $phoneNumber = '1234567890';
    $fullPhone = $countryCode . $phoneNumber;
    
    // Check existing users
    $existingUsers = \App\Models\User::where('phone', $fullPhone)
        ->orWhere('phone', $phoneNumber)
        ->get();
    
    return response()->json([
        'country_code' => $countryCode,
        'phone_number' => $phoneNumber,
        'full_phone' => $fullPhone,
        'existing_users' => $existingUsers->toArray()
    ]);
});

// Redis Status Check Route - Access via: /check-redis
Route::get('/check-redis', function () {
    $status = array(
        'timestamp'      => now()->toDateTimeString(),
        'environment'    => app()->environment(),
        'cache_driver'   => config('cache.default'),
        'session_driver' => config('session.driver'),
        'queue_driver'   => config('queue.default'),
        'redis_config'   => array(
            'host'     => config('database.redis.default.host'),
            'port'     => config('database.redis.default.port'),
            'database' => config('database.redis.default.database'),
        ),
        'tests'          => array(),
    );

    // Test 1: Check if Redis extension is loaded
    $status['tests']['php_redis_extension'] = array(
        'loaded'  => extension_loaded('redis'),
        'message' => extension_loaded('redis') ? 'PHP Redis extension is loaded' : 'PHP Redis extension NOT loaded',
    );

    // Test 2: Try to connect to Redis
    try {
        $redis = \Illuminate\Support\Facades\Redis::connection();
        $status['tests']['redis_connection'] = array(
            'success' => true,
            'message' => 'Successfully connected to Redis',
        );

        // Test 3: Ping Redis
        try {
            $redis->ping();
            $status['tests']['redis_ping'] = array(
                'success' => true,
                'message' => 'PONG - Redis is responding',
            );
        } catch ( \Exception $e ) {
            $status['tests']['redis_ping'] = array(
                'success' => false,
                'message' => 'Ping failed: ' . $e->getMessage(),
            );
        }

        // Test 4: Set and Get test
        try {
            $testKey   = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);
            
            $redis->set($testKey, $testValue, 'EX', 10); // Expire in 10 seconds
            $retrieved = $redis->get($testKey);
            
            $status['tests']['redis_set_get'] = array(
                'success' => ( $retrieved === $testValue ),
                'message' => ( $retrieved === $testValue ) ? 'SET/GET operations working' : 'SET/GET test failed',
            );

            $redis->del($testKey); // Clean up
        } catch ( \Exception $e ) {
            $status['tests']['redis_set_get'] = array(
                'success' => false,
                'message' => 'SET/GET failed: ' . $e->getMessage(),
            );
        }

        // Test 5: Get Redis Server Info
        try {
            $info = $redis->info();
            
            if ( isset( $info['Server'] ) ) {
                $status['redis_server'] = array(
                    'version'      => $info['Server']['redis_version'] ?? 'Unknown',
                    'mode'         => $info['Server']['redis_mode'] ?? 'Unknown',
                    'os'           => $info['Server']['os'] ?? 'Unknown',
                    'uptime_days'  => isset( $info['Server']['uptime_in_days'] ) ? $info['Server']['uptime_in_days'] : 'Unknown',
                );
            }
            
            if ( isset( $info['Memory'] ) ) {
                $status['redis_memory'] = array(
                    'used_memory_human' => $info['Memory']['used_memory_human'] ?? 'Unknown',
                    'used_memory_peak_human' => $info['Memory']['used_memory_peak_human'] ?? 'Unknown',
                );
            }

            if ( isset( $info['Stats'] ) ) {
                $status['redis_stats'] = array(
                    'total_connections_received' => $info['Stats']['total_connections_received'] ?? 'Unknown',
                    'total_commands_processed'   => $info['Stats']['total_commands_processed'] ?? 'Unknown',
                );
            }

        } catch ( \Exception $e ) {
            $status['tests']['redis_info'] = array(
                'success' => false,
                'message' => 'Could not retrieve server info: ' . $e->getMessage(),
            );
        }

    } catch ( \Exception $e ) {
        $status['tests']['redis_connection'] = array(
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage(),
            'error'   => $e->getCode(),
        );
    }

    // Overall status
    $allTestsPassed = true;
    foreach ( $status['tests'] as $test ) {
        if ( isset( $test['success'] ) && ! $test['success'] ) {
            $allTestsPassed = false;
            break;
        }
    }

    $status['overall_status'] = $allTestsPassed ? 'REDIS IS WORKING ✓' : 'REDIS HAS ISSUES ✗';

    return response()->json($status, 200, array(), JSON_PRETTY_PRINT);
});

// Memcached Status Check Route - Access via: /check-memcached
Route::get('/check-memcached', function () {
    $status = array(
        'timestamp'      => now()->toDateTimeString(),
        'environment'    => app()->environment(),
        'cache_driver'   => config('cache.default'),
        'session_driver' => config('session.driver'),
        'queue_driver'   => config('queue.default'),
        'memcached_config' => array(
            'host'   => config('cache.stores.memcached.servers.0.host'),
            'port'   => config('cache.stores.memcached.servers.0.port'),
            'weight' => config('cache.stores.memcached.servers.0.weight'),
        ),
        'tests'          => array(),
    );

    // Test 1: Check if Memcached extension is loaded
    $status['tests']['php_memcached_extension'] = array(
        'loaded'  => extension_loaded('memcached'),
        'message' => extension_loaded('memcached') ? 'PHP Memcached extension is loaded' : 'PHP Memcached extension NOT loaded',
    );

    // Test 2: Try to connect to Memcached
    try {
        if ( config('cache.default') !== 'memcached' ) {
            $status['tests']['cache_driver_check'] = array(
                'success' => false,
                'message' => 'Cache driver is not set to memcached (current: ' . config('cache.default') . ')',
                'note'    => 'Set CACHE_DRIVER=memcached in .env to enable',
            );
        }

        // Create a Memcached instance directly
        $memcached = new \Memcached();
        $memcached->addServer(
            config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
            config('cache.stores.memcached.servers.0.port', 11211)
        );

        $status['tests']['memcached_connection'] = array(
            'success' => true,
            'message' => 'Successfully created Memcached connection',
        );

        // Test 3: Set and Get test
        try {
            $testKey   = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);
            
            $setResult = $memcached->set($testKey, $testValue, 10); // Expire in 10 seconds
            $retrieved = $memcached->get($testKey);
            
            $status['tests']['memcached_set_get'] = array(
                'success' => ( $retrieved === $testValue ),
                'message' => ( $retrieved === $testValue ) ? 'SET/GET operations working' : 'SET/GET test failed',
                'set_result' => $setResult,
            );

            $memcached->delete($testKey); // Clean up
        } catch ( \Exception $e ) {
            $status['tests']['memcached_set_get'] = array(
                'success' => false,
                'message' => 'SET/GET failed: ' . $e->getMessage(),
            );
        }

        // Test 4: Get Server Stats
        try {
            $stats = $memcached->getStats();
            
            if ( ! empty( $stats ) ) {
                $firstServer = array_values($stats)[0];
                
                if ( $firstServer && is_array($firstServer) ) {
                    $status['memcached_server'] = array(
                        'version'            => $firstServer['version'] ?? 'Unknown',
                        'uptime'             => isset( $firstServer['uptime'] ) ? round( $firstServer['uptime'] / 86400, 1 ) . ' days' : 'Unknown',
                        'total_connections'  => $firstServer['total_connections'] ?? 'Unknown',
                        'current_connections' => $firstServer['curr_connections'] ?? 'Unknown',
                        'threads'            => $firstServer['threads'] ?? 'Unknown',
                    );

                    $status['memcached_memory'] = array(
                        'bytes_used'    => isset( $firstServer['bytes'] ) ? round( $firstServer['bytes'] / 1024 / 1024, 2 ) . ' MB' : 'Unknown',
                        'limit'         => isset( $firstServer['limit_maxbytes'] ) ? round( $firstServer['limit_maxbytes'] / 1024 / 1024, 2 ) . ' MB' : 'Unknown',
                        'usage_percent' => isset( $firstServer['bytes'], $firstServer['limit_maxbytes'] ) ? round( ( $firstServer['bytes'] / $firstServer['limit_maxbytes'] ) * 100, 2 ) . '%' : 'Unknown',
                    );

                    $status['memcached_performance'] = array(
                        'get_hits'      => $firstServer['get_hits'] ?? 'Unknown',
                        'get_misses'    => $firstServer['get_misses'] ?? 'Unknown',
                        'hit_rate'      => isset( $firstServer['get_hits'], $firstServer['get_misses'] ) 
                            ? round( ( $firstServer['get_hits'] / ( $firstServer['get_hits'] + $firstServer['get_misses'] ) ) * 100, 2 ) . '%'
                            : 'Unknown',
                        'total_items'   => $firstServer['curr_items'] ?? 'Unknown',
                        'evictions'     => $firstServer['evictions'] ?? 'Unknown',
                    );
                } else {
                    $status['tests']['memcached_stats'] = array(
                        'success' => false,
                        'message' => 'Server returned invalid stats',
                    );
                }
            } else {
                $status['tests']['memcached_stats'] = array(
                    'success' => false,
                    'message' => 'Could not retrieve server stats - server may not be running',
                );
            }

        } catch ( \Exception $e ) {
            $status['tests']['memcached_stats'] = array(
                'success' => false,
                'message' => 'Could not retrieve stats: ' . $e->getMessage(),
            );
        }

    } catch ( \Exception $e ) {
        $status['tests']['memcached_connection'] = array(
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage(),
            'error'   => $e->getCode(),
        );
    }

    // Overall status
    $allTestsPassed = true;
    foreach ( $status['tests'] as $test ) {
        if ( isset( $test['success'] ) && ! $test['success'] ) {
            $allTestsPassed = false;
            break;
        }
    }

    $status['overall_status'] = $allTestsPassed ? 'MEMCACHED IS WORKING ✓' : 'MEMCACHED HAS ISSUES ✗';

    return response()->json($status, 200, array(), JSON_PRETTY_PRINT);
});

// Quick Memcached Test - Access via: /test-memcached
Route::get('/test-memcached', function () {
    $result = [
        'php_extension' => extension_loaded('memcached'),
        'timestamp' => now()->toDateTimeString(),
    ];
    
    if (!$result['php_extension']) {
        return response()->json([
            'status' => 'error',
            'message' => 'Memcached PHP extension not loaded',
            'result' => $result
        ], 500);
    }
    
    try {
        $memcached = new \Memcached();
        $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 2000);
        
        $host = config('cache.stores.memcached.servers.0.host', '127.0.0.1');
        $port = config('cache.stores.memcached.servers.0.port', 11211);
        
        $result['host'] = $host;
        $result['port'] = $port;
        
        $memcached->addServer($host, $port);
        
        // Get stats
        $stats = $memcached->getStats();
        $result['stats'] = $stats;
        $result['stats_empty'] = empty($stats);
        
        if (empty($stats)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot connect to Memcached server - service may not be running',
                'result' => $result,
                'solution' => 'Start Memcached service on ' . $host . ':' . $port
            ], 500);
        }
        
        // Try to set a value
        $testKey = 'test_' . time();
        $setResult = $memcached->set($testKey, 'Hello Memcached', 10);
        $result['set_result'] = $setResult;
        $result['set_result_code'] = $memcached->getResultCode();
        $result['set_result_message'] = $memcached->getResultMessage();
        
        if (!$setResult) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to write to Memcached',
                'result' => $result
            ], 500);
        }
        
        // Try to get the value
        $getValue = $memcached->get($testKey);
        $result['get_value'] = $getValue;
        $result['get_result_code'] = $memcached->getResultCode();
        $result['get_result_message'] = $memcached->getResultMessage();
        
        // Clean up
        $memcached->delete($testKey);
        
        if ($getValue === 'Hello Memcached') {
            return response()->json([
                'status' => 'success',
                'message' => 'Memcached is working correctly! ✓',
                'result' => $result
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Memcached read test failed',
                'result' => $result
            ], 500);
        }
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'result' => $result
        ], 500);
    }
});

// Combined Cache Systems Check - Access via: /check-cache
Route::get('/check-cache', function () {
    $status = array(
        'timestamp'      => now()->toDateTimeString(),
        'environment'    => app()->environment(),
        'cache_driver'   => config('cache.default'),
        'session_driver' => config('session.driver'),
        'queue_driver'   => config('queue.default'),
        'systems'        => array(),
    );

    // Check Redis
    $status['systems']['redis'] = array(
        'configured'      => in_array( config('cache.default'), array( 'redis' ) ) || in_array( config('session.driver'), array( 'redis' ) ),
        'extension_loaded' => extension_loaded('redis'),
        'status'          => 'not_checked',
    );

    if ( $status['systems']['redis']['extension_loaded'] ) {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $redis->ping();
            $status['systems']['redis']['status'] = 'working';
            $status['systems']['redis']['message'] = '✓ Redis is active and responding';
        } catch ( \Exception $e ) {
            $status['systems']['redis']['status'] = 'error';
            $status['systems']['redis']['message'] = '✗ Redis error: ' . $e->getMessage();
        }
    } else {
        $status['systems']['redis']['status'] = 'not_available';
        $status['systems']['redis']['message'] = '✗ PHP Redis extension not loaded';
    }

    // Check Memcached
    $status['systems']['memcached'] = array(
        'configured'       => in_array( config('cache.default'), array( 'memcached' ) ),
        'extension_loaded' => extension_loaded('memcached'),
        'status'           => 'not_checked',
    );

    if ( $status['systems']['memcached']['extension_loaded'] ) {
        try {
            $memcached = new \Memcached();
            $memcached->addServer(
                config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
                config('cache.stores.memcached.servers.0.port', 11211)
            );
            
            $testKey = 'health_' . time();
            $setResult = $memcached->set($testKey, 'test', 5);
            $getValue = $memcached->get($testKey);
            $memcached->delete($testKey);
            
            if ( $getValue === 'test' ) {
                $status['systems']['memcached']['status'] = 'working';
                $status['systems']['memcached']['message'] = '✓ Memcached is active and responding';
            } else {
                $status['systems']['memcached']['status'] = 'error';
                $status['systems']['memcached']['message'] = '✗ Memcached not responding correctly';
            }
        } catch ( \Exception $e ) {
            $status['systems']['memcached']['status'] = 'error';
            $status['systems']['memcached']['message'] = '✗ Memcached error: ' . $e->getMessage();
        }
    } else {
        $status['systems']['memcached']['status'] = 'not_available';
        $status['systems']['memcached']['message'] = '✗ PHP Memcached extension not loaded';
    }

    // Test current cache driver
    try {
        \Cache::put('health_check_test', 'working', 10);
        $test = \Cache::get('health_check_test');
        
        $status['current_cache_test'] = array(
            'success' => ( $test === 'working' ),
            'message' => ( $test === 'working' ) ? '✓ Current cache driver (' . config('cache.default') . ') is working' : '✗ Cache test failed',
        );
    } catch ( \Exception $e ) {
        $status['current_cache_test'] = array(
            'success' => false,
            'message' => '✗ Cache error: ' . $e->getMessage(),
        );
    }

    // Recommendation
    $workingSystems = array_filter(
        array( 'redis', 'memcached' ),
        function ($system) use ($status) {
            return $status['systems'][ $system ]['status'] === 'working';
        }
    );

    if ( ! empty( $workingSystems ) && config('cache.default') === 'file' ) {
        $status['recommendation'] = '⚠️ You have ' . implode( ' and ', $workingSystems ) . ' available but using file cache. Consider updating CACHE_DRIVER in .env';
    } elseif ( empty( $workingSystems ) && app()->environment('production') ) {
        $status['recommendation'] = '⚠️ No memory cache system available. Consider installing Redis or Memcached for better performance.';
    } else {
        $status['recommendation'] = '✓ Configuration looks good';
    }

    return response()->json($status, 200, array(), JSON_PRETTY_PRINT);
});

Route::prefix('manager')->group(function () {
    // Manager login routes (no middleware needed here)
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('manager.login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('manager.login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('manager.logout');

    // Group routes that require 'auth:admin' middleware
    Route::middleware('auth:admin')->group(function () {
        // Dashboard route
        Route::get('/', [DashboardController::class, 'dashboard'])->middleware('can:access-dashboard')->name('manager.dashboard');

        // Routes with 'can:manage-games' middleware
        Route::middleware('can:manage-games')->group(function () {
            Route::prefix('games')->group(function () {
                Route::get('/', [ManagerController::class, 'showGames'])->name('manager.games');
                Route::get('/ps4', [ManagerController::class, 'showPS4Games'])->name('manager.games.ps4');
                Route::get('/ps5', [ManagerController::class, 'showPS5Games'])->name('manager.games.ps5');
                Route::get('/search/ps4', [ManagerController::class, 'searchPS4Games'])->name('manager.games.search.ps4');
                Route::get('/search/ps5', [ManagerController::class, 'searchPS5Games'])->name('manager.games.search.ps5');
                Route::get('/search', [ManagerController::class, 'searchGamesByTitle'])->name('manager.games.search');
            });
        });

        // Routes with 'can:edit-games' middleware for admin only
        Route::middleware(['checkRole:admin', 'can:edit-games'])->group(function () {
            Route::prefix('games')->group(function () {
                Route::get('/{id}/edit', [ManagerController::class, 'edit'])->name('manager.games.edit');
                Route::post('/store', [ManagerController::class, 'store'])->name('games.store');
                Route::put('/{id}', [ManagerController::class, 'update'])->name('manager.games.update');
            });
        });

        // Routes with 'can:manage-accounts' middleware for admin and account manager
        Route::middleware(['checkRole:admin,account manager', 'can:manage-accounts'])->group(function () {
            Route::prefix('accounts')->group(function () {
                Route::get('/', [AccountController::class, 'index'])->name('manager.accounts');
                Route::post('/store', [AccountController::class, 'store'])->name('manager.accounts.store');
                Route::get('/search', [AccountController::class, 'search'])->name('manager.accounts.search');
                Route::get('/export', [AccountController::class, 'export'])->name('manager.accounts.export');
                Route::post('/import', [AccountController::class, 'import'])->name('manager.accounts.import');
                Route::get('/template', [AccountController::class, 'template'])->name('manager.accounts.template');
                Route::get('/{id}/edit', [AccountController::class, 'edit'])->name('manager.accounts.edit');
                Route::put('/{id}', [AccountController::class, 'update'])->name('manager.accounts.update');
            });
        });

        // Routes with 'can:view-sell-log' middleware
        Route::middleware('can:view-sell-log')->group(function () {
            Route::prefix('orders')->group(function () {
                Route::get('/', [OrderController::class, 'index'])->name('manager.orders');
                Route::get('/search', [OrderController::class, 'search'])->name('manager.orders.search');
                Route::get('/quick-search', [OrderController::class, 'quickSearch'])->name('manager.orders.qsearch');
                Route::get('/export', [OrderController::class, 'export'])->name('manager.orders.export');
                Route::post('/store', [OrderController::class, 'store'])->name('orders.store');
                Route::post('/sell-card', [OrderController::class, 'sellCard'])->name('manager.orders.sell.card');
                Route::post('/send-to-pos', [OrderController::class, 'sendToPos'])->name('manager.orders.sendToPos');
                
                Route::get('/has-problem', [OrderController::class, 'ordersHasProblem'])->name('manager.orders.has_problem');
                Route::get('/needs-return', [OrderController::class, 'ordersWithNeedsReturn'])->name('manager.orders.needs_return');
                Route::get('/solved', [OrderController::class, 'solvedOrders'])->name('manager.orders.solved');
            });
        });

        // Customer-related routes
        Route::prefix('customers')->group(function () {
            Route::get('/export', [OrderController::class, 'customersExport'])->name('manager.customers.export');
            Route::get('/', [OrderController::class, 'uniqueBuyers'])->middleware('can:manage-users')->name('manager.uniqueBuyers');
            Route::get('/search', [OrderController::class, 'searchCustomers'])->name('manager.orders.searchCustomers');
            Route::get('/buyer-name', [UserController::class, 'searchUserHelper'])->name('manager.buyer.name');
        });

        // Admin-only order routes
        Route::middleware(['checkRole:admin', 'can:manage-options'])->group(function () {
            Route::post('/orders/undo', [OrderController::class, 'undo'])->name('manager.orders.undo');
        });
        Route::post('/reports/store', [ReportsController::class, 'store'])->name('manager.reports.store');
        // Routes with 'can:view-reports' middleware
        Route::middleware('can:view-reports')->group(function () {
            Route::post('/reports/solve-problem', [ReportsController::class, 'solveProblem'])->name('reports.solve_problem');
            Route::get('/reports/{order_id}', [ReportsController::class, 'getReportsForOrder']);
            
            Route::prefix('special-prices')->group(function () {
                Route::post('/create', [SpecialPriceController::class, 'createSpecialPrice'])->name('special-prices.create');
                Route::get('/{id}', [SpecialPriceController::class, 'getGamesWithSpecialPrices'])->name('manager.special-prices');
                Route::put('/{id}', [SpecialPriceController::class, 'update'])->name('special-prices.update');
                Route::get('/{id}/edit', [SpecialPriceController::class, 'edit'])->name('special-prices.edit');
                Route::put('/{id}/toggle-availability', [SpecialPriceController::class, 'toggleAvailability']);
            });
        });

        // Routes with 'can:manage-users' middleware
        Route::middleware('can:manage-users')->group(function () {
            Route::prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('manager.users.index');
                Route::get('/sales', [UserController::class, 'sales'])->name('manager.users.sales');
                Route::get('/accountants', [UserController::class, 'accountants'])->name('manager.users.accountants');
                Route::get('/admins', [UserController::class, 'admins'])->name('manager.users.admins');
                Route::get('/account-managers', [UserController::class, 'accountManagers'])->name('manager.users.acc.managers');
                Route::get('/customers', [UserController::class, 'customers'])->name('manager.users.customers');
                Route::get('/search/{role?}', [UserController::class, 'search'])->name('manager.users.search');
                Route::get('/{id}/edit', [UserController::class, 'edit'])->name('manager.users.edit');
                Route::put('/update/{id}', [UserController::class, 'update'])->name('manager.users.update');
                Route::post('/store', [UserController::class, 'store'])->name('manager.users.store');
                Route::delete('/delete/{id}', [UserController::class, 'destroy'])->name('users.delete');
                Route::post('/toggle-status/{id}', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
            });
        });

        // Routes with 'can:manage-store-profiles' middleware
        Route::middleware('can:manage-store-profiles')->group(function () {
            Route::prefix('storeProfiles')->group(function () {
                Route::get('/', [StoreProfileController::class, 'index'])->name('manager.storeProfiles.index');
                Route::post('/store', [StoreProfileController::class, 'store'])->name('manager.storeProfiles.store');
                Route::get('/{id}/edit', [StoreProfileController::class, 'edit'])->name('manager.storeProfiles.edit');
                Route::put('/update/{id}', [StoreProfileController::class, 'update'])->name('manager.storeProfiles.update');
                Route::get('/search', [StoreProfileController::class, 'search'])->name('manager.storeProfiles.search');
            });
        });

        // Routes with 'can:manage-gift-cards' middleware
        Route::middleware('can:manage-gift-cards')->group(function () {
            Route::get('/cards/sell', [CardCategoryController::class, 'sell'])->name('manager.sell-cards');
            Route::get('/cards/search', [CardCategoryController::class, 'searchSellCategories'])
            ->name('manager.sell-cards.search');

        });

        // Resource routes
        Route::resource('masters', MasterController::class);
        Route::resource('card-categories', CardCategoryController::class);
        Route::resource('cards', CardController::class);
        
        // Device Repair Management Routes
        Route::middleware('can:manage-device-repairs')->group(function () {
            Route::prefix('device-repairs')->group(function () {
                Route::get('/', [DeviceRepairController::class, 'index'])->name('device-repairs.index');
                Route::get('/create', [DeviceRepairController::class, 'create'])->name('device-repairs.create');
                Route::post('/', [DeviceRepairController::class, 'store'])->name('device-repairs.store');
                Route::get('/{deviceRepair}', [DeviceRepairController::class, 'show'])->name('device-repairs.show');
                Route::get('/{deviceRepair}/edit', [DeviceRepairController::class, 'edit'])->name('device-repairs.edit');
                Route::put('/{deviceRepair}', [DeviceRepairController::class, 'update'])->name('device-repairs.update');
                Route::delete('/{deviceRepair}', [DeviceRepairController::class, 'destroy'])->name('device-repairs.destroy');
                Route::patch('/{deviceRepair}/status', [DeviceRepairController::class, 'updateStatus'])->name('device-repairs.update-status');
                Route::get('/api/stats', [DeviceRepairController::class, 'getStats'])->name('device-repairs.stats');
                Route::post('/check-user', [DeviceRepairController::class, 'checkUser'])->name('device-repairs.check-user');
            });
        });

        // Settings Management Routes (Admin only)
        Route::middleware(['checkRole:admin', 'can:manage-options'])->group(function () {
            Route::prefix('settings')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
                Route::post('/update', [SettingsController::class, 'update'])->name('settings.update');
                Route::post('/reset', [SettingsController::class, 'reset'])->name('settings.reset');
                Route::get('/get/{key}', [SettingsController::class, 'get'])->name('settings.get');
                Route::post('/set/{key}', [SettingsController::class, 'set'])->name('settings.set');
            });
            
            // System Health Check Route (Admin only)
            Route::get('/health-check', [ManagerController::class, 'healthCheck'])->name('manager.health-check');
        });
    });
});

// Public Device Repair Routes
Route::prefix('device')->group(function () {
    Route::get('/submit', [PublicDeviceController::class, 'showSubmissionForm'])->name('device.submit');
    Route::post('/submit', [PublicDeviceController::class, 'submitDevice'])->name('device.submit.store');
    Route::get('/track', [PublicDeviceController::class, 'trackDevice'])->name('device.tracking');
    Route::post('/search', [PublicDeviceController::class, 'searchByPhone'])->name('device.search');
    Route::get('/api/country-codes', [PublicDeviceController::class, 'getCountryCodes'])->name('device.country-codes');
});