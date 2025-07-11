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

        @if($categories->isEmpty())
            <div class="alert alert-warning" role="alert">
                No card categories with available codes are currently found.
            </div>
        @else
            <div class="row">
                @foreach($categories as $category)
                    <div class="col-12 col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-category-img">
                                @if($category->poster_image)
                                    <img style="max-width:100%" src="{{ asset($category->poster_image) }}" alt="{{ $category->name }}">
                                @else
                                    <div class="card-category-img" style="width:100%;height:250px;background-color:burlywood"></div>
                                @endif
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">{{ $category->name }}</h5>
                                <p class="card-text">
                                    ${{ number_format($category->price, 2) }} ({{ $category->cards->count() }} available)
                                </p>
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
                    <button type="button" class="btn btn-primary" onclick="submitOrder()">Submit</button>
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
            if (iti.isValidNumber()) {
                
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
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An unexpected error occurred. Please try again later.'
                        });
                        console.error('Error:', error);
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
