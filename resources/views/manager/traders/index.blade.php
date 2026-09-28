@extends('layouts.admin')

@section('title', 'Traders')

@section('content_header_title', 'Traders')
@section('content_header_subtitle', 'Suppliers, purchase orders & balances')

@section('content_body')
{{-- Filters and create action --}}
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
    <form method="GET" action="{{ route('manager.traders.index') }}" class="form-inline flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm mr-2 mb-2"
               placeholder="Name, phone or WhatsApp">
        <select name="status" class="form-control form-control-sm mr-2 mb-2">
            <option value="">All statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary mr-2 mb-2">Filter</button>
        <a href="{{ route('manager.traders.index') }}" class="btn btn-sm btn-secondary mb-2">Reset</a>
    </form>

    @can('manage-traders')
        <button type="button" class="btn btn-success btn-sm mb-2" id="createTraderBtn">
            <i class="bi bi-plus-lg"></i> New Trader
        </button>
    @endcan
</div>

{{-- Traders table with computed ledger totals --}}
<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>WhatsApp</th>
                    <th class="text-right">Purchases</th>
                    <th class="text-right">Payments</th>
                    <th class="text-right">Balance Due</th>
                    <th class="text-center">POs</th>
                    <th class="text-center">Accounts</th>
                    <th>Status</th>
                    <th style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($traders as $trader)
                    @php($totals = $ledgerTotals[$trader->id])
                    <tr>
                        <td><a href="{{ route('manager.traders.show', $trader) }}">{{ $trader->name }}</a></td>
                        <td>{{ $trader->phone ?: '—' }}</td>
                        <td>{{ $trader->whatsapp ?: '—' }}</td>
                        <td class="text-right">{{ number_format($totals['purchases'], 2) }}</td>
                        <td class="text-right">{{ number_format($totals['payments'], 2) }}</td>
                        <td class="text-right font-weight-bold {{ $totals['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($totals['balance'], 2) }}
                        </td>
                        <td class="text-center">{{ $totals['purchase_orders'] }}</td>
                        <td class="text-center">{{ $totals['accounts'] }}</td>
                        <td>
                            <span class="badge badge-{{ $trader->isActive() ? 'success' : 'secondary' }}">{{ ucfirst($trader->status) }}</span>
                        </td>
                        <td>
                            <a href="{{ route('manager.traders.show', $trader) }}" class="btn btn-xs btn-outline-primary">View</a>
                            @can('manage-traders')
                                <button type="button" class="btn btn-xs btn-outline-secondary edit-trader"
                                        data-trader="{{ json_encode($trader->only(array('id', 'name', 'phone', 'whatsapp', 'notes', 'status'))) }}">
                                    Edit
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    {{-- Empty state --}}
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-truck d-block mb-2" style="font-size: 2rem;"></i>
                            No traders found. {{ request()->hasAny(array('search', 'status')) ? 'Try clearing the filters.' : 'Create your first trader to start recording purchases.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2">
    {{ $traders->links('vendor.pagination.bootstrap-5') }}
</div>

@can('manage-traders')
    @include('manager.traders.partials.trader_modal')
@endcan
@endsection

@push('js')
@include('manager.traders.partials.helpers_js')
@can('manage-traders')
    @include('manager.traders.partials.trader_modal_js')
@endcan
@endpush
