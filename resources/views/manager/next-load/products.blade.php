@extends('layouts.admin')

@section('title', 'Next Load Products')

@section('content_header_title', 'Next Load Supplier')
@section('content_header_subtitle', 'Products & category links')

@section('content_body')
@include('manager.next-load.partials.nav')

{{-- Search the cached supplier price list --}}
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
    <form method="GET" action="{{ route('manager.next-load.products') }}" class="form-inline">
        <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm mr-2 mb-2" placeholder="Product name or id">
        <button type="submit" class="btn btn-sm btn-primary mr-2 mb-2">Search</button>
        <a href="{{ route('manager.next-load.products') }}" class="btn btn-sm btn-secondary mb-2">Reset</a>
    </form>
    <a href="{{ route('manager.next-load.links.create') }}" class="btn btn-sm btn-success mb-2">
        <i class="bi bi-link-45deg"></i> New link
    </a>
</div>

@if ($error)
    <div class="alert alert-danger">{{ $error }}</div>
@endif

<div class="card">
    <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Product id</th>
                    <th>Product</th>
                    <th class="text-right">Supplier price</th>
                    <th class="text-right">Local cost (x{{ $exchangeRate }})</th>
                    <th>Linked categories</th>
                    <th style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $product['product_id'] }}</td>
                        <td>{{ $product['product'] }}</td>
                        <td class="text-right">{{ $product['price'] }}</td>
                        <td class="text-right">{{ \App\Services\NextLoad\Money::toLocal($product['price'], $exchangeRate) }}</td>
                        <td>
                            @foreach ($links->get($product['product_id'], collect()) as $linked)
                                <a href="{{ route('manager.next-load.links.edit', $linked->id) }}" class="badge badge-primary">
                                    {{ $linked->name }} ({{ str_replace('_', ' ', $linked->supplier_mode) }})
                                </a>
                            @endforeach
                        </td>
                        <td>
                            <a href="{{ route('manager.next-load.links.create', array('product_id' => $product['product_id'])) }}" class="btn btn-xs btn-outline-primary">
                                Link / create category
                            </a>
                        </td>
                    </tr>
                @empty
                    {{-- Empty state --}}
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-box-seam d-block mb-2" style="font-size: 2rem;"></i>
                            {{ $search !== '' ? 'No products match your search.' : 'No products returned by Next Load. Check the connection on the Overview tab.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2">
    {{ $products->links('vendor.pagination.bootstrap-5') }}
</div>
@endsection
