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

        $StockLevels = $this->getStockLevels();
        $lowStockGames = $StockLevels[0];
        $highStockGames = $StockLevels[1];

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
    // Low stock for PS4 primary and offline, PS5 primary and offline (stock < 20)
        $lowStockGames = Game::select(
            'games.*',
            DB::raw('COALESCE(SUM(accounts.ps4_primary_stock), 0) + COALESCE(SUM(accounts.ps4_offline_stock), 0) + COALESCE(SUM(accounts.ps5_primary_stock), 0) + COALESCE(SUM(accounts.ps5_offline_stock), 0) as total_primary_offline_stock')
        )
        ->join('accounts', 'games.id', '=', 'accounts.game_id')
        ->groupBy('games.id')
        ->having('total_primary_offline_stock', '<', 20)
        ->get();

    // High stock for PS4 and PS5 secondary (stock > 200)
        $highStockGames = Game::select(
            'games.*',
            DB::raw('COALESCE(SUM(accounts.ps4_secondary_stock), 0) + COALESCE(SUM(accounts.ps5_secondary_stock), 0) as total_secondary_stock')
        )
        ->join('accounts', 'games.id', '=', 'accounts.game_id')
        ->groupBy('games.id')
        ->having('total_secondary_stock', '>', 200)
        ->get();

        return  array($lowStockGames, $highStockGames);
    }
}
