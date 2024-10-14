@extends('layouts.admin')

@section('title', 'Manager - Games')

@section('content')
<div class="container">
    <div class="row">
        @php
            $moneyIcon = '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="30px" height="30px" style="shape-rendering:geometricPrecision;text-rendering:geometricPrecision;image-rendering:optimizeQuality;fill-rule:evenodd;clip-rule:evenodd" viewBox="0 0 2048 2048" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><style type="text/css">.fil1{fill:#fff;fill-rule:nonzero}</style></defs><g id="Layer_x0020_1"><metadata id="CorelCorpID_0Corel-Layer"/><rect class="fil0" width="30" height="30"/><g id="_445077408"><rect id="_444487808" class="fil0" x="255.996" y="255.996" width="25" height="25"/><g><path id="_444486272" class="fil1" d="M1486.9 1362.28c0,-139.942 -60.9721,-311.361 -157.902,-440.683 -81.9957,-109.399 -189.144,-187.611 -305.001,-187.611 -115.854,0 -223.002,78.2115 -304.997,187.612 -96.9296,129.323 -157.901,300.74 -157.901,440.681 0,123.073 49.0418,197.743 128.327,241.355 83.9918,46.2 202.663,60.3591 334.571,60.3591 131.91,0 250.583,-14.1591 334.575,-60.3591 79.2863,-43.6122 128.328,-118.282 128.328,-241.355zm-106.902 -478.934c104.911,139.97 170.903,326.274 170.903,478.934 0,150.253 -61.7481,242.443 -161.578,297.356 -95.1249,52.3241 -223.908,68.3599 -365.326,68.3599 -141.415,0 -270.197,-16.0358 -365.322,-68.3599 -99.8292,-54.913 -161.577,-147.104 -161.577,-297.356 0,-152.661 65.9918,-338.962 170.902,-478.933 93.2493,-124.415 217.801,-213.363 355.998,-213.363 138.199,0 262.751,88.9477 356.001,213.362z"/><path id="_444485024" class="fil1" d="M1164.36 384.966l-125.28 66.2682 -14.876 7.86851 -14.8736 -7.86851 -125.61 -66.4453 59.5595 198.873c8.09174,-1.7941 16.265,-3.32953 24.515,-4.60866 19.2626,-2.98465 38.08,-4.48229 56.4107,-4.48229 18.5244,0 37.0536,1.42087 55.5556,4.24961 8.02323,1.22717 16.0819,2.73898 24.1736,4.53662l60.4252 -198.391zm-140.154 1.9004l111.891 -59.1863c15.8539,-8.38701 32.6339,-9.19135 47.5843,-4.63937 8.1189,2.47205 15.587,6.52205 21.9555,11.778 6.49843,5.36221 11.941,11.9504 15.8728,19.3854 7.26024,13.7291 9.60946,30.3461 4.35473,47.6055l-69.8989 229.497 -9.33662 30.6544 -30.5386 -9.40513c-14.9315,-4.59804 -30.2173,-8.09764 -45.8292,-10.4847 -15.2327,-2.32914 -30.5918,-3.49843 -46.0548,-3.49843 -16.1386,0 -31.7008,1.16339 -46.6595,3.48071 -15.2421,2.36221 -30.7524,6.00827 -46.4977,10.9287l-30.8362 9.6378 -9.2882 -31.0122 -68.9682 -230.288c-5.12599,-17.113 -2.73071,-33.6414 4.52599,-47.3563 3.95906,-7.47993 9.4193,-14.0799 15.9248,-19.4232 6.56339,-5.39056 14.1024,-9.4819 22.154,-11.8937 14.8335,-4.44095 31.5036,-3.56457 47.328,4.80709l112.317 59.413z"/><path id="_445069008" class="fil1" d="M1045.94 1517.7l0 -58.2449 0 -9.63898 9.49253 -1.93701c31.8473,-6.49607 57.854,-20.8866 75.7926,-40.813 17.5914,-19.539 27.4689,-44.5902 27.4689,-72.8575 0,-28.6748 -7.54253,-51.8056 -23.9492,-70.9571 -17.0244,-19.8756 -43.7469,-36.065 -81.5386,-50.1969l-0.0933072 0c-29.5949,-11.3126 -49.6453,-20.9067 -62.6162,-30.6768 -15.3602,-11.5701 -21.5823,-23.5347 -21.5823,-38.1343 0,-16.1232 7.01693,-29.0847 19.8591,-38.015 11.5122,-8.00552 27.6792,-12.3957 47.4367,-12.3957 22.4516,0 41.08,3.67559 56.452,8.54174 13.1752,4.17048 23.863,9.21024 32.4874,13.5272l16.2473 -54.9048c-8.03977,-3.86457 -18.0851,-8.15316 -30.4677,-11.8618 -13.774,-4.12796 -30.4146,-7.52953 -50.3469,-8.81221l-11.1118 -0.714567 0 -11.0917 0 -50.2441 -47.9209 0 0 54.5339 0 9.56457 -9.4004 2.01024c-30.8599,6.60355 -55.663,20.4295 -72.639,39.5351 -16.6701,18.7618 -25.8709,42.8422 -25.8709,70.3359 0,32.7544 13.7102,56.3693 34.1209,74.2394 21.4559,18.7854 50.537,31.7469 79.4032,42.2823l0.00826772 -0.0259843c25.8213,9.22324 44.0847,18.5043 56.2371,29.1284 13.7563,12.0248 19.8118,25.4244 19.8118,41.6552 0,17.3457 -8.09174,31.6252 -22.4067,41.517 -12.9898,8.97757 -31.1268,14.061 -52.7162,14.061 -24.4855,0 -46.467,-4.73977 -64.6689,-10.8484 -15.7583,-5.2878 -28.6654,-11.6256 -37.8792,-16.7764l-16.2473 56.7591c11.2217,6.68859 25.65,12.5575 41.7059,17.1165 17.2937,4.90867 36.2953,8.24292 55.0843,9.39804l11.1591 0.687402 0 11.1201 0 53.1343 48.6886 0z"/></g></g></g></svg>';
            $up = '<svg width="22px" height="18px" version="1.1" id="Layer_2" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve"><g><g><polygon fill="#fff" points="256,10 106,210 186,210 186,366 326,366 326,210 406,210 		"/></g><g><rect x="186" y="394" fill="#fff" width="140" height="25"/></g><g><rect x="186" y="462" fill="#fff" width="140" height="40"/></g></g></svg>';
        @endphp
            
        @foreach($psGames as $game)
            <div class="col-md-4 mb-4">
                <div class="card">
                    @php
                        $image_url = "ps{$n}_image_url";
                        $offline_stock = "ps{$n}_offline_stock";
                        $primary_stock = "ps{$n}_primary_stock";
                        $secondary_stock = "ps{$n}_secondary_stock";
                        $offline_price = "ps{$n}_offline_price";
                        $primary_price = "ps{$n}_primary_price";
                        $secondary_price = "ps{$n}_secondary_price";
                    @endphp
                    
                    <div class="card-img-top" style="background-image: url('{{ asset($game->$image_url) }}'); height: 250px; background-size: cover; background-position: center;"></div>
            
                    <div class="card-body text-center">
                        <h5 class="card-title">{{ $game->title }}</h5>

                        @foreach (['offline', 'primary', 'secondary'] as $type)
                            @php
                                $stock = ${"{$type}_stock"};
                                $price = ${"{$type}_price"};
                                $backgroundColor = $type === 'offline' ? '#f64e60' : ($type === 'primary' ? '#1bc5bd' : '#ffa800');
                            @endphp
                            <!-- Dynamic Buttons -->
                            <a title="{{ ucfirst($type) }}" 
                            class="d-inline-flex justify-content-center align-items-center rounded text-light font-weight-bold m-2 {{ $game->$stock > 0 ? 'open-modal' : '' }}" 
                            style="padding:10px;background-color: {{ $backgroundColor }};"
                            data-game-id="{{ $game->id }}" 
                            data-game-title="{{ $game->title }}" 
                            data-type="{{ $type }}"
                            data-platform="{{ $n }}">
                                {!! $up !!}{{ $game->$stock }} 
                                <span>&nbsp;|&nbsp;</span> 
                                {!! $moneyIcon !!}{{ $game->$price }}
                            </a>

                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $psGames->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

<!-- Modal -->
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
                        <select class="form-control" name="store_profile_id" required>
                            @foreach ($storeProfiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Client Name:</label>
                        <input type="text" class="form-control" placeholder="Client name" name="buyer_name" required>
                    </div>
                    <div class="form-group">
                        <label>Client Phone:</label>
                        <input type="text" class="form-control" placeholder="Enter Client Phone" name="buyer_phone" required>
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
            <div class="modal-body">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
    jQuery(document).ready(function($) {
    // Initialize the modal only once
    var gameModal = new bootstrap.Modal(document.getElementById('gameModal'));

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
        gameModal.show();
    });

    // Handle form submission with AJAX
    $('#orderForm').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: '/manager/orders/store',  
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'  
            },
            success: function(response) {
                alert('Order created successfully!');
                gameModal.hide();
                // Populate the second modal with account email and password
                $('#accountEmail').val(response.account_email);
                $('#accountPassword').val(response.account_password);

                // Show the modal with account details
                $('#accountModal').modal('show');  
            },
            error: function(xhr) {
                console.log(xhr.responseText);
                alert('An error occurred while creating the order.');
            }
        });
    });

    // Function to copy the input value to the clipboard
    function copyToClipboard(element) {
        var copyText = document.querySelector(element);
        copyText.select();
        document.execCommand("copy");
        alert('Copied: ' + copyText.value);  // Alert to confirm copy
    }

    // Copy functionality for both email and password fields
    $('#copyEmail').on('click', function() {
        copyToClipboard('#accountEmail');
    });

    $('#copyPassword').on('click', function() {
        copyToClipboard('#accountPassword');
    });

    // Confirmation before closing the accountModal
    $('#accountModal').on('hide.bs.modal', function (e) {
        if (!confirm('Are you sure you want to close the account details?')) {
            e.preventDefault();  // Prevent modal from closing
        }
    });
});
</script>
@endpush
