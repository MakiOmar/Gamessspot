@extends('layouts.admin')

@section('title', 'Special Prices for Store Profile')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Special Prices for Store Profile: {{ $storeProfile->name }}</h1>

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

@endsection

@push('scripts')
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
    });

</script>
@endpush
