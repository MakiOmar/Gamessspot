@extends('layouts.admin')

@section('title', 'Next Load Orders')

@section('content_header_title', 'Next Load Supplier')
@section('content_header_subtitle', 'Supplier orders')

@section('content_body')
@include('manager.next-load.partials.nav')

{{-- Filters --}}
<form method="GET" action="{{ route('manager.next-load.orders.index') }}" class="form-inline flex-wrap mb-3">
    <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm mr-2 mb-2" placeholder="Reference, Next Load id or shop order">
    <select name="status" class="form-control form-control-sm mr-2 mb-2">
        <option value="">All statuses</option>
        @foreach ($statuses as $status)
            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', $status) }}</option>
        @endforeach
    </select>
    <select name="purpose" class="form-control form-control-sm mr-2 mb-2">
        <option value="">Sale &amp; restock</option>
        <option value="sale" @selected($filters['purpose'] === 'sale')>Sale</option>
        <option value="restock" @selected($filters['purpose'] === 'restock')>Restock</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary mr-2 mb-2">Filter</button>
    <a href="{{ route('manager.next-load.orders.index') }}" class="btn btn-sm btn-secondary mb-2">Reset</a>
</form>

<div class="card">
    <div class="card-body p-0 table-responsive">
        @include('manager.next-load.orders.partials.table', array('orders' => $orders))
    </div>
</div>
<div class="mt-2">
    {{ $orders->links('vendor.pagination.bootstrap-5') }}
</div>
@endsection
