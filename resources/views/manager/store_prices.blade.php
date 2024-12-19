@extends('layouts.admin')

@section('title', 'Special Prices for Store Profile')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Special Prices for Store Profile: {{ $storeProfile->name }}</h1>
    <!-- Button to open modal -->
    <button class="btn btn-primary create-prices-btn" data-bs-toggle="modal" data-bs-target="#createPricesModal">
        Create Special Prices
    </button>
    <!-- Special Prices Table -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>PS4 Primary Price</th>
                    <th>PS4 Secondary Price</th>
                    <th>PS4 Offline Price</th>
                    <th>PS5 Primary Price</th>
                    <th>PS5 Secondary Price</th>
                    <th>PS5 Offline Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($specialPrices as $specialPrice)
                    <tr>
                        <td>{{ $specialPrice->title }}</td>
                        <td>{{ $specialPrice->ps4_primary_price }}</td>
                        <td>{{ $specialPrice->ps4_secondary_price }}</td>
                        <td>{{ $specialPrice->ps4_offline_price }}</td>
                        <td>{{ $specialPrice->ps5_primary_price }}</td>
                        <td>{{ $specialPrice->ps5_secondary_price }}</td>
                        <td>{{ $specialPrice->ps5_offline_price }}</td>
                        <td>
                            <button class="btn btn-primary edit-special-price" 
                                data-id="{{ $specialPrice->id }}" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editSpecialPriceModal">Edit
                            </button>
                            <button class="btn {{ $specialPrice->is_available ? 'btn-danger' : 'btn-success' }} toggle-availability"
                                data-id="{{ $specialPrice->id }}">
                                {{ $specialPrice->is_available ? 'Block' : 'Unblock' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Price Modal -->

<!-- Edit Special Price Modal -->
<div class="modal fade" id="editSpecialPriceModal" tabindex="-1" aria-labelledby="editSpecialPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSpecialPriceModalLabel">Edit Special Price</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSpecialPriceForm">
                    @csrf
                    <input type="hidden" id="specialPriceId" name="special_price_id">
                
                    <!-- PS4 and PS5 prices -->
                    <label for="ps4_primary_price" class="form-label">PS4 Primary Price</label>
                    <input type="number" id="ps4_primary_price" name="ps4_primary_price" class="form-control" required>
                
                    <label for="ps4_secondary_price" class="form-label mt-3">PS4 Secondary Price</label>
                    <input type="number" id="ps4_secondary_price" name="ps4_secondary_price" class="form-control">
                
                    <label for="ps4_offline_price" class="form-label mt-3">PS4 Offline Price</label>
                    <input type="number" id="ps4_offline_price" name="ps4_offline_price" class="form-control">
                
                    <!-- PS5 prices -->
                    <label for="ps5_primary_price" class="form-label mt-3">PS5 Primary Price</label>
                    <input type="number" id="ps5_primary_price" name="ps5_primary_price" class="form-control">
                
                    <label for="ps5_secondary_price" class="form-label mt-3">PS5 Secondary Price</label>
                    <input type="number" id="ps5_secondary_price" name="ps5_secondary_price" class="form-control">
                
                    <label for="ps5_offline_price" class="form-label mt-3">PS5 Offline Price</label>
                    <input type="number" id="ps5_offline_price" name="ps5_offline_price" class="form-control">
                
                    <!-- Save button -->
                    <button type="submit" class="btn btn-success mt-3">Save Changes</button>
                </form>                
            </div>
        </div>
    </div>
</div>

<!-- Modal for Creating/Updating Prices -->
<div class="modal fade" id="createPricesModal" tabindex="-1" aria-labelledby="createPricesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPricesModalLabel">Create Special Prices</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createSpecialPriceForm" method="POST">
                    @method('POST')
                    @csrf
                    <!-- Store Profile Hidden -->
                    <input type="hidden" id="store_profile_id" name="store_profile_id" value="{{ $storeProfile->id }}">

                    <!-- Game Dropdown -->
                    <label for="game_id" class="form-label">Select Game</label>
                    <select id="game_id" name="game_id" class="form-control" required>
                        @foreach($games as $game)
                            <option value="{{ $game->id }}">{{ $game->title }}</option>
                        @endforeach
                    </select>

                    <!-- Price fields -->
                    <label for="ps4_primary_price" class="form-label mt-3">PS4 Primary Price</label>
                    <input type="number" id="ps4_primary_price" name="ps4_primary_price" class="form-control" required>

                    <label for="ps4_secondary_price" class="form-label mt-3">PS4 Secondary Price</label>
                    <input type="number" id="ps4_secondary_price" name="ps4_secondary_price" class="form-control">

                    <label for="ps4_offline_price" class="form-label mt-3">PS4 Offline Price</label>
                    <input type="number" id="ps4_offline_price" name="ps4_offline_price" class="form-control">

                    <label for="ps5_primary_price" class="form-label mt-3">PS5 Primary Price</label>
                    <input type="number" id="ps5_primary_price" name="ps5_primary_price" class="form-control">

                    <label for="ps5_secondary_price" class="form-label mt-3">PS5 Secondary Price</label>
                    <input type="number" id="ps5_secondary_price" name="ps5_secondary_price" class="form-control">

                    <label for="ps5_offline_price" class="form-label mt-3">PS5 Offline Price</label>
                    <input type="number" id="ps5_offline_price" name="ps5_offline_price" class="form-control">

                    <!-- Save Button -->
                    <button type="submit" class="btn btn-success mt-3">Save Prices</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    jQuery(document).ready(function($) {
        // When the "Edit" button is clicked
        $('.edit-special-price').on('click', function() {
            var specialPriceId = $(this).data('id');
            
            // Fetch the special price details via AJAX
            $.ajax({
                url: '/manager/special-prices/' + specialPriceId + '/edit',
                method: 'GET',
                success: function(response) {
                    // Fill the modal form with the special price data
                    $('#specialPriceId').val(response.id);
                    $('#ps4_primary_price').val(response.ps4_primary_price);
                    $('#ps4_secondary_price').val(response.ps4_secondary_price);
                    $('#ps4_offline_price').val(response.ps4_offline_price);
                    $('#ps5_primary_price').val(response.ps5_primary_price);
                    $('#ps5_secondary_price').val(response.ps5_secondary_price);
                    $('#ps5_offline_price').val(response.ps5_offline_price);
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to load the special prices.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        // Handle form submission via AJAX
        $('#editSpecialPriceForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize(); // Serialize form data

            $.ajax({
                url: '/manager/special-prices/' + $('#specialPriceId').val(),
                method: 'PUT',
                data: formData,
                success: function(response) {
                    Swal.fire({
                        title: 'Success',
                        text: 'Special prices updated successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        // Check if the user clicked "OK"
                        if (result.isConfirmed) {
                            location.reload(); // Reload the page
                        }
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while updating the prices.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        // Handle form submission via AJAX
        $('#createSpecialPriceForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize(); // Serialize the form data

            $.ajax({
                url: '/manager/special-prices/create',  // Update to your correct route
                method: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire({
                        title: 'Success',
                        text: 'Special prices created successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload(); // Reload the page after success
                        }
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while saving the prices.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        // Toggle availability
        $('.toggle-availability').on('click', function() {
            var specialPriceId = $(this).data('id');
            var button = $(this); // Reference to the button to change its text and style

            $.ajax({
                url: '/manager/special-prices/' + specialPriceId + '/toggle-availability',
                method: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Success',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    // Toggle button text and class based on the new status
                    button.toggleClass('btn-danger btn-success');
                    button.text(response.new_status ? 'Block' : 'Unblock');
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while changing availability status.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    });

</script>
@endpush
