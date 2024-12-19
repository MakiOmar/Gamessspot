@extends('layouts.admin')

@section('title', 'Manager - Games')

@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">Games Management</h1>
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-success" id="createGameBtn" data-bs-toggle="modal" data-bs-target="#editGameModal">
                Create Game
            </button>
        </div>
        
        <!-- Scrollable table container -->
        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            <table class="table table-striped table-bordered" style="min-width: 800px;">
                <thead>
                    <tr>
                        <th style="width: 21px;">id</th>
                        <th style="width: 388px;">Game Name</th>
                        <th style="width: 286px;">Code</th>
                        <th style="width: 98px;">Reports</th>
                        <th style="width: 73px;">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($games as $game)
                        <tr>
                            <td>{{ $game->id }}</td>
                            <td>{{ $game->title }}</td>
                            <td>{{ $game->code }}</td>
                            <td><a href="#">View Reports</a></td>
                            <td><a href="#" class="btn btn-primary edit-game" data-id="{{ $game->id }}" data-bs-toggle="modal" data-bs-target="#editGameModal">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination links -->
        <div class="d-flex justify-content-center mt-4">
            {{ $games->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>

    <!-- Edit Game Modal -->
    <div class="modal fade" id="editGameModal" tabindex="-1" aria-labelledby="editGameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGameModalLabel">Edit Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editGameForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="gameId" name="gameId">

                        <!-- Game Name -->
                        <label for="gameName">Game Name</label>
                        <input type="text" id="gameName" name="title" class="form-control" style="border-radius: 10px;" required>

                        <!-- Game Code -->
                        <label for="gameCode" class="mt-3">Game Code</label>
                        <input type="text" id="gameCode" name="code" class="form-control" style="border-radius: 10px;" required>

                        <!-- Full Price -->
                        <label for="fullPrice" class="mt-3">Full Price</label>
                        <input type="number" id="fullPrice" name="full_price" class="form-control" style="border-radius: 10px;" required>

                        <!-- PS4 Primary Price -->
                        <label for="ps4PrimaryPrice" class="mt-3">PS4 Primary Price</label>
                        <input type="number" id="ps4PrimaryPrice" name="ps4_primary_price" class="form-control" style="border-radius: 10px;">

                        <!-- PS4 Primary Status -->
                        <label for="ps4PrimaryStatus" class="mt-3">PS4 Primary Status</label>
                        <select id="ps4PrimaryStatus" name="ps4_primary_status" class="form-control" style="border-radius: 10px;">
                            <option value="1" selected>Available</option>
                            <option value="0">Not Available</option>
                        </select>

                        <!-- PS4 Secondary Price -->
                        <label for="ps4SecondaryPrice" class="mt-3">PS4 Secondary Price</label>
                        <input type="number" id="ps4SecondaryPrice" name="ps4_secondary_price" class="form-control" style="border-radius: 10px;">

                        <!-- PS4 Secondary Status -->
                        <label for="ps4SecondaryStatus" class="mt-3">PS4 Secondary Status</label>
                        <select id="ps4SecondaryStatus" name="ps4_secondary_status" class="form-control" style="border-radius: 10px;">
                            <option value="1" selected>Available</option>
                            <option value="0">Not Available</option>
                        </select>

                        <!-- PS4 Offline Price -->
                        <label for="ps4OfflinePrice" class="mt-3">PS4 Offline Price</label>
                        <input type="number" id="ps4OfflinePrice" name="ps4_offline_price" class="form-control" style="border-radius: 10px;">

                        <!-- PS4 Offline Status -->
                        <label for="ps4OfflineStatus" class="mt-3">PS4 Offline Status</label>
                        <select id="ps4OfflineStatus" name="ps4_offline_status" class="form-control" style="border-radius: 10px;">
                            <option value="1" selected>Available</option>
                            <option value="0">Not Available</option>
                        </select>

                        <!-- PS5 Primary Price -->
                        <label for="ps5PrimaryPrice" class="mt-3">PS5 Primary Price</label>
                        <input type="number" id="ps5PrimaryPrice" name="ps5_primary_price" class="form-control" style="border-radius: 10px;">

                        <!-- PS5 Primary Status -->
                        <label for="ps5PrimaryStatus" class="mt-3">PS5 Primary Status</label>
                        <select id="ps5PrimaryStatus" name="ps5_primary_status" class="form-control" style="border-radius: 10px;">
                            <option value="1" selected>Available</option>
                            <option value="0">Not Available</option>
                        </select>

                        <!-- PS5 Offline Price -->
                        <label for="ps5OfflinePrice" class="mt-3">PS5 Offline Price</label>
                        <input type="number" id="ps5OfflinePrice" name="ps5_offline_price" class="form-control" style="border-radius: 10px;">

                        <!-- PS5 Offline Status -->
                        <label for="ps5OfflineStatus" class="mt-3">PS5 Offline Status</label>
                        <select id="ps5OfflineStatus" name="ps5_offline_status" class="form-control" style="border-radius: 10px;">
                            <option value="1" selected>Available</option>
                            <option value="0">Not Available</option>
                        </select>

                        <!-- PS5 Secondary Price -->
                        <label for="ps5SecondaryPrice" class="mt-3">PS5 Secondary Price</label>
                        <input type="number" id="ps5SecondaryPrice" name="ps5_secondary_price" class="form-control" style="border-radius: 10px;">

                        <!-- PS5 Secondary Status -->
                        <label for="ps5SecondaryStatus" class="mt-3">PS5 Secondary Status</label>
                        <select id="ps5SecondaryStatus" name="ps5_secondary_status" class="form-control" style="border-radius: 10px;">
                            <option value="1" selected>Available</option>
                            <option value="0">Not Available</option>
                        </select>


                        <!-- PS4 Image Upload -->
                        <a href="#" id="ps4ImageLink" target="_blank">
                            <img id="ps4ImagePreview" class="img-thumbnail" style="max-width: 150px; display:none;">
                        </a>
                        <br>
                        <label for="ps4Image" class="mt-3">PS4 Image</label>
                        <input type="file" id="ps4Image" name="ps4_image" class="form-control" style="border-radius: 10px;" accept="image/*">

                        <!-- PS5 Image Upload -->
                        <a href="#" id="ps5ImageLink" target="_blank">
                            <img id="ps5ImagePreview" class="img-thumbnail" style="max-width: 150px; display:none;">
                        </a>
                        <br>
                        <label for="ps5Image" class="mt-3">PS5 Image</label>
                        <input type="file" id="ps5Image" name="ps5_image" class="form-control" style="border-radius: 10px;" accept="image/*">


                        <!-- Save Button -->
                        <button type="submit" class="btn btn-success mt-3">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection
@push('js')
<script>
    jQuery(document).ready(function($) {
        // Set up AJAX to include CSRF token in every request
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // When the "Create Game" button is clicked
        $('#createGameBtn').on('click', function() {
            // Reset the form fields for creating a new game
            $('#editGameForm').find('input').val(''); // Reset all input fields
            $('#editGameForm').find('select').val('1'); // Reset all input fields
            $('#editGameForm').find('.is-invalid').removeClass('is-invalid'); // Remove validation classes
            $('#editGameForm').find('.invalid-feedback').remove(); // Remove previous error messages
            $('#editGameModalLabel').text('Create New Game'); // Update modal title

            // Hide image previews for new game
            $('#ps4ImagePreview, #ps4ImageLink').hide();
            $('#ps5ImagePreview, #ps5ImageLink').hide();

            // Remove any game ID for the new game
            $('#gameId').val('');
        });

        // When the "Edit" button is clicked
        $('.edit-game').on('click', function() {
            var gameId = $(this).data('id');

            // Clear any previous error messages or inputs
            $('#editGameForm').find('input, select').val(''); // Reset all input fields to blank
            $('#editGameForm').find('.is-invalid').removeClass('is-invalid'); // Remove previous validation errors
            $('#editGameForm').find('.invalid-feedback').remove(); // Remove previous error messages
            $('#editGameModalLabel').text('Edit Game'); // Update modal title

            // Use AJAX to fetch the game data
            $.ajax({
                url: '/manager/games/' + gameId + '/edit',
                method: 'GET',
                success: function(response) {
                    // Populate the form fields with the game data
                    $('#gameId').val(response.id);
                    $('#gameName').val(response.title);
                    $('#gameCode').val(response.code);
                    $('#fullPrice').val(response.full_price);
                    $('#ps4PrimaryPrice').val(response.ps4_primary_price);
                    $('#ps4PrimaryStatus').val(response.ps4_primary_status);
                    $('#ps4SecondaryPrice').val(response.ps4_secondary_price);
                    $('#ps4SecondaryStatus').val(response.ps4_secondary_status);
                    $('#ps4OfflinePrice').val(response.ps4_offline_price);
                    $('#ps4OfflineStatus').val(response.ps4_offline_status);
                    $('#ps5PrimaryPrice').val(response.ps5_primary_price);
                    $('#ps5PrimaryStatus').val(response.ps5_primary_status);
                    $('#ps5OfflinePrice').val(response.ps5_offline_price);
                    $('#ps5OfflineStatus').val(response.ps5_offline_status);
                    $('#ps5SecondaryPrice').val(response.ps5_secondary_price);
                    $('#ps5SecondaryStatus').val(response.ps5_secondary_status);

                    // Generate preview for the PS4 image
                    if (response.ps4_image_url) {
                        $('#ps4ImagePreview').attr('src', '/' + response.ps4_image_url).show();
                        $('#ps4ImageLink').attr('href', '/' + response.ps4_image_url).show();
                    } else {
                        $('#ps4ImagePreview').hide(); // Hide if no image available
                        $('#ps4ImageLink').hide();
                    }

                    // Generate preview for the PS5 image
                    if (response.ps5_image_url) {
                        $('#ps5ImagePreview').attr('src', '/' + response.ps5_image_url).show();
                        $('#ps5ImageLink').attr('href', '/' + response.ps5_image_url).show();
                    } else {
                        $('#ps5ImagePreview').hide(); // Hide if no image available
                        $('#ps5ImageLink').hide();
                    }
                }
            });
        });

        // Handle form submission via AJAX (for both Create and Edit)
        $('#editGameForm').on('submit', function(e) {
            e.preventDefault();

            var gameId = $('#gameId').val();
            var formData = new FormData(this);

            // If creating a new game, do not append the PUT method
            if (!gameId) {
                var url = '/manager/games/store';
                var method = 'POST';
            } else {
                var url = '/manager/games/' + gameId;
                var method = 'POST';
                formData.append('_method', 'PUT'); // Append method for updating
            }

            $.ajax({
                url: url,
                method: method,
                data: formData,
                contentType: false, // Required for file uploads
                processData: false, // Required for file uploads
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Game saved successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });

                    location.reload(); // Reload the page or update the table dynamically
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Handle validation errors
                        var errors = xhr.responseJSON.errors;

                        // Loop through validation errors and display them
                        $.each(errors, function(key, value) {
                            var inputField = $('#' + key);
                            inputField.addClass('is-invalid');
                            inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });

                    }
                }
            });
        });
    });

</script>
@endpush