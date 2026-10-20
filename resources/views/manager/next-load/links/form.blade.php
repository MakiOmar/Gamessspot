@extends('layouts.admin')

@php($isEdit = $category !== null)
@php($modeLabels = array(
    'local_only' => 'Local only — sell codes added by staff, never buy',
    'local_first' => 'Local first — sell a local code, buy from Next Load when none is left',
    'supplier_only' => 'Next Load only — always buy the code at sale time',
))

@section('title', $isEdit ? 'Supplier link: '.$category->name : 'New supplier link')

@section('content_header_title', 'Next Load Supplier')
@section('content_header_subtitle', $isEdit ? $category->name : 'New link')

@section('content_body')
@include('manager.next-load.partials.nav')

<div class="row">
    <div class="col-lg-7">
        {{-- Link form: create (POST) or edit (PUT) --}}
        <div class="card">
            <div class="card-header"><strong>{{ $isEdit ? 'Supplier settings' : 'Link a category to a Next Load product' }}</strong></div>
            <div class="card-body">
                @if ($products === array())
                    <div class="alert alert-warning">The Next Load price list is unavailable right now, so links cannot be saved.</div>
                @endif
                <form id="nlLinkForm">
                    <div class="form-group">
                        <label for="nlProduct">Next Load product</label>
                        <select id="nlProduct" name="next_load_product_id" class="form-control" required>
                            <option value="">Select a product…</option>
                            @foreach ($products as $row)
                                <option value="{{ $row['product_id'] }}" @selected((int) $productId === $row['product_id'])>
                                    #{{ $row['product_id'] }} — {{ $row['product'] }} ({{ $row['price'] }})
                                </option>
                            @endforeach
                        </select>
                        @if ($productId && ! $product)
                            <small class="text-danger">Product #{{ $productId }} is no longer in the price list.</small>
                        @endif
                    </div>

                    @unless ($isEdit)
                        {{-- Existing category or a new one created from the product --}}
                        <div class="form-group">
                            <label>Category</label>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="nlTargetExisting" name="nl_target" value="existing" class="custom-control-input" checked>
                                <label class="custom-control-label" for="nlTargetExisting">Existing category</label>
                            </div>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="nlTargetNew" name="nl_target" value="new" class="custom-control-input">
                                <label class="custom-control-label" for="nlTargetNew">Create a new category</label>
                            </div>
                            <div id="nlExistingFields">
                                <select name="card_category_id" class="form-control">
                                    <option value="">Select a category…</option>
                                    @foreach ($categories as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }} ({{ number_format((float) $option->price, 2) }})</option>
                                    @endforeach
                                </select>
                                @if ($categories->isEmpty())
                                    <small class="text-muted">Every category is already linked; create a new one instead.</small>
                                @endif
                            </div>
                            <div id="nlNewFields" class="d-none">
                                <input type="text" name="name" class="form-control mb-2" maxlength="255" placeholder="Category name" value="{{ $product['product'] ?? '' }}">
                                <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="Sale price (local currency)">
                            </div>
                        </div>
                    @endunless

                    <div class="form-group">
                        <label for="nlMode">Sourcing mode</label>
                        <select id="nlMode" name="supplier_mode" class="form-control" required>
                            @foreach ($modes as $mode)
                                <option value="{{ $mode }}" @selected(($category->supplier_mode ?? 'local_first') === $mode)>{{ $modeLabels[$mode] ?? $mode }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" id="nlSaveBtn" @disabled($products === array())>Save</button>
                    <a href="{{ route('manager.next-load.products') }}" class="btn btn-secondary">Back</a>
                </form>
            </div>
        </div>
    </div>

    @if ($isEdit)
        <div class="col-lg-5">
            {{-- Stock, cost and supplier actions --}}
            <div class="card">
                <div class="card-header"><strong>Stock &amp; supplier</strong></div>
                <div class="card-body">
                    <p class="mb-1">Sale price: <strong>{{ number_format((float) $category->price, 2) }}</strong></p>
                    <p class="mb-1">Local codes available: <strong>{{ $category->available_count }}</strong></p>
                    <p class="mb-1">Supplier cost: <strong>{{ $category->supplier_cost ?? '—' }}</strong>
                        @if ($category->supplier_cost !== null)
                            (local {{ \App\Services\NextLoad\Money::toLocal($category->supplier_cost, config('services.nextload.exchange_rate', '1')) }})
                        @endif
                    </p>
                    <p class="small text-muted">Synced {{ $category->supplier_synced_at?->diffForHumans() ?? 'never' }}</p>

                    <button type="button" class="btn btn-sm btn-success" id="nlRestockBtn" @disabled(! $category->usesSupplier())>
                        <i class="bi bi-cart-plus"></i> Restock from Next Load
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="nlUnlinkBtn">
                        <i class="bi bi-x-circle"></i> Unlink
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@if ($isEdit)
    {{-- Supplier orders for this category --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <strong>Recent supplier orders</strong>
            <a href="{{ route('manager.next-load.orders.index') }}" class="small">All orders</a>
        </div>
        <div class="card-body p-0 table-responsive">
            @include('manager.next-load.orders.partials.table', array('orders' => $recentOrders))
        </div>
    </div>
@endif
@endsection

@push('js')
@include('manager.next-load.partials.helpers_js')
<script>
jQuery(function ($) {
    var isEdit = @json($isEdit);
    var saveUrl = @json($isEdit ? route('manager.next-load.links.update', $category) : route('manager.next-load.links.store'));

    if ($.fn.select2) {
        $('#nlProduct').select2({ width: '100%' });
    }

    // Toggle between linking an existing category and creating one
    $('input[name="nl_target"]').on('change', function () {
        var isNew = $(this).val() === 'new';
        $('#nlNewFields').toggleClass('d-none', !isNew);
        $('#nlExistingFields').toggleClass('d-none', isNew);
    });

    $('#nlLinkForm').on('submit', function (e) {
        e.preventDefault();
        var data = {
            next_load_product_id: $('#nlProduct').val(),
            supplier_mode: $('#nlMode').val()
        };
        if (isEdit) {
            data._method = 'PUT';
        } else if ($('input[name="nl_target"]:checked').val() === 'new') {
            data.name = $('input[name="name"]').val();
            data.price = $('input[name="price"]').val();
        } else {
            data.card_category_id = $('select[name="card_category_id"]').val();
        }
        NextLoadUi.post($('#nlSaveBtn'), saveUrl, data, null, true);
    });

    @if ($isEdit)
    $('#nlRestockBtn').on('click', function () {
        var $btn = $(this);
        Swal.fire({
            title: 'Restock from Next Load',
            text: 'Buys codes now and charges the supplier balance. Delivered codes are added to local stock.',
            icon: 'question',
            input: 'number',
            inputValue: 1,
            inputAttributes: { min: 1, max: @json($maxRestock), step: 1 },
            showCancelButton: true,
            confirmButtonText: 'Buy codes',
            inputValidator: function (value) {
                var qty = parseInt(value, 10);
                if (!qty || qty < 1 || qty > @json($maxRestock)) return 'Enter a quantity between 1 and {{ $maxRestock }}.';
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                NextLoadUi.post($btn, @json(route('manager.next-load.links.restock', $category)), { quantity: result.value });
            }
        });
    });

    $('#nlUnlinkBtn').on('click', function () {
        NextLoadUi.post($(this), @json(route('manager.next-load.links.destroy', $category)), { _method: 'DELETE' }, {
            title: 'Unlink from Next Load?',
            text: 'The category will sell local codes only. Existing supplier orders are kept.',
            icon: 'warning',
            confirmButtonText: 'Unlink',
            confirmButtonColor: '#d33'
        });
    });
    @endif
});
</script>
@endpush
