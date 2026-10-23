@extends('layouts.admin')

@section('title', 'Next Load Supplier')

@section('content_header_title', 'Next Load Supplier')
@section('content_header_subtitle', 'Gift card supplier balance, products and orders')

@section('content_body')
@include('manager.next-load.partials.nav')

{{-- Configuration warning: the integration stays off until env credentials exist --}}
@if (! $configured)
    <div class="alert alert-warning">
        Next Load credentials are not configured. Set <code>NEXTLOAD_EMAIL</code>, <code>NEXTLOAD_PASSWORD</code> and
        <code>NEXTLOAD_SECURITY_CODE</code> in <code>.env</code> (see docs/NEXTLOAD.md).
    </div>
@elseif (! $enabled)
    <div class="alert alert-info">
        Credentials are set but sales do not use Next Load until <code>NEXTLOAD_ENABLED=true</code>.
    </div>
@endif

{{-- Summary cards --}}
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Balance</div>
                @php($isLow = $balance !== null && \App\Services\NextLoad\Money::toMillis($lowBalance) > 0 && \App\Services\NextLoad\Money::compare($balance, $lowBalance) < 0)
                <div class="h3 mb-1 {{ $isLow ? 'text-danger' : '' }}">{{ $balance ?? '—' }}</div>
                <div class="small text-muted">
                    @if ($balance === null)
                        Not available (check the connection).
                    @elseif ($isLow)
                        Below the low-balance threshold ({{ $lowBalance }}).
                    @else
                        Shared by all linked products. Lowered after each purchase; re-read from Next Load every 15 minutes.
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Linked categories</div>
                <div class="h3 mb-1">{{ $linkedCount }}</div>
                <div class="small text-muted">
                    Price list synced: {{ $lastProductSync ? \Illuminate\Support\Carbon::parse($lastProductSync)->diffForHumans() : 'never' }}
                    · Exchange rate {{ $exchangeRate }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Supplier orders</div>
                <div class="small mt-1">
                    @foreach (array('complete' => 'success', 'in_progress' => 'info', 'pending_send' => 'warning', 'failed' => 'danger') as $status => $color)
                        <a href="{{ route('manager.next-load.orders.index', array('status' => $status)) }}" class="badge badge-{{ $color }} mr-1">
                            {{ str_replace('_', ' ', $status) }}: {{ $statusCounts[$status] ?? 0 }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Actions --}}
<div class="mb-3">
    <button type="button" class="btn btn-sm btn-outline-primary" id="nlTestBtn" @disabled(! $configured)>
        <i class="bi bi-plug"></i> Test connection
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="nlSyncBtn" @disabled(! $configured)>
        <i class="bi bi-arrow-repeat"></i> Refresh price list
    </button>
    <span class="small text-muted ml-2">API: {{ $baseUrl }}</span>
</div>

{{-- Linked categories: local stock vs what the shared balance can buy --}}
@if ($linked->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <strong>Linked categories</strong>
            <div class="small text-muted">
                "Can buy now" is calculated per product from the <strong>same shared balance</strong>: buying one product lowers the others.
                Numbers are not added together. Use a daily limit or Restock to set money aside for a product.
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Mode</th>
                        <th class="text-right">Supplier price</th>
                        <th class="text-right">Local codes</th>
                        <th class="text-right">Bought today</th>
                        <th class="text-right">Can buy now</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($linked as $row)
                        @php($category = $row['category'])
                        <tr>
                            <td><a href="{{ route('manager.next-load.links.edit', $category) }}">{{ $category->name }}</a></td>
                            <td>{{ str_replace('_', ' ', $category->supplier_mode) }}</td>
                            <td class="text-right">{{ $category->supplier_cost ?? '—' }}</td>
                            <td class="text-right">{{ $category->available_count }}</td>
                            <td class="text-right">
                                {{ $row['purchased_today'] }}{{ $category->supplier_daily_limit ? ' / '.$category->supplier_daily_limit : '' }}
                            </td>
                            <td class="text-right {{ $category->usesSupplier() && $row['can_buy'] === 0 ? 'text-danger' : '' }}">
                                {{ $category->usesSupplier() ? $row['can_buy'] : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Recent supplier orders --}}
<div class="card">
    <div class="card-header"><strong>Recent supplier orders</strong></div>
    <div class="card-body p-0 table-responsive">
        @include('manager.next-load.orders.partials.table', array('orders' => $recentOrders))
    </div>
</div>
@endsection

@push('js')
@include('manager.next-load.partials.helpers_js')
<script>
jQuery(function ($) {
    $('#nlTestBtn').on('click', function () {
        NextLoadUi.post($(this), @json(route('manager.next-load.test')), {}, null, false);
    });
    $('#nlSyncBtn').on('click', function () {
        NextLoadUi.post($(this), @json(route('manager.next-load.sync-products')), {}, null, true);
    });
});
</script>
@endpush
