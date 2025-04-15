<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Cache;
use App\Models\Card;
use App\Models\User;
use App\Models\Order;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use App\Models\StoresProfile;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // Get the total code cost, caching it for 10 minutes
        $totalCodeCost = Cache::remember('total_code_cost', 600, function () {
            return Card::sum('cost');
        });
        $accountsCost = Cache::remember('total_account_cost', 600, function () {
            return Account::sum('cost');
        });

        // Get the total user count, caching it for 10 minutes
        $totalUserCount = Cache::remember('total_user_count', 600, function () {
            return User::count();
        });

        // Get the total orders count, caching it for 5 minutes
        $totalOrderCount = Cache::remember('today_order_count', 300, function () {
            return Order::whereDate('created_at', now()->toDateString())->count();
        });

        // Get the unique buyer_phone count, caching it for 10 minutes
        $uniqueBuyerPhoneCount = Cache::remember('unique_buyer_phone_count', 600, function () {
            return Order::distinct('buyer_phone')->count('buyer_phone');
        });

        $topSellingGames = $this->topSellingGames();

        $topBuyers = $this->topBuyers();
        $topSellingStores = $this->topSellingStores();
        $branchesWithOrders = $this->branchesWithOrdersThisMonth();

        $storeProfiles = StoresProfile::withCount('orders')->paginate(10);

        $StockLevels = $this->getStockLevels();
        $lowStockGames = $StockLevels['lowStock'] ?? collect();
        $highStockGames = $StockLevels['highStock'] ?? collect();

        $orders   = $this->activity();
        $newUsersCount = Cache::remember('new_users_role_5_count', 600, function () {
            return User::whereHas('roles', function ($query) {
                    $query->where('role_id', 5);
            })
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
        });

        $total = $totalCodeCost + $accountsCost;
        return view(
            'manager.dashboard',
            compact(
                'accountsCost',
                'totalCodeCost',
                'total',
                'totalUserCount',
                'uniqueBuyerPhoneCount',
                'topSellingGames',
                'topBuyers',
                'lowStockGames',
                'highStockGames',
                'storeProfiles',
                'topSellingStores',
                'branchesWithOrders',
                'orders',
                'newUsersCount',
                'totalOrderCount'
            )
        );
    }
    public function branchesWithOrdersThisMonth()
    {
        return StoresProfile::withCount(['orders' => function ($query) {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
        }])
        ->withSum(['orders' => function ($query) {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
        }], 'price')
        ->having('orders_count', '>', 0)
        ->get();
    }
    public function activity()
    {
        // Fetch the 10 most recent orders for admin
        return Order::with(['seller', 'account.game', 'card'])
            ->latest()
            ->take(4)
            ->get();
    }

    public function topSellingGames()
    {
    // Get top 5 selling games
        return Order::select('accounts.game_id', DB::raw('count(orders.id) as total_sales'))
        ->join('accounts', 'orders.account_id', '=', 'accounts.id')
        ->groupBy('accounts.game_id')
        ->orderByDesc('total_sales')
        ->take(5)
        ->with('game') // Assuming you have a relationship defined to fetch game data
        ->get();
    }
    public function topBuyers()
    {
    // Get top buyers based on buyer_phone by counting the number of orders for each phone
        return Order::select('buyer_phone', 'buyer_name', DB::raw('count(*) as total_orders'))
        ->groupBy('buyer_phone', 'buyer_name')
        ->orderByDesc('total_orders')
        ->take(5)
        ->get();
    }
    public function getStockLevels()
    {
        $stocks = [
            'lowStock' => [
                'columns' => ['ps4_primary_stock', 'ps4_offline_stock', 'ps5_primary_stock', 'ps5_offline_stock'],
                'condition' => '<',
                'threshold' => 20,
            ],
            'highStock' => [
                'columns' => ['ps4_secondary_stock', 'ps5_secondary_stock'],
                'condition' => '>',
                'threshold' => 200,
            ],
        ];

        $results = [];

        foreach ($stocks as $key => $stock) {
            // Validate configuration
            if (empty($stock['columns']) || !is_array($stock['columns'])) {
                $results[$key] = collect();
                continue;
            }

            try {
                // Build the stock sum expression with COALESCE for each column
                $stockColumns = array_map(
                    fn($col) => "COALESCE(SUM(accounts.{$col}), 0)",
                    $stock['columns']
                );
                $stockExpression = implode(' + ', $stockColumns);

                $query = Game::query()
                    ->select([
                        'games.id',
                        'games.title',
                        'games.code',
                        'games.full_price',
                        'games.ps4_image_url',
                        'games.ps5_image_url',
                        DB::raw("{$stockExpression} as total_stock")
                    ])
                    ->leftJoin('accounts', 'games.id', '=', 'accounts.game_id')
                    ->groupBy([
                        'games.id',
                        'games.title',
                        'games.code',
                        'games.full_price',
                        'games.ps4_image_url',
                        'games.ps5_image_url'
                    ]);

                // Use havingRaw for more reliable comparison
                $query->havingRaw("{$stockExpression} {$stock['condition']} ?", [$stock['threshold']]);

                $results[$key] = $query->get();
            } catch (\Exception $e) {
                // Log error and return empty collection
                \Log::error("Failed to get stock levels for {$key}: " . $e->getMessage());
                $results[$key] = collect();
            }
        }
        return $results;
    }
    public function topSellingStores()
    {
        return StoresProfile::withCount('orders') // Count the number of orders
        ->withSum('orders', 'price') // Sum the price of all orders
        ->having('orders_sum_price', '>', 0) // Ensure total price > 0
        ->orderBy('orders_sum_price', 'desc') // Sort by total price in descending order
        ->take(3)
        ->get();
    }
}
