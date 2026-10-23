@extends('layouts.admin')

@php($isEdit = $category !== null)
@php($modeLabels = array(
    'local_only' => 'Local only — sell codes added by staff, never buy',
    'local_first' => 'Local first — sell a local code, buy from Next Load when none is left',
    'supplier_only' => 'Next Load only — always buy the code at sale time',
))
@php($modeHelp = array(
    'local_only' => array(
        'Every sale uses a code staff added to this category.',
        'When none is left the sale fails with "No active code found". Next Load is never charged.',
        'The link and hourly price sync are kept, so you can switch back at any time. Restock is disabled.',
    ),
    'local_first' => array(
        'A sale uses a local code first (staff-added or restocked).',
        'When none is left, one code is bought from Next Load during the sale and returned like a local code (cost = supplier price × exchange rate).',
        'Shop and POS show local stock, then how many codes the Next Load balance can buy (max 99).',
    ),
    'supplier_only' => array(
        'Every sale buys one code from Next Load; local codes in this category are not sold.',
        'If Next Load is processing, POS/shop retry automatically and a manager sale gets the code added to stock when it arrives.',
        'The category is hidden from sale when the balance is too low or Next Load is unreachable.',
    ),
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
                                <option value="{{ $row['product_id'] }}" data-name="{{ $row['product'] }}" @selected((int) $productId === $row['product_id'])>
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
                        {{-- How a sale is sourced in the selected mode (toggled by JS) --}}
                        @foreach ($modeHelp as $mode => $lines)
                            <ul class="small text-muted mt-2 mb-0 pl-3 nl-mode-help @if (($category->supplier_mode ?? 'local_first') !== $mode) d-none @endif" data-mode="{{ $mode }}">
                                @foreach ($lines as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @endforeach
                        <small class="d-block text-muted mt-1">
                            Supplier purchases need Next Load enabled, the product still in the price list, and a supplier price (× exchange rate) not above the sale price.
                            Linking buys nothing by itself.
                        </small>
                    </div>

                    {{-- Caps automatic purchases so one product cannot use up the shared balance --}}
                    <div class="form-group">
                        <label for="nlDailyLimit">Daily Next Load limit <span class="text-muted font-weight-normal">(optional)</span></label>
                        <input type="number" id="nlDailyLimit" name="supplier_daily_limit" class="form-control" min="1" max="1000" step="1"
                               placeholder="No limit" value="{{ $category->supplier_daily_limit ?? '' }}">
                        <small class="text-muted">
                            Max codes bought from Next Load per day for sales of this category. The balance is shared by every linked product,
                            so a limit keeps one product from using it all. Local codes and Restock are not counted.
                        </small>
                    </div>

                    {{-- Generated artwork, shown on the sell screen and storefront when there is no poster --}}
                    <fieldset class="border rounded p-3 mb-3">
                        <legend class="w-auto px-2 h6 mb-0">Card artwork</legend>
                        @if ($category?->poster_image)
                            <p class="small text-muted">This category has a poster image, which is shown instead of the artwork.</p>
                        @endif
                        <div class="row">
                            <div class="col-sm-7">
                                <p class="small text-muted">Brand, value and flag are read from the product name. Override them only if the preview is wrong.</p>
                                <div class="form-group">
                                    <label for="nlArtBrand">Brand</label>
                                    <select id="nlArtBrand" name="art_brand" class="form-control nl-art-input">
                                        <option value="">Automatic</option>
                                        @foreach ($artBrands as $key => $label)
                                            <option value="{{ $key }}" @selected(($category->art_brand ?? null) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="nlArtCountry">Country flag</label>
                                    <select id="nlArtCountry" name="art_country" class="form-control nl-art-input">
                                        <option value="">Automatic</option>
                                        @foreach ($artCountries as $code => $label)
                                            <option value="{{ $code }}" @selected(($category->art_country ?? null) === $code)>{{ $label }} ({{ strtoupper($code) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="nlArtValue">Face value</label>
                                    <input type="text" id="nlArtValue" name="art_value" class="form-control nl-art-input" maxlength="20" placeholder="Automatic, e.g. $10 or 100 SAR" value="{{ $category->art_value ?? '' }}">
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <label class="d-block">Preview</label>
                                <div id="nlArtPreview" style="max-width:200px"></div>
                                <small id="nlArtEmpty" class="text-muted d-none">No face value found in the name. Enter one to show artwork.</small>
                            </div>
                        </div>
                    </fieldset>

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
                    <p class="mb-1">Bought from Next Load today: <strong>{{ $purchasedToday }}</strong>
                        @if ($category->supplier_daily_limit)
                            / {{ $category->supplier_daily_limit }} limit
                        @endif
                    </p>
                    <p class="mb-1" title="From the shared balance; other linked products spend the same money">
                        Can buy now: <strong>{{ $supplierStock }}</strong>
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

    // Show the explanation for the selected sourcing mode
    $('#nlMode').on('change', function () {
        var mode = $(this).val();
        $('.nl-mode-help').each(function () {
            $(this).toggleClass('d-none', $(this).data('mode') !== mode);
        });
    });

    // Artwork preview from the selected product name and overrides (debounced)
    var artTimer = null;
    var artRequest = null;
    function refreshArt() {
        var name = $('#nlProduct option:selected').data('name') || '';
        if (!name) {
            $('#nlArtPreview').empty();
            $('#nlArtEmpty').addClass('d-none');
            return;
        }
        if (artRequest) artRequest.abort();
        artRequest = $.get(@json(route('manager.next-load.links.art-preview')), {
            name: name,
            art_brand: $('#nlArtBrand').val(),
            art_country: $('#nlArtCountry').val(),
            art_value: $('#nlArtValue').val()
        }).done(function (res) {
            $('#nlArtPreview').html(res.html || '');
            $('#nlArtEmpty').toggleClass('d-none', !!res.html);
        }).fail(function (xhr) {
            if (xhr.statusText !== 'abort') NextLoadUi.ajaxError(xhr, 'Could not load the artwork preview.');
        });
    }
    function scheduleArt() {
        clearTimeout(artTimer);
        artTimer = setTimeout(refreshArt, 300);
    }
    $('#nlProduct, .nl-art-input').on('change', scheduleArt);
    $('#nlArtValue').on('input', scheduleArt);
    refreshArt();

    $('#nlLinkForm').on('submit', function (e) {
        e.preventDefault();
        var data = {
            next_load_product_id: $('#nlProduct').val(),
            supplier_mode: $('#nlMode').val(),
            supplier_daily_limit: $('#nlDailyLimit').val(),
            art_brand: $('#nlArtBrand').val(),
            art_country: $('#nlArtCountry').val(),
            art_value: $('#nlArtValue').val()
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
