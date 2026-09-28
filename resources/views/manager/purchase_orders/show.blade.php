@extends('layouts.admin')

@section('title', $order->po_number)

@section('content_header_title', 'Purchase Order ' . $order->po_number)
@section('content_header_subtitle', 'Traders')

@section('content_body')
{{-- Cancelled banner --}}
@if ($order->isCancelled())
    <div class="alert alert-secondary">
        <strong>Voided</strong> on {{ $order->cancelled_at?->toDateTimeString() }} by {{ $order->canceller?->name ?? '—' }}.
        Reason: {{ $order->cancellation_reason }}
    </div>
@endif

{{-- Order info --}}
<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between">
            <div class="mb-2">
                <a href="{{ route('manager.traders.show', array('trader' => $order->trader_id, 'tab' => 'purchase-orders')) }}" class="small">
                    <i class="bi bi-arrow-left"></i> Back to trader
                </a>
                <h4 class="mt-1 mb-2">
                    @if ($order->isCancelled())<del>{{ $order->po_number }}</del>@else{{ $order->po_number }}@endif
                    <span class="badge badge-{{ $order->isCancelled() ? 'secondary' : 'success' }} align-middle">{{ ucfirst($order->status) }}</span>
                </h4>
                <dl class="row small mb-0">
                    <dt class="col-sm-4">Trader</dt>
                    <dd class="col-sm-8"><a href="{{ route('manager.traders.show', $order->trader_id) }}">{{ $order->trader?->name }}</a></dd>
                    <dt class="col-sm-4">Purchase date</dt>
                    <dd class="col-sm-8">{{ $order->purchase_date?->toDateString() }}</dd>
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">{{ $order->created_at?->toDateTimeString() }} by {{ $order->creator?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Last modified</dt>
                    <dd class="col-sm-8">{{ $order->updated_at?->toDateTimeString() }} by {{ $order->updater?->name ?? '—' }}</dd>
                    @if ($order->notes)
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">{{ $order->notes }}</dd>
                    @endif
                </dl>
            </div>
            <div class="text-right mb-2">
                <div class="small text-muted">Total accounts</div>
                <div class="h4">{{ $importedTotal }} / {{ $order->total_quantity }}</div>
                <div class="small text-muted">Total cost</div>
                <div class="h4">{{ number_format($order->total_cost, 2) }} EGP</div>
                @unless ($order->isCancelled())
                    @can('manage-traders')
                        <a href="{{ route('manager.purchase-orders.edit', $order) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
                    @endcan
                    @can('void-trader-transactions')
                        @if ($importedTotal === 0)
                            <button type="button" class="btn btn-sm btn-outline-danger" id="voidOrderBtn"><i class="bi bi-x-circle"></i> Void</button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Orders with imported accounts cannot be voided">
                                <i class="bi bi-x-circle"></i> Void
                            </button>
                        @endif
                    @endcan
                @endunless
            </div>
        </div>
    </div>
</div>

{{-- Game lines with import progress --}}
<div class="card">
    <div class="card-header"><strong>Games</strong></div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Game</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Cost / account</th>
                    <th class="text-right">Line total</th>
                    <th style="min-width: 220px;">Imported</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->items as $item)
                    @php($percent = $item->quantity > 0 ? min(100, round($item->accounts_count / $item->quantity * 100)) : 0)
                    <tr>
                        <td>{{ $item->game?->title ?? '—' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->cost_per_account, 2) }}</td>
                        <td class="text-right">{{ number_format($item->total_cost, 2) }}</td>
                        <td>
                            <div class="small">Imported {{ $item->accounts_count }} / {{ $item->quantity }}</div>
                            <div class="progress progress-sm">
                                <div class="progress-bar {{ $percent >= 100 ? 'bg-success' : 'bg-info' }}" role="progressbar"
                                     style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    {{-- Empty state --}}
                    <tr><td colspan="5" class="text-center text-muted py-3">No game lines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Linked accounts, searchable by email --}}
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <strong>Accounts</strong>
        <form method="GET" action="{{ route('manager.purchase-orders.show', $order) }}" class="form-inline">
            <input type="text" name="email" value="{{ request('email') }}" class="form-control form-control-sm mr-2" placeholder="Search by email">
            <button type="submit" class="btn btn-sm btn-primary mr-1">Search</button>
            @if (request()->filled('email'))
                <a href="{{ route('manager.purchase-orders.show', $order) }}" class="btn btn-sm btn-secondary">Clear</a>
            @endif
        </form>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Game</th>
                    <th>Purchase date</th>
                    <th class="text-right">Original cost</th>
                    <th>Added</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td>{{ $account->id }}</td>
                        <td>{{ $account->mail }}</td>
                        <td>{{ $account->game?->title ?? '—' }}</td>
                        <td>{{ $account->purchase_date?->toDateString() }}</td>
                        <td class="text-right">{{ number_format((float) $account->original_cost, 2) }}</td>
                        <td>{{ $account->created_at?->toDateTimeString() }}</td>
                    </tr>
                @empty
                    {{-- Empty state --}}
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            {{ request()->filled('email') ? 'No accounts match this email.' : 'No accounts imported for this purchase order yet.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $accounts->links('vendor.pagination.bootstrap-5') }}
@endsection

@push('js')
@include('manager.traders.partials.helpers_js')
@can('void-trader-transactions')
<script>
jQuery(function ($) {
    // Void purchase order (admin only, requires a reason)
    $('#voidOrderBtn').on('click', function () {
        TraderUi.confirmVoid(@js(route('manager.purchase-orders.void', $order)), 'purchase order ' + @js($order->po_number), function () {
            setTimeout(function () { location.reload(); }, 600);
        });
    });
});
</script>
@endcan
@endpush
