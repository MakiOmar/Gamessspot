<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Game;
use App\Models\Trader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TraderProfileService
{
    public const TABS = array('overview', 'purchase-orders', 'accounts', 'payments', 'statement');

    public const PER_PAGE = 20;

    public function resolveTab(?string $tab): string
    {
        return in_array($tab, self::TABS, true) ? $tab : 'overview';
    }

    public function purchaseOrders(Trader $trader): LengthAwarePaginator
    {
        return $trader->purchaseOrders()
            ->withCount('accounts')
            ->with('creator:id,name')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, array('*'), 'po_page')
            ->withQueryString();
    }

    public function payments(Trader $trader): LengthAwarePaginator
    {
        return $trader->payments()
            ->with(array('creator:id,name', 'canceller:id,name'))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, array('*'), 'payment_page')
            ->withQueryString();
    }

    /**
     * Accounts purchased from the trader, filtered by email, game, purchase order and date range.
     */
    public function accounts(Trader $trader, Request $request): LengthAwarePaginator
    {
        $query = Account::query()
            ->where('trader_id', $trader->id)
            ->with(array('game:id,title', 'purchaseOrder:id,po_number'));

        if ($request->filled('email')) {
            $query->where('mail', 'like', '%' . $this->escapeLike((string) $request->input('email')) . '%');
        }

        if ($request->filled('game_id')) {
            $query->where('game_id', (int) $request->input('game_id'));
        }

        if ($request->filled('purchase_order_id')) {
            $query->where('purchase_order_id', (int) $request->input('purchase_order_id'));
        }

        if ($request->filled('from') && strtotime((string) $request->input('from'))) {
            $query->whereDate('purchase_date', '>=', $request->input('from'));
        }

        if ($request->filled('to') && strtotime((string) $request->input('to'))) {
            $query->whereDate('purchase_date', '<=', $request->input('to'));
        }

        return $query->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, array('*'), 'account_page')
            ->withQueryString();
    }

    /**
     * Games and purchase orders used by the trader, for the accounts filter selects.
     *
     * @return array{games: Collection, orders: Collection}
     */
    public function accountFilterOptions(Trader $trader): array
    {
        $gameIds = Account::where('trader_id', $trader->id)->distinct()->pluck('game_id');

        return array(
            'games' => Game::whereIn('id', $gameIds)->orderBy('title')->get(array('id', 'title')),
            'orders' => $trader->purchaseOrders()->orderByDesc('id')->get(array('id', 'po_number')),
        );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $value);
    }
}
