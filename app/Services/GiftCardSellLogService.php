<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Gift card sales (local and Next Load codes) for the Gift Cards Sell Log page.
 */
class GiftCardSellLogService
{
    public const PER_PAGE = 25;

    /**
     * @param  array{q: string, start_date: string, end_date: string, store_profile_id: ?int, card_category_id: ?int, source: string}  $filters
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        return $this->query($user, $filters)
            ->with(array('seller:id,name', 'storeProfile:id,name', 'card.category:id,name'))
            ->latest('orders.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array{q: string, start_date: string, end_date: string, store_profile_id: ?int, card_category_id: ?int, source: string}  $filters
     * @return array{count: int, revenue: float, cost: float}
     */
    public function totals(User $user, array $filters): array
    {
        $query = $this->query($user, $filters);

        return array(
            'count'   => (clone $query)->count(),
            'revenue' => (float) (clone $query)->sum('orders.price'),
            'cost'    => (float) Card::whereIn('id', (clone $query)->select('orders.card_id'))->sum('cost'),
        );
    }

    /**
     * @param  array{q: string, start_date: string, end_date: string, store_profile_id: ?int, card_category_id: ?int, source: string}  $filters
     */
    private function query(User $user, array $filters): Builder
    {
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();
        $search = $filters['q'];

        return Order::query()
            ->where('orders.sold_item', 'card')
            ->whereNotNull('orders.card_id')
            ->whereBetween('orders.created_at', array($start, $end))
            // Shop orders have no seller: only users who may see every customer's orders see them.
            ->when(! $this->seesAllSales($user), fn (Builder $q) => $q->where('orders.seller_id', $user->id))
            ->when($filters['store_profile_id'], fn (Builder $q, int $id) => $q->where('orders.store_profile_id', $id))
            ->when($filters['card_category_id'], fn (Builder $q, int $id) => $q->whereHas('card', fn (Builder $c) => $c->where('card_category_id', $id)))
            ->when($filters['source'] !== 'all', fn (Builder $q) => $q->whereHas('card', fn (Builder $c) => $c->where('source', $filters['source'])))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('orders.buyer_phone', 'like', '%' . $search . '%')
                ->orWhere('orders.buyer_name', 'like', '%' . $search . '%')
                // Card codes are encrypted: exact match only
                ->orWhereHas('card', fn (Builder $c) => $c->where('code_hash', Card::hashCode($search)))));
    }

    private function seesAllSales(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('search-customer-orders');
    }
}
