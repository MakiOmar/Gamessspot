@extends('layouts.admin')

@section('title', 'Trader: ' . $trader->name)

@section('content_header_title', $trader->name)
@section('content_header_subtitle', 'Trader profile')

@section('content_body')
{{-- Trader header: contact info, balance and quick actions --}}
<div class="card">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start">
        <div class="mb-2">
            <a href="{{ route('manager.traders.index') }}" class="small"><i class="bi bi-arrow-left"></i> All traders</a>
            <h4 class="mb-1 mt-1">
                {{ $trader->name }}
                <span class="badge badge-{{ $trader->isActive() ? 'success' : 'secondary' }} align-middle">{{ ucfirst($trader->status) }}</span>
            </h4>
            <div class="text-muted small">
                <i class="bi bi-telephone"></i> {{ $trader->phone ?: '—' }}
                <span class="mx-2">|</span>
                <i class="bi bi-whatsapp"></i>
                @if ($trader->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $trader->whatsapp) }}" target="_blank" rel="noopener">{{ $trader->whatsapp }}</a>
                @else
                    —
                @endif
            </div>
            @if ($trader->notes)
                <div class="small mt-1">{{ $trader->notes }}</div>
            @endif
        </div>
        <div class="text-right mb-2">
            <div class="small text-muted">Balance Due</div>
            <div class="h3 mb-2 {{ $totals['balance'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totals['balance'], 2) }} EGP</div>
            @can('manage-traders')
                <button type="button" class="btn btn-sm btn-outline-secondary edit-trader"
                        data-trader="{{ json_encode($trader->only(array('id', 'name', 'phone', 'whatsapp', 'notes', 'status'))) }}">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                @if ($trader->isActive())
                    <a href="{{ route('manager.purchase-orders.create', array('trader' => $trader->id)) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> New Purchase Order
                    </a>
                @endif
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="bi bi-cash-coin"></i> Add Payment
                </button>
            @endcan
        </div>
    </div>
</div>

{{-- Tabs (server-rendered, each tab is its own request) --}}
@php
    $tabLabels = array(
        'overview' => 'Overview',
        'purchase-orders' => 'Purchase Orders',
        'accounts' => 'Accounts',
        'payments' => 'Payments',
        'statement' => 'Statement',
    );
@endphp
<ul class="nav nav-tabs">
    @foreach ($tabLabels as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('manager.traders.show', array('trader' => $trader->id, 'tab' => $key)) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="border border-top-0 p-3 bg-white mb-3">
    @include('manager.traders.tabs.' . $tab)
</div>

@can('manage-traders')
    @include('manager.traders.partials.trader_modal')
    @include('manager.traders.partials.payment_modal')
@endcan
@endsection

@push('js')
@include('manager.traders.partials.helpers_js')
@can('manage-traders')
    @include('manager.traders.partials.trader_modal_js')
    @include('manager.traders.partials.payment_modal_js')
@endcan
@stack('trader_tab_js')
@endpush
