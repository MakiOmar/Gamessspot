<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param array{search?: string, status?: string, start_date?: string, end_date?: string, store_profile_id?: int} $filters
     */
    public function __construct(private array $filters = array())
    {
    }

    /**
     * Orders with seller, account/game or card category; left joins keep card and orphaned orders.
     */
    public function query(): Builder
    {
        $filters = $this->filters;

        return Order::query()
            ->leftJoin('users', 'orders.seller_id', '=', 'users.id')
            ->leftJoin('accounts', 'orders.account_id', '=', 'accounts.id')
            ->leftJoin('games', 'accounts.game_id', '=', 'games.id')
            ->leftJoin('cards', 'orders.card_id', '=', 'cards.id')
            ->leftJoin('card_categories', 'cards.card_category_id', '=', 'card_categories.id')
            ->select(array(
                'orders.id',
                'users.name as seller_name',
                'accounts.mail as account_mail',
                'accounts.password as account_password',
                'games.title as game_name',
                'card_categories.name as card_category_name',
                'orders.buyer_phone',
                'orders.buyer_name',
                'orders.price',
                'orders.sold_item',
                'orders.notes',
                'orders.created_at',
            ))
            ->when(! empty($filters['store_profile_id']), fn (Builder $query) => $query->where('orders.store_profile_id', $filters['store_profile_id']))
            ->when(! empty($filters['status']), fn (Builder $query) => $query->whereHas('reports', fn (Builder $reports) => $reports->where('status', $filters['status'])))
            ->when(! empty($filters['search']), fn (Builder $query) => $this->applySearch($query, $filters['search']))
            ->when(! empty($filters['start_date']), fn (Builder $query) => $query->where('orders.created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay()))
            ->when(! empty($filters['end_date']), fn (Builder $query) => $query->where('orders.created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay()))
            ->orderBy('orders.id');
    }

    /**
     * @param Order $order
     */
    public function map($order): array
    {
        return array(
            $order->id,
            $order->seller_name,
            $order->account_mail,
            $order->account_password,
            $order->game_name ?? $order->card_category_name,
            // Leading space keeps Excel from turning phone numbers into numbers.
            $order->buyer_phone !== null ? ' ' . $order->buyer_phone : null,
            $order->buyer_name,
            $order->price,
            $order->sold_item,
            $order->notes,
            optional($order->created_at)->format('Y-m-d H:i:s'),
        );
    }

    public function headings(): array
    {
        return array(
            'ID',
            'Seller Name',
            'Account Mail',
            'Password',
            'Game Name',
            'Buyer Phone',
            'Buyer Name',
            'Price',
            'Sold Item',
            'Notes',
            'Created At',
        );
    }

    private function applySearch(Builder $query, string $search): void
    {
        $like = '%' . $search . '%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('users.name', 'like', $like)
                ->orWhere('accounts.mail', 'like', $like)
                ->orWhere('orders.buyer_phone', 'like', $like)
                ->orWhere('orders.buyer_name', 'like', $like);
        });
    }
}
