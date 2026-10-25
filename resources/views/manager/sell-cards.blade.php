@extends('layouts.admin')
@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/intlTelInput.min.css') }}">
<style>
    label{
        display:block
    }
    div.iti{
        width: 100%
    }
    /* Brand tiles (mobile-first: 2 columns, wider screens fit more) */
    .sell-brands{
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 768px){
        .sell-brands{ grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    }
    .sell-brand{
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .35rem;
        padding: .75rem;
        border: 1px solid #dee2e6;
        border-radius: .75rem;
        background: #fff;
        color: inherit;
        text-align: center;
        cursor: pointer;
        transition: box-shadow .15s, transform .15s;
    }
    .sell-brand:hover, .sell-brand:focus{
        color: inherit;
        text-decoration: none;
        box-shadow: 0 .25rem .75rem rgba(0,0,0,.12);
        transform: translateY(-2px);
    }
    .sell-brand__logo{
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        aspect-ratio: 16 / 9;
        border-radius: .5rem;
    }
    .sell-brand__logo img{
        max-width: 55%;
        max-height: 60%;
        object-fit: contain;
    }
    .sell-brand__initial{
        color: #fff;
        font-size: 2rem;
        font-weight: 700;
    }
    .sell-brand__label{ font-weight: 600; }
    .sell-brand__count{ font-size: .85rem; color: #6c757d; }
    .sell-crumbs{
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .4rem;
    }
    .sell-crumbs__sep{ color: #6c757d; }
    /* Country picker inside the brand modal */
    .sell-countries{
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .5rem;
    }
    .sell-country{
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .65rem;
        border: 1px solid #dee2e6;
        border-radius: .5rem;
        color: inherit;
    }
    .sell-country:hover, .sell-country:focus{
        color: inherit;
        text-decoration: none;
        background: #f8f9fa;
    }
    .sell-country__flag{
        width: 28px;
        height: 20px;
        object-fit: cover;
        border-radius: 3px;
        flex-shrink: 0;
    }
    .sell-country__flag--blank{ background: #e9ecef; }
    .sell-country__label{ flex: 1; min-width: 0; }
</style>
@endpush
@section('content')
    <div class="container">
        <h1 class="mb-4">Card Categories</h1>
        <div class="mb-4">
            <form method="GET" action="{{ route('manager.sell-cards.search') }}" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" placeholder="Search category..." value="{{ $query ?? '' }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        @php($sellUrl = route('manager.sell-cards'))
        @if($categories->isEmpty() && empty($groups))
            <div class="alert alert-warning" role="alert">
                No card categories with available codes (local or from Next Load) are currently found.
            </div>
        @elseif($browsing)
            {{-- Step 1: pick a brand; brands with several countries open a country modal --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Choose a brand</h2>
                <a href="{{ $sellUrl }}?all=1" class="btn btn-outline-secondary btn-sm">All cards</a>
            </div>
            <div class="sell-brands">
                @foreach($groups as $group)
                    @php($single = count($group['countries']) === 1)
                    @if($single)
                        <a class="sell-brand" href="{{ $sellUrl }}?{{ http_build_query(['brand' => $group['brand'], 'country' => $group['countries'][0]['key']]) }}">
                    @else
                        <button type="button" class="sell-brand" data-toggle="modal" data-target="#brandModal-{{ $group['brand'] }}">
                    @endif
                        {{-- Logo on the brand colour, or the label when no logo file exists --}}
                        <span class="sell-brand__logo" style="background-color: {{ $group['color'] }}">
                            @if($group['logo_url'])
                                <img src="{{ $group['logo_url'] }}" alt="" loading="lazy">
                            @else
                                <span class="sell-brand__initial">{{ mb_substr($group['label'], 0, 1) }}</span>
                            @endif
                        </span>
                        <span class="sell-brand__label">{{ $group['label'] }}</span>
                        <span class="sell-brand__count">{{ $group['count'] }} {{ \Illuminate\Support\Str::plural('card', $group['count']) }}</span>
                    @if($single)
                        </a>
                    @else
                        </button>
                    @endif
                @endforeach
            </div>
        @else
            {{-- Brand/country breadcrumb above a filtered grid --}}
            @if($brandGroup)
                <nav aria-label="Gift card filter" class="sell-crumbs mb-3">
                    <a href="{{ $sellUrl }}">All brands</a>
                    <span class="sell-crumbs__sep">&rsaquo;</span>
                    <strong>{{ $brandGroup['label'] }}</strong>
                    <span class="sell-crumbs__sep">&rsaquo;</span>
                    <strong>{{ $country ? \App\Services\CardArt\CardArtCatalog::countryLabel($country) : 'All countries' }}</strong>
                    @if(count($brandGroup['countries']) > 1)
                        <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#brandModal-{{ $brandGroup['brand'] }}">Change country</button>
                    @endif
                </nav>
            @elseif(blank($query))
                <div class="mb-3"><a href="{{ $sellUrl }}">&lsaquo; All brands</a></div>
            @endif
        @endif

        @if($categories->isEmpty() && ! $browsing && ! empty($groups))
            <div class="alert alert-info" role="alert">No cards match this selection.</div>
        @elseif(! $browsing && $categories->isNotEmpty())
            <div class="row">
                @foreach($categories as $category)
                    <div class="col-12 col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-category-img">
                                @if($category->poster_image)
                                    <img style="max-width:100%" src="{{ asset($category->poster_image) }}" alt="{{ $category->name }}">
                                @elseif ($category->card_art)
                                    {{-- No poster: generated artwork from the product name --}}
                                    <x-card-art :art="$category->card_art" />
                                @else
                                    <div class="card-category-img" style="width:100%;height:250px;background-color:burlywood"></div>
                                @endif
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">
                                    {{ $category->name }}
                                    {{-- Codes may be bought from Next Load at sale time --}}
                                    @if ($category->usesSupplier())
                                        <span class="badge bg-info text-dark" title="Bought from Next Load when no local code is left">Supplier</span>
                                    @endif
                                </h5>
                                <p class="card-text">
                                    ${{ number_format($category->price, 2) }} ({{ $category->available_count }} available)
                                </p>
                                {{-- Supplier counts all draw on one Next Load balance --}}
                                @if ($category->usesSupplier())
                                    <p class="small text-muted mb-2">When local codes run out, the count is what the shared Next Load balance can buy (shared with other supplier cards).</p>
                                @endif
                                <button type="button" class="btn btn-primary" onclick="openOrderForm({{ $category->id }}, '{{ $category->name }}')">
                                    Order Code
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Country pickers: one per brand that is sold in several countries --}}
    @foreach($groups as $group)
        @continue(count($group['countries']) < 2)
        <div class="modal fade" id="brandModal-{{ $group['brand'] }}" tabindex="-1" aria-labelledby="brandModalLabel-{{ $group['brand'] }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="brandModalLabel-{{ $group['brand'] }}">{{ $group['label'] }}: choose country</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="sell-countries">
                            @foreach($group['countries'] as $option)
                                <a class="sell-country" href="{{ $sellUrl }}?{{ http_build_query(['brand' => $group['brand'], 'country' => $option['key']]) }}">
                                    @if($option['flag_url'])
                                        <img class="sell-country__flag" src="{{ $option['flag_url'] }}" alt="" loading="lazy">
                                    @else
                                        <span class="sell-country__flag sell-country__flag--blank" aria-hidden="true"></span>
                                    @endif
                                    <span class="sell-country__label">{{ $option['label'] }}</span>
                                    <span class="badge badge-light">{{ $option['count'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ $sellUrl }}?{{ http_build_query(['brand' => $group['brand']]) }}" class="btn btn-outline-secondary btn-sm">All {{ $group['label'] }} cards</a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach


    <!-- Order Form Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Order for <span id="categoryName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="orderForm">
                        @csrf
                        <input type="hidden" name="card_category_id" id="card_category_id">
                        <input type="hidden" name="platform" value="card">
                        <div class="form-group">
                            <label>Store Profile</label>
                            @if(auth()->user()->roles->contains('name', 'admin') || auth()->user()->roles->contains('name', 'account manager'))
                                <select class="form-control" name="store_profile_id" required>
                                    @foreach ($storeProfiles as $profile)
                                        <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="store_profile_id" value="{{ auth()->user()->store_profile_id }}">
                                <p>{{ auth()->user()->storeProfile->name ?? 'No Store Profile Assigned' }}</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Client Phone:</label>
                            <input type="text" class="form-control" name="buyer_phone" id="buyer_phone" placeholder="Enter Client Phone" required>
                        </div>
                        <div class="form-group">
                            <label>Client Name:</label>
                            <input type="text" class="form-control" name="buyer_name" id="buyer_name" placeholder="Client name" required>
                        </div>
                        <div class="form-group">
                            <label>Enter Price:</label>
                            <input type="number" class="form-control" name="price" placeholder="Enter Price" required>
                        </div>
                        <div id="orderSuccessMessage" class="alert alert-success d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitOrderBtn" onclick="submitOrder()">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Code details -->
    <div class="modal fade" id="codeDetails" tabindex="-1" aria-labelledby="codeDetailsLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="codeDetailsLabel">Order Created!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Copy the code below:</p>
                    <input type="text" id="orderCodeInput" class="form-control" readonly>
                    <button id="copyOrderCodeBtn" class="btn btn-primary mt-3">Copy Code</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal" id="closeOrderModalBtn">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        jQuery(document).ready(function ($) {
            const $searchInput = $('input[name="q"]');

            $searchInput.on('input', function () {
                if ($(this).val().trim() === '') {
                    // إعادة التوجيه للصفحة الأصلية إذا أصبح الحقل فارغًا
                    setTimeout(function () {
                        window.location.href = "{{ route('manager.sell-cards') }}";
                    }, 300); // التأخير اختياري
                }
            });
        });
    </script>

    <script src="{{ asset('assets/js/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('assets/js/utils.js') }}"></script>
    <script>
        jQuery(document).ready(function($) {
            const input = document.querySelector("#buyer_phone");
            const iti = window.intlTelInput(input, {
                initialCountry: "auto", // Automatically detect the user's country
                separateDialCode: true, // Show country code separately
                geoIpLookup: function(success, failure) {
                    // Automatically detect the user's country using an API
                    fetch("https://ipinfo.io/json", {mode: "cors"})
                    .then(response => response.json())
                    .then((response) => {
                        const countryCode = (response && response.country) ? response.country : "eg";
                        success(countryCode);
                    })
                    .catch(() => failure());
                }
            });
            window.iti = iti;
            let buyPhoneRequest = false;
            $('#buyer_phone').on('focusout', function () {
                if (buyPhoneRequest) return;
                buyPhoneRequest = true;

                const query = $(this).val().trim();
                if (query.length > 0) {
                    $.ajax({
                        url: '{{ route("manager.buyer.name") }}',
                        type: 'GET',
                        data: { search: query },
                        dataType: 'json',
                        success: function (response) {
                            if (response.length > 0) {
                                $('#buyer_name').val(response[0].buyer_name);
                            } else {
                                $('#buyer_name').val('');
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                        },
                        complete: function () {
                            buyPhoneRequest = false;
                        }
                    });
                }
            });

        });

        function isValidPhoneNumberForOrder(itiInstance) {
            const phoneNumber = itiInstance.getNumber();
            const countryData = typeof itiInstance.getSelectedCountryData === 'function'
                ? itiInstance.getSelectedCountryData()
                : null;

            if (countryData && countryData.iso2 === 'sa') {
                const digits = phoneNumber.replace(/\D/g, '');
                if (/^9665\d{8}$/.test(digits)) {
                    return true;
                }
            }

            return itiInstance.isValidNumber();
        }

        function openOrderForm(categoryId, categoryName) {
            document.getElementById('card_category_id').value = categoryId;
            document.getElementById('categoryName').textContent = categoryName;
            document.getElementById('orderForm').reset();
            jQuery('#orderModal').modal('show');
        }
        function showOrderModal(code) {
            // Set the code in the input field
            const inputField = document.getElementById('orderCodeInput');
            inputField.value = code;

            // Show the modal
            jQuery('#codeDetails').modal('show');

            // Handle the copy button click
            document.getElementById('copyOrderCodeBtn').addEventListener('click', () => {
                navigator.clipboard.writeText(code).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'The code has been copied to your clipboard.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                });
            });

            // Add a listener for when the modal is closed
            document.getElementById('closeOrderModalBtn').addEventListener('click', () => {
                jQuery('#codeDetails').modal('hide');
            });
        }
        $('#codeDetails').on('hidden.bs.modal', function () {
            location.reload();
        });
        function submitOrder() {
            let formElement = document.getElementById('orderForm');
            let formData = new FormData(formElement);
            let phoneNumber = iti.getNumber();
            // Manually replace the `buyer_phone` in the FormData
            formData.set('buyer_phone', phoneNumber);
            if (isValidPhoneNumberForOrder(iti)) {
                const submitBtn = document.getElementById('submitOrderBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Processing…';

                jQuery.ajax({
                    url: "{{ route('manager.orders.sell.card') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data.success) {
                            document.getElementById('orderSuccessMessage').textContent = data.message + ' Code: ' + data.code;
                            document.getElementById('orderSuccessMessage').classList.remove('d-none');
                            showOrderModal(data.code);
                            jQuery('#orderModal').modal('hide');
                        } else if (data.pending) {
                            // 202: Next Load is still processing; the code goes to stock when delivered.
                            jQuery('#orderModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'Supplier processing',
                                text: data.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        const res = xhr.responseJSON || {};
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: res.message || 'An unexpected error occurred. Please try again later.'
                        });
                        console.error('Error:', error);
                    },
                    complete: function () {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit';
                    }
                });
            } else {
                Swal.fire({
                    title: 'Invalid Phone Number',
                    text: 'Please enter a valid phone number.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
            
        }
    </script>
@endpush
