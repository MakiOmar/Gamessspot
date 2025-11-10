@extends('layouts.admin')

@php
    $pageTitle = $pageTitle ?? 'Manager - Games';
    $pageHeading = $pageHeading ?? null;
    $pageDescription = $pageDescription ?? null;
    $showSearch = $showSearch ?? true;
@endphp

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/intlTelInput.min.css') }}">
<style>
    label{
        display:block
    }
    div.iti{
        width: 100%
    }
    .disabled{
        opacity: 0.4;
    }
    .game-card .card-body{
        padding: 5px;
        margin-bottom: 20px;
    }
    #gamesListings{
        max-width: 1200px;
    }
    #gamesListings .card-img-top{
        height: 250px; 
        background-size: contain; 
        background-position: center;
        background-repeat: no-repeat;
    }
</style>
@endpush
@section('title', $pageTitle)

@section('content')
    <div class="container mb-4">
        @if($pageHeading || $pageDescription)
            <div class="mb-3">
                @if($pageHeading)
                    <h1 class="h3 mb-1">{{ $pageHeading }}</h1>
                @endif
                @if($pageDescription)
                    <p class="text-muted mb-0">{{ $pageDescription }}</p>
                @endif
            </div>
        @endif
        {{-- Cache Indicator --}}
        @include('components.cache-indicator')
        
        @if($showSearch)
            <div class="row d-flex justify-content-center mt-3">
                <div class="col-md-6">
                    <input type="text" id="searchBox" class="form-control" placeholder="Search games...">
                </div>
            </div>
        @endif
    </div>
    <div class="container" id="gamesListings">
        <div class="row">
            @include('manager.partials.games_list', ['psGames' => $psGames, 'n' => $n])
        </div>
        {{--
        <div class="d-flex justify-content-center mt-4">
            {{ $psGames->links('vendor.pagination.bootstrap-5') }}
        </div>
        --}}
    </div>

    <!-- Game Modal -->
    <div class="modal fade" id="gameModal" tabindex="-1" aria-labelledby="gameModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gameModalLabel">Game Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-bs-target="#gameModal"></button>
                </div>
                <div class="modal-body">
                    <form id="orderForm">
                        <div class="form-group">
                            <label>Store Profile</label>
                            
                            @if(auth()->user()->roles->contains('name', 'admin')) <!-- Check if the user has the 'admin' role -->
                                <select class="form-control" name="store_profile_id" required>
                                    @foreach ($storeProfiles as $profile)
                                        <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="store_profile_id" value="{{ auth()->user()->store_profile_id ?? '0' }}">
                                <!-- Optionally display the store profile name, handling if storeProfile relation is null -->
                                <p>{{ auth()->user()->storeProfile->name ?? 'No Store Profile Assigned' }}</p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Client Phone:</label>
                            <input type="text" class="form-control" placeholder="Enter Client Phone" name="buyer_phone" id="buyer_phone" required>
                        </div>                        
                        <div class="form-group">
                            <label>Client Name:</label>
                            <input type="text" class="form-control" placeholder="Client name" id="buyer_name" name="buyer_name" required>
                        </div>
                        <div class="form-group">
                            <label>Enter Price:</label>
                            <input type="number" class="form-control" placeholder="Enter Price" name="price" required>
                        </div>
                        <input type="hidden" name="game_id" id="game_id">
                        <input type="hidden" name="type" id="game_type">
                        <input type="hidden" name="platform" id="game_platform">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="orderForm">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Details Modal -->
    <div class="modal fade" id="accountModal" tabindex="-1" role="dialog" aria-labelledby="accountModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalLabel">Account Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-bs-target="#accountModal"></button>
                </div>
                <div class="modal-body" id="accountModalBody">
                    <!-- Dynamic content for account details will go here -->
                    <div id="accountDetailsContainer">
                        <div>
                            <label class="flex-grow-1">Account Email:</label>
                            <div class="form-group d-flex align-items-center">
                                <input type="text" class="form-control flex-grow-2" id="accountEmail" readonly>
                                <button type="button" class="btn btn-outline-secondary ml-2" id="copyEmail">Copy</button>
                            </div>
                        </div>
                        <div>
                            <label class="flex-grow-1">Account Password:</label>
                            <div class="form-group d-flex align-items-center">
                                <input type="text" class="form-control flex-grow-2" id="accountPassword" readonly>
                                <button type="button" class="btn btn-outline-secondary ml-2" id="copyPassword">Copy</button>
                            </div>
                        </div>
                    </div>
                    <!-- Seller Details -->
                    <div id="sellerDetailsContainer" style="display:none;">
                        <!-- Seller details will be dynamically added here -->
                    </div>
                    <div id="noAccountMessage" style="display:none;">
                        <p class="text-danger">Disabled or no available account with the required stock.</p>
                    </div>
                    <!-- New report form -->
                    <form id="reportForm">
                        <div class="form-group">
                            <label>Status:</label>
                            <div>
                                <input type="radio" name="status" value="has_problem" required> Has Problem
                                <input type="radio" name="status" value="needs_return" required> Needs Return
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="note">Note:</label>
                            <textarea class="form-control" name="note" rows="3" placeholder="Describe the issue"></textarea>
                        </div>
                        <input type="hidden" name="order_id" id="reportOrderId">
                        <button type="submit" class="btn btn-primary">Submit Report</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="{{ asset('assets/js/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('assets/js/utils.js') }}"></script>
    <script>
        jQuery(document).ready(function ($) {
            let typingTimer; // Timer to delay the AJAX request
            const typingDelay = 300; // Delay in milliseconds (adjust as needed)
    
            $('#searchBox').on('input', function () {
                const query = $(this).val();
    
                if (query.length >= 3) {
                    clearTimeout(typingTimer); // Clear the timer
                    typingTimer = setTimeout(function () {
                        performSearch(query);
                    }, typingDelay);
                } else if (query === '') {
                    location.reload(); // Reload the page if the search is cleared
                }
            });
            let buyPhoneRequest = false; 
            $('#buyer_phone').on('focusout', function () {
                if (buyPhoneRequest) return;
                buyPhoneRequest = true;
                const query = $(this).val().trim(); // Get input value

                if (query.length > 0) {
                    $.ajax({
                        url: '{{ route("manager.buyer.name") }}', // Make sure this route matches your Laravel route
                        type: 'GET',
                        data: { search: query },
                        dataType: 'json',
                        success: function (response) {
                            console.log(response);
                            if (response.length > 0) {
                                $('#buyer_name').val(response[0].buyer_name); // Populate name field
                            } else {
                                $('#buyer_name').val(''); // Clear if no result
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                        },
                        complete: function () {
                            buyPhoneRequest = false; // Reset flag when request is complete
                        }
                    });
                }
            });
    
            function performSearch(query) {
                const platform = '{{ $n }}'; // Get the current platform dynamically (4 for PS4 or 5 for PS5)
                const url = platform === '4' ? '{{ route('manager.games.search.ps4') }}' : '{{ route('manager.games.search.ps5') }}';
    
                $.ajax({
                    url: url,
                    method: 'GET',
                    data: { query: query },
                    success: function (response) {
                        $('#gamesListings .row').html(response); // Replace the game cards with the filtered results
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while fetching the games.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    </script>
    

    <script>
        jQuery(document).ready(function($) {

            function copyToClipboard(selector) {
                // Get the element by selector
                const element = document.querySelector(selector);

                if (element) {
                    // Select the text content
                    element.select();
                    element.setSelectionRange(0, 99999); // For mobile compatibility

                    // Copy the text to the clipboard
                    document.execCommand("copy");

                    // Display success message using SweetAlert2
                    Swal.fire({
                        icon: "success",
                        title: "Copied!",
                        text: "Has been successfully copied to your clipboard.",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                } else {
                // Display error message if element is not found
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Unable to find the element to copy.",
                    timer: 2000,
                    showConfirmButton: false,
                });
                }
            }
            const input = document.querySelector("#buyer_phone");
            const iti = window.intlTelInput(input, {
                initialCountry: "auto", // Automatically detect the user's country
                separateDialCode: true, // Show country code separately
                countrySearch: true, // Show country code separately
                preferredCountries: ["us", "gb", "eg"],
                allowDropdown: true,
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
            var gameModal = new bootstrap.Modal(document.getElementById('gameModal'));
            let reportFormSubmitting = false;
            // Monitor changes in the report status radio buttons
            $('input[name="status"]').on('change', function () {
                // Check the selected value
                if ($(this).val() === 'has_problem') {
                    // Make the note field required
                    $('textarea[name="note"]').prop('required', true);
                } else {
                    // Make the note field optional
                    $('textarea[name="note"]').prop('required', false);
                }
            });

            // Trigger the change event on page load to handle default state
            $('input[name="status"]:checked').trigger('change');
            $(document).on('submit', '#reportForm', function(e) {
                e.preventDefault();
                if (reportFormSubmitting) return; // Prevent multiple submissions
                reportFormSubmitting = true; // Set flag before sending request

                var formData = $(this).serialize();

                $.ajax({
                    url: '/manager/reports/store',  // Change this to your actual report submission route
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'  // CSRF token for security
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Report Submitted!',
                            text: 'Your report has been successfully submitted.'
                        });
                        $('#accountModal').modal('hide');
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'An error occurred while submitting the report.'
                        });
                    },
                    complete: function() {
                        reportFormSubmitting = false; // Reset flag after request completes
                    }
                });
            });

            // Handle opening the modal and setting dynamic data
            $(document).on('click', '.open-modal', function() {
                var gameTitle = $(this).data('game-title');
                var gameId = $(this).data('game-id');
                var stock = $(this).data('stock');
                var type = $(this).data('type');
                var platform = $(this).data('platform'); 

                // Set the modal title and hidden fields
                $('#gameModalLabel').text(gameTitle + ' (' + type + ' PS' + platform + ')');
                $('#game_id').val(gameId);
                $('#game_type').val(type);
                $('#game_platform').val(platform);

                // Show the modal
                $('#gameModal').modal('show');
            });
            let orderFormSubmitting = false; // Flag to prevent duplicate submissions
            // Handle form submission with AJAX
            $(document).on('submit', '#orderForm',function(e) {
                e.preventDefault();
                if (orderFormSubmitting) return; // Prevent multiple submissions
                orderFormSubmitting = true; // Set flag before sending request
                // Get the formatted phone number from intl-tel-input
                if (iti.isValidNumber()) {
                    var phoneNumber = iti.getNumber();  // Get the full international number
                    // Manually set the phone number value in the form data
                    var formData = $(this).serializeArray();  // Use serializeArray to manipulate the data
                    formData.forEach(function(item) {
                        if (item.name === "buyer_phone") {
                            item.value = phoneNumber;  // Replace buyer_phone value with the intl-tel-input number
                        }
                    });

                    $.ajax({
                        url: '/manager/orders/store',  
                        method: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'  
                        },
                        success: function(response) {
                            gameModal.hide();

                            // Check if the response contains account data
                            if (response.account_email && response.account_password) {
                                // Populate the account modal with account details
                                $('#accountEmail').val(response.account_email);
                                $('#accountPassword').val(response.account_password);
                                $('#accountDetailsContainer').show();
                                $('#noAccountMessage').hide();

                                // Check for seller details and display them if available
                                if (response.message) {
                                    $('#sellerDetailsContainer').html(`
                                        <div class="alert alert-warning mt-3" role="alert">${response.message}</div>
                                    `).show();
                                } else {
                                    $('#sellerDetailsContainer').hide();
                                }
                            } else {
                                $('#accountDetailsContainer').hide();
                                $('#noAccountMessage').show();
                            }
                            // Set the order_id in the report form
                            if (response.order_id) {
                                $('#reportOrderId').val(response.order_id);
                            }
                            // Show the modal with account and seller details or the no account message
                            $('#accountModal').modal('show');
                        },
                        error: function(xhr) {
                            console.log(xhr);
                            let errorMessage = 'An error occurred while creating the order.';
                            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                                // Extract the error message from the response
                                errorMessage = xhr.responseJSON.message;
                            }

                            Swal.fire({
                                title: 'Error',
                                text: errorMessage, // Display the specific error message
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        },
                        complete: function() {
                            orderFormSubmitting = false; // Reset flag after request completes
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Invalid Phone Number',
                        text: 'Please enter a valid phone number.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    orderFormSubmitting = false; // Reset flag after request completes
                }
            });


            // Copy functionality for both email and password fields
            $('#copyEmail').on('click', function() {
                copyToClipboard('#accountEmail');
            });

            $('#copyPassword').on('click', function() {
                copyToClipboard('#accountPassword');
            });

            // Confirmation before closing the accountModal
            $(document).on('hide.bs.modal', '#accountModal',function (e) {
                e.preventDefault(); // Prevent the modal from closing automatically

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you really want to close the account details?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, close it!',
                    cancelButtonText: 'No, keep it open'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // If confirmed, hide the modal and reload the page
                        $('#accountModal').modal('hide');
                        location.reload();
                    }
                });
            });

        });
    </script>
@endpush
