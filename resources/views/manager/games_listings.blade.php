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
@section('title', 'Manager - Games')

@section('content')
    <div class="container mb-4">
        <div class="row d-flex justify-content-center">
            <div class="col-md-6">
                <input type="text" id="searchBox" class="form-control" placeholder="Search games...">
            </div>
        </div>
    </div>
    <div class="container" id="gamesListings">
        <div class="row">
            @php
                $moneyIcon = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="30px" height="30px" style="shape-rendering:geometricPrecision;text-rendering:geometricPrecision;image-rendering:optimizeQuality;fill-rule:evenodd;clip-rule:evenodd" viewBox="0 0 2048 2048" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><style type="text/css">.fil1{fill:#fff;fill-rule:nonzero}</style></defs><g id="Layer_x0020_1"><metadata id="CorelCorpID_0Corel-Layer"/><rect class="fil0" width="30" height="30"/><g id="_445077408"><rect id="_444487808" class="fil0" x="255.996" y="255.996" width="25" height="25"/><g><path id="_444486272" class="fil1" d="M1486.9 1362.28c0,-139.942 -60.9721,-311.361 -157.902,-440.683 -81.9957,-109.399 -189.144,-187.611 -305.001,-187.611 -115.854,0 -223.002,78.2115 -304.997,187.612 -96.9296,129.323 -157.901,300.74 -157.901,440.681 0,123.073 49.0418,197.743 128.327,241.355 83.9918,46.2 202.663,60.3591 334.571,60.3591 131.91,0 250.583,-14.1591 334.575,-60.3591 79.2863,-43.6122 128.328,-118.282 128.328,-241.355zm-106.902 -478.934c104.911,139.97 170.903,326.274 170.903,478.934 0,150.253 -61.7481,242.443 -161.578,297.356 -95.1249,52.3241 -223.908,68.3599 -365.326,68.3599 -141.415,0 -270.197,-16.0358 -365.322,-68.3599 -99.8292,-54.913 -161.577,-147.104 -161.577,-297.356 0,-152.661 65.9918,-338.962 170.902,-478.933 93.2493,-124.415 217.801,-213.363 355.998,-213.363 138.199,0 262.751,88.9477 356.001,213.362z"/><path id="_444485024" class="fil1" d="M1164.36 384.966l-125.28 66.2682 -14.876 7.86851 -14.8736 -7.86851 -125.61 -66.4453 59.5595 198.873c8.09174,-1.7941 16.265,-3.32953 24.515,-4.60866 19.2626,-2.98465 38.08,-4.48229 56.4107,-4.48229 18.5244,0 37.0536,1.42087 55.5556,4.24961 8.02323,1.22717 16.0819,2.73898 24.1736,4.53662l60.4252 -198.391zm-140.154 1.9004l111.891 -59.1863c15.8539,-8.38701 32.6339,-9.19135 47.5843,-4.63937 8.1189,2.47205 15.587,6.52205 21.9555,11.778 6.49843,5.36221 11.941,11.9504 15.8728,19.3854 7.26024,13.7291 9.60946,30.3461 4.35473,47.6055l-69.8989 229.497 -9.33662 30.6544 -30.5386 -9.40513c-14.9315,-4.59804 -30.2173,-8.09764 -45.8292,-10.4847 -15.2327,-2.32914 -30.5918,-3.49843 -46.0548,-3.49843 -16.1386,0 -31.7008,1.16339 -46.6595,3.48071 -15.2421,2.36221 -30.7524,6.00827 -46.4977,10.9287l-30.8362 9.6378 -9.2882 -31.0122 -68.9682 -230.288c-5.12599,-17.113 -2.73071,-33.6414 4.52599,-47.3563 3.95906,-7.47993 9.4193,-14.0799 15.9248,-19.4232 6.56339,-5.39056 14.1024,-9.4819 22.154,-11.8937 14.8335,-4.44095 31.5036,-3.56457 47.328,4.80709l112.317 59.413z"/><path id="_445069008" class="fil1" d="M1045.94 1517.7l0 -58.2449 0 -9.63898 9.49253 -1.93701c31.8473,-6.49607 57.854,-20.8866 75.7926,-40.813 17.5914,-19.539 27.4689,-44.5902 27.4689,-72.8575 0,-28.6748 -7.54253,-51.8056 -23.9492,-70.9571 -17.0244,-19.8756 -43.7469,-36.065 -81.5386,-50.1969l-0.0933072 0c-29.5949,-11.3126 -49.6453,-20.9067 -62.6162,-30.6768 -15.3602,-11.5701 -21.5823,-23.5347 -21.5823,-38.1343 0,-16.1232 7.01693,-29.0847 19.8591,-38.015 11.5122,-8.00552 27.6792,-12.3957 47.4367,-12.3957 22.4516,0 41.08,3.67559 56.452,8.54174 13.1752,4.17048 23.863,9.21024 32.4874,13.5272l16.2473 -54.9048c-8.03977,-3.86457 -18.0851,-8.15316 -30.4677,-11.8618 -13.774,-4.12796 -30.4146,-7.52953 -50.3469,-8.81221l-11.1118 -0.714567 0 -11.0917 0 -50.2441 -47.9209 0 0 54.5339 0 9.56457 -9.4004 2.01024c-30.8599,6.60355 -55.663,20.4295 -72.639,39.5351 -16.6701,18.7618 -25.8709,42.8422 -25.8709,70.3359 0,32.7544 13.7102,56.3693 34.1209,74.2394 21.4559,18.7854 50.537,31.7469 79.4032,42.2823l0.00826772 -0.0259843c25.8213,9.22324 44.0847,18.5043 56.2371,29.1284 13.7563,12.0248 19.8118,25.4244 19.8118,41.6552 0,17.3457 -8.09174,31.6252 -22.4067,41.517 -12.9898,8.97757 -31.1268,14.061 -52.7162,14.061 -24.4855,0 -46.467,-4.73977 -64.6689,-10.8484 -15.7583,-5.2878 -28.6654,-11.6256 -37.8792,-16.7764l-16.2473 56.7591c11.2217,6.68859 25.65,12.5575 41.7059,17.1165 17.2937,4.90867 36.2953,8.24292 55.0843,9.39804l11.1591 0.687402 0 11.1201 0 53.1343 48.6886 0z"/></g></g></g></svg>';
                $up = '<svg width="22px" height="18px" version="1.1" id="Layer_2" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve"><g><g><polygon fill="#fff" points="256,10 106,210 186,210 186,366 326,366 326,210 406,210 		"/></g><g><rect x="186" y="394" fill="#fff" width="140" height="25"/></g><g><rect x="186" y="462" fill="#fff" width="140" height="40"/></g></g></svg>';
            @endphp

            @foreach($psGames as $game)
                <div class="col-md-4 mb-4 game-card">
                    <div class="card">
                        @php
                            $image_url = "ps{$n}_image_url";
                            $offline_stock = "ps{$n}_offline_stock";
                            $primary_stock = "ps{$n}_primary_stock";
                            $secondary_stock = "ps{$n}_secondary_stock";
                            $offline_price = "ps{$n}_offline_price";
                            $primary_price = "ps{$n}_primary_price";
                            $secondary_price = "ps{$n}_secondary_price";

                            // Check if store profile is blocked for this game
                            $isBlocked = auth()->user()->storeProfile ? auth()->user()->storeProfile->isBlockedForGame($game->id) : false;
                        @endphp
                        
                        <div class="card-img-top" style="background-image: url('{{ asset($game->$image_url) }}')"></div>
                
                        <div class="card-body text-center">
                            <h5 class="card-title float-none">{{ $game->title }}</h5>
                            @if (!$isBlocked)
                                @foreach (['offline', 'primary', 'secondary'] as $type)
                                    @php
                                        $stock = ${"{$type}_stock"};
                                        $price = ${"{$type}_price"};
                                        $backgroundColor = $type === 'offline' ? '#f64e60' : ($type === 'primary' ? '#1bc5bd' : '#ffa800');
                                        $status = "ps{$n}_{$type}_status";
                                        $active = 'open-modal';
                                        if ( ! $game->$status || $game->$stock < 1 || ( 'primary' === $type && ! $game->is_primary_active ) ) {
                                            $active = 'disabled';
                                        }                                        
                                    @endphp
                                        <!-- Dynamic Buttons -->
                                        <a title="{{ ucfirst($type) }}" 
                                        class="d-inline-flex justify-content-center align-items-center rounded text-light font-weight-bold {{ $active }}" 
                                        style="padding:5px;margin:3px;background-color: {{ $backgroundColor }};"
                                        data-game-id="{{ $game->id }}" 
                                        data-game-title="{{ $game->title }}" 
                                        data-type="{{ $type }}"
                                        data-platform="{{ $n }}">
                                            {!! $up !!}{{ $game->$stock }} 
                                            <span>&nbsp;|&nbsp;</span> 
                                            {!! $moneyIcon !!}{{ $game->$price }}
                                        </a>
                                @endforeach
                            @else
                                <!-- Bootstrap Alert for Blocked Game -->
                                <div class="alert alert-warning mt-2" role="alert">
                                    You are not allowed to sell this game due to restrictions on your store profile.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
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
        jQuery(document).ready(function ($) {
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
            $('#reportForm').on('submit', function(e) {
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
            $('.open-modal').on('click', function() {
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
            $('#orderForm').on('submit', function(e) {
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
            $('#accountModal').on('hide.bs.modal', function (e) {
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
