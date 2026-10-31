@extends('layouts.admin')

@section('title', 'Gift Cards Sell Log')

@section('content_header_title', 'Gift Cards')
@section('content_header_subtitle', 'Sell log')

@section('content_body')
{{-- Filters (GET, so every view can be bookmarked or shared) --}}
<form method="GET" action="{{ route('manager.gift-cards.sell-log') }}" class="form-inline flex-wrap mb-3">
    <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm mr-2 mb-2" placeholder="Buyer phone, name or exact code" aria-label="Search">
    <label class="small text-muted mr-1 mb-2" for="giftLogStart">From</label>
    <input type="date" id="giftLogStart" name="start_date" value="{{ $filters['start_date'] }}" class="form-control form-control-sm mr-2 mb-2">
    <label class="small text-muted mr-1 mb-2" for="giftLogEnd">To</label>
    <input type="date" id="giftLogEnd" name="end_date" value="{{ $filters['end_date'] }}" class="form-control form-control-sm mr-2 mb-2">
    <select name="store_profile_id" class="form-control form-control-sm mr-2 mb-2" aria-label="Store">
        <option value="">All stores</option>
        @foreach ($storeProfiles as $store)
            <option value="{{ $store->id }}" @selected($filters['store_profile_id'] === $store->id)>{{ $store->name }}</option>
        @endforeach
    </select>
    <select name="card_category_id" class="form-control form-control-sm mr-2 mb-2" aria-label="Category">
        <option value="">All categories</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected($filters['card_category_id'] === $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select name="source" class="form-control form-control-sm mr-2 mb-2" aria-label="Source">
        <option value="all" @selected($filters['source'] === 'all')>Local &amp; Next Load</option>
        <option value="manual" @selected($filters['source'] === 'manual')>Local codes</option>
        <option value="next_load" @selected($filters['source'] === 'next_load')>Next Load</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary mr-2 mb-2">Filter</button>
    <a href="{{ route('manager.gift-cards.sell-log') }}" class="btn btn-sm btn-secondary mb-2">Today</a>
</form>

{{-- Totals for the filtered sales --}}
<div class="row">
    <div class="col-6 col-md-3 mb-3">
        <div class="card mb-0"><div class="card-body py-2">
            <div class="small text-muted">Cards sold</div>
            <div class="h5 mb-0">{{ number_format($totals['count']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="card mb-0"><div class="card-body py-2">
            <div class="small text-muted">Revenue</div>
            <div class="h5 mb-0">{{ number_format($totals['revenue'], 2) }}</div>
        </div></div>
    </div>
    @if ($showCost)
        <div class="col-6 col-md-3 mb-3">
            <div class="card mb-0"><div class="card-body py-2">
                <div class="small text-muted">Cost</div>
                <div class="h5 mb-0">{{ number_format($totals['cost'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="card mb-0"><div class="card-body py-2">
                <div class="small text-muted">Profit</div>
                <div class="h5 mb-0">{{ number_format($totals['revenue'] - $totals['cost'], 2) }}</div>
            </div></div>
        </div>
    @endif
</div>

{{-- Sales table --}}
<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Seller</th>
                    <th>Store</th>
                    <th>Category</th>
                    @if ($showCode)
                        <th>Code</th>
                    @endif
                    <th>Source</th>
                    <th class="text-right">Price</th>
                    <th>Buyer phone</th>
                    <th>Buyer name</th>
                    <th>POS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td class="small text-nowrap">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        {{-- Shop orders have no staff seller --}}
                        @if ($order->isWebsiteSale())
                            <td>Website</td>
                        @else
                            <td class="{{ $order->seller ? '' : 'text-danger' }}">{{ $order->seller?->name ?? 'Maybe deleted' }}</td>
                        @endif
                        <td>{{ $order->storeProfile?->name ?? '—' }}</td>
                        <td>{{ $order->card?->category?->name ?? '—' }}</td>
                        @if ($showCode)
                            <td class="small text-monospace">{{ $order->card?->code ?? '—' }}</td>
                        @endif
                        <td>
                            @if ($order->card?->isFromNextLoad())
                                <span class="badge badge-info">Next Load</span>
                            @else
                                <span class="badge badge-secondary">Local</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $order->price }}</td>
                        <td>{{ $order->buyer_phone }}</td>
                        <td>{{ $order->buyer_name }}</td>
                        <td>
                            @if ($order->pos_order_id)
                                <span class="badge badge-success" title="POS Order ID: {{ $order->pos_order_id }}">POS</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    {{-- Empty state --}}
                    <tr>
                        <td colspan="{{ $showCode ? 11 : 10 }}" class="text-center text-muted py-4">
                            <i class="bi bi-gift d-block mb-2" style="font-size: 2rem;"></i>
                            No gift card sales match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2">
    {{ $orders->links('vendor.pagination.bootstrap-5') }}
</div>
@endsection
