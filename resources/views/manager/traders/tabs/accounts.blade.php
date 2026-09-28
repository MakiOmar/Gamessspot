{{-- Accounts purchased from this trader, with filters --}}
<form method="GET" action="{{ route('manager.traders.show', $trader) }}" class="form-row align-items-end mb-3">
    <input type="hidden" name="tab" value="accounts">
    <div class="form-group col-md-3">
        <label class="small mb-1">Email</label>
        <input type="text" name="email" value="{{ request('email') }}" class="form-control form-control-sm" placeholder="Search email">
    </div>
    <div class="form-group col-md-2">
        <label class="small mb-1">Game</label>
        <select name="game_id" class="form-control form-control-sm">
            <option value="">All games</option>
            @foreach ($filterOptions['games'] as $game)
                <option value="{{ $game->id }}" {{ (string) request('game_id') === (string) $game->id ? 'selected' : '' }}>{{ $game->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label class="small mb-1">Purchase Order</label>
        <select name="purchase_order_id" class="form-control form-control-sm">
            <option value="">All POs</option>
            @foreach ($filterOptions['orders'] as $order)
                <option value="{{ $order->id }}" {{ (string) request('purchase_order_id') === (string) $order->id ? 'selected' : '' }}>{{ $order->po_number }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label class="small mb-1">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
    </div>
    <div class="form-group col-md-2">
        <label class="small mb-1">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
    </div>
    <div class="form-group col-md-1">
        <button type="submit" class="btn btn-sm btn-primary btn-block">Filter</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Game</th>
                <th>Purchase Order</th>
                <th>Purchase Date</th>
                <th class="text-right">Original Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($accounts as $account)
                <tr>
                    <td>{{ $account->id }}</td>
                    <td>{{ $account->mail }}</td>
                    <td>{{ $account->game?->title ?? '—' }}</td>
                    <td>
                        @if ($account->purchaseOrder)
                            <a href="{{ route('manager.purchase-orders.show', $account->purchase_order_id) }}">{{ $account->purchaseOrder->po_number }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $account->purchase_date?->toDateString() }}</td>
                    <td class="text-right">{{ number_format((float) $account->original_cost, 2) }}</td>
                </tr>
            @empty
                {{-- Empty state --}}
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No accounts match these filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $accounts->links('vendor.pagination.bootstrap-5') }}
