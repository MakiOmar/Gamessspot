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

        // Get the unique buyer_phone count, caching it for 10 minutes
        $uniqueBuyerPhoneCount = Cache::remember('unique_buyer_phone_count', 600, function () {
            return Order::distinct('buyer_phone')->count('buyer_phone');
        });

        $topSellingGames = $this->topSellingGames();

        $topBuyers = $this->topBuyers();

        $storeProfiles = StoresProfile::withCount('orders')->paginate(10);

        $StockLevels = $this->getStockLevels();
        $lowStockGames = $StockLevels[0] ?? collect([]);
        $highStockGames = $StockLevels[1] ?? collect([]);

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
            )
        );
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
            // Validate columns
            if (empty($stock['columns']) || !is_array($stock['columns'])) {
                $results[$key] = collect(); // Return an empty collection if invalid
                continue;
            }

            // Generate stock aggregation logic dynamically
            $stockColumns = array_map(fn($col) => "COALESCE(SUM(accounts.$col), 0)", $stock['columns']);
            $stockExpression = implode(' + ', $stockColumns);

            $results[$key] = Game::select(
                'games.id',
                'games.title',
                'games.code',
                'games.full_price',
                'games.ps4_image_url',
                'games.ps5_image_url',
                DB::raw("$stockExpression as total_stock")
            )
            ->join('accounts', 'games.id', '=', 'accounts.game_id')
            ->groupBy(
                'games.id',
                'games.title',
                'games.code',
                'games.full_price',
                'games.ps4_image_url',
                'games.ps5_image_url'
            )
            ->having('total_stock', $stock['condition'], $stock['threshold'])
            ->get();
        }

        return $results;
    }
}
