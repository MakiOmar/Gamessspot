@extends('layouts.admin')

@php
    $pageTitle = $pageTitle ?? 'Manager - Games';
    $pageHeading = $pageHeading ?? 'Games Management';
    $createButtonLabel = $createButtonLabel ?? 'Create Game';
    $defaultProductType = $defaultProductType ?? \App\Models\Game::TYPE_GAME;
    $searchRoute = $searchRoute ?? route('manager.games.search');
    $isSubscriptionCatalog = $isSubscriptionCatalog ?? false;
@endphp

@section('title', $pageTitle)
@section('plugins.Summernote', true)
@push('css')
<style>
    @media screen and ( min-width:1200px ){
        .games-reponsive-table{
            min-width: 1200px;
        }
    }
    /* Wider add/edit modal so the description editor is usable */
    #editGameModal .modal-dialog {
        max-width: min(1140px, 96vw);
    }
    #editGameModal .note-editor {
        background: #fff;
    }
    #editGameModal .note-editable {
        min-height: 220px;
    }
</style>
@endpush
@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">{{ $pageHeading }}</h1>
        
        {{-- Cache Indicator --}}
        @include('components.cache-indicator')
        
        <!-- Search Box -->
        <input type="text" id="search-box" class="form-control mb-3" placeholder="Search by title...">
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-success" id="createGameBtn" data-bs-toggle="modal" data-bs-target="#editGameModal">
                {{ $createButtonLabel }}
            </button>
            
        </div>
        
        <!-- Scrollable table container -->
        <div id="games-table" style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            @include('manager.partials.games_row', ['games' => $games]) <!-- Load initial table -->
        </div>

        
    </div>

    <!-- Edit Game Modal -->
    <div class="modal fade" id="editGameModal" tabindex="-1" aria-labelledby="editGameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
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

                        <!-- Product type: game vs subscription (PS Plus, etc.) -->
                        <label for="productType" class="mt-3">Product Type</label>
                        <select id="productType" name="product_type" class="form-control" style="border-radius: 10px;" required>
                            <option value="game">Game</option>
                            <option value="subscription">Subscription</option>
                        </select>

                        <!-- Featured for public featured catalog endpoint -->
                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="isFeatured" name="is_featured" value="1">
                            <label class="form-check-label" for="isFeatured">Featured</label>
                        </div>

                        <!-- Game Description (WYSIWYG) -->
                        <label for="description" class="mt-3">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="8" placeholder="Write the game description..."></textarea>

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

                        <!-- Gallery images (max 8) -->
                        <label for="galleryImages" class="mt-3">Gallery Images</label>
                        <input type="file" id="galleryImages" name="gallery_images[]" class="form-control" style="border-radius: 10px;" accept="image/*" multiple>
                        <small class="text-muted">Up to 8 images. Existing images can be removed below.</small>
                        <div id="existingGallery" class="d-flex flex-wrap gap-2 mt-2"></div>

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
        $('.games-reponsive-table').mobileTableToggle({
            maxVisibleCols: 2,
        });
        // Set up AJAX to include CSRF token in every request
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // WYSIWYG editor for the game description field
        function ensureGameDescriptionEditor() {
            if (typeof $.fn.summernote !== 'function') {
                return;
            }

            if (!$('#description').data('summernote')) {
                $('#description').summernote({
                    height: 240,
                    dialogsInBody: true,
                    placeholder: 'Write the game description...',
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link']],
                        ['view', ['codeview']]
                    ]
                });
            }
        }

        function setGameDescription(html) {
            if (typeof $.fn.summernote === 'function') {
                ensureGameDescriptionEditor();
                $('#description').summernote('code', html || '');
                return;
            }

            $('#description').val(html || '');
        }

        // Status selects use "1"/"0"; API returns booleans from Eloquent casts
        function setStatusSelect(selector, value) {
            $(selector).val(value ? '1' : '0');
        }

        // Map validation error keys (snake_case names) to form controls
        function fieldForValidationKey(key) {
            var $byName = $('#editGameForm').find('[name="' + key + '"]');
            if ($byName.length) {
                return $byName.first();
            }
            return $('#' + key);
        }

        function showValidationErrors(xhr) {
            var payload = xhr.responseJSON || {};
            var errors = payload.errors || {};
            var messages = [];

            $.each(errors, function (key, value) {
                var msg = Array.isArray(value) ? value[0] : value;
                messages.push(msg);
                var $field = fieldForValidationKey(key);
                if ($field.length) {
                    $field.addClass('is-invalid');
                    if (!$field.next('.invalid-feedback').length) {
                        $field.after('<div class="invalid-feedback">' + msg + '</div>');
                    }
                }
            });

            Swal.fire({
                title: 'Validation error',
                text: payload.message || messages.join(' ') || 'Please fix the highlighted fields.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }

        // Render existing gallery thumbs with delete checkboxes
        function renderExistingGallery(images) {
            var $wrap = $('#existingGallery');
            $wrap.empty();
            (images || []).forEach(function (img) {
                var url = img.url || ('/' + img.path);
                $wrap.append(
                    '<label class="border rounded p-1 text-center" style="width:100px;">' +
                        '<img src="' + url + '" class="img-thumbnail mb-1" style="max-width:90px;max-height:90px;">' +
                        '<div class="form-check">' +
                            '<input class="form-check-input" type="checkbox" name="delete_gallery_ids[]" value="' + img.id + '">' +
                            '<span class="small">Remove</span>' +
                        '</div>' +
                    '</label>'
                );
            });
        }

        $('#editGameModal').on('shown.bs.modal', function () {
            ensureGameDescriptionEditor();
        });

        $('#search-box').on('keyup', function () {
            console.log($(this).val());
            let query = $(this).val();
            if ( query.length >= 3 ) {
                    $.ajax({
                    url: @json($searchRoute),
                    type: "GET",
                    data: { query: query, product_type: @json($defaultProductType) },
                    success: function (data) {
                        $('#games-table').html(data);
                    }
                });
            } else if( query === '' ) {
                location.reload();
            }
        });

        // When the "Create Game" button is clicked
        $('#createGameBtn').on('click', function() {
            // Reset the form fields for creating a new game
            $('#editGameForm').find('input').val(''); // Reset all input fields
            $('#editGameForm').find('select').not('#productType').val('1'); // Status selects default Available
            $('#productType').val(@json($defaultProductType));
            $('#isFeatured').prop('checked', false);
            $('#editGameForm').find('.is-invalid').removeClass('is-invalid'); // Remove validation classes
            $('#editGameForm').find('.invalid-feedback').remove(); // Remove previous error messages
            $('#editGameModalLabel').text(@json($isSubscriptionCatalog ? 'Create New Subscription' : 'Create New Game'));

            // Hide image previews for new game
            $('#ps4ImagePreview, #ps4ImageLink').hide();
            $('#ps5ImagePreview, #ps5ImageLink').hide();

            // Remove any game ID for the new game
            $('#gameId').val('');
            setGameDescription('');
            renderExistingGallery([]);
            $('#galleryImages').val('');
        });

        // When the "Edit" button is clicked
        $(document).on('click', '.edit-game', function() {
            var gameId = $(this).data('id');

            // Clear any previous error messages or inputs
            $('#editGameForm').find('input, select').val(''); // Reset all input fields to blank
            $('#isFeatured').prop('checked', false);
            $('#editGameForm').find('.is-invalid').removeClass('is-invalid'); // Remove previous validation errors
            $('#editGameForm').find('.invalid-feedback').remove(); // Remove previous error messages
            $('#editGameModalLabel').text(@json($isSubscriptionCatalog ? 'Edit Subscription' : 'Edit Game'));
            setGameDescription('');
            renderExistingGallery([]);
            $('#galleryImages').val('');

            // Use AJAX to fetch the game data
            $.ajax({
                url: '/manager/games/' + gameId + '/edit',
                method: 'GET',
                success: function(response) {
                    // Populate the form fields with the game data
                    $('#gameId').val(response.id);
                    $('#gameName').val(response.title);
                    $('#gameCode').val(response.code);
                    $('#productType').val(response.product_type || 'game');
                    $('#isFeatured').prop('checked', !!response.is_featured);
                    $('#fullPrice').val(response.full_price);
                    $('#ps4PrimaryPrice').val(response.ps4_primary_price);
                    setStatusSelect('#ps4PrimaryStatus', response.ps4_primary_status);
                    $('#ps4SecondaryPrice').val(response.ps4_secondary_price);
                    setStatusSelect('#ps4SecondaryStatus', response.ps4_secondary_status);
                    $('#ps4OfflinePrice').val(response.ps4_offline_price);
                    setStatusSelect('#ps4OfflineStatus', response.ps4_offline_status);
                    $('#ps5PrimaryPrice').val(response.ps5_primary_price);
                    setStatusSelect('#ps5PrimaryStatus', response.ps5_primary_status);
                    $('#ps5OfflinePrice').val(response.ps5_offline_price);
                    setStatusSelect('#ps5OfflineStatus', response.ps5_offline_status);
                    $('#ps5SecondaryPrice').val(response.ps5_secondary_price);
                    setStatusSelect('#ps5SecondaryStatus', response.ps5_secondary_status);
                    setGameDescription(response.description);
                    renderExistingGallery(response.gallery || []);

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
            if ($('#description').data('summernote')) {
                $('#description').val($('#description').summernote('code'));
            }
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
                    if (xhr.status === 422) {
                        showValidationErrors(xhr);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: (xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        });

        // Handle game deletion
        // Toggle featured star on listing (no page reload)
        $(document).on('click', '.toggle-featured', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const gameId = $btn.data('id');

            $.ajax({
                url: '/manager/games/' + gameId + '/featured',
                method: 'POST',
                data: { _method: 'PATCH' },
                success: function (response) {
                    const featured = !!response.is_featured;
                    $btn.data('featured', featured ? '1' : '0');
                    $btn.attr('title', featured ? 'Remove from featured' : 'Set as featured');
                    $btn.toggleClass('btn-warning', featured);
                    $btn.toggleClass('btn-outline-secondary', !featured);
                    $btn.find('i').toggleClass('fas', featured).toggleClass('far', !featured);

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.message || (featured ? 'Featured' : 'Unfeatured'),
                        showConfirmButton: false,
                        timer: 2000
                    });
                },
                error: function (xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Failed to update featured status.';
                    Swal.fire({
                        title: 'Error',
                        text: message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        $(document).on('click', '.delete-game', function (e) {
            e.preventDefault();

            const gameId = $(this).data('id');
            const gameTitle = $(this).data('title');

            Swal.fire({
                title: 'Delete game?',
                text: `This will remove "${gameTitle}" and related data.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/manager/games/' + gameId,
                        method: 'POST',
                        data: {
                            _method: 'DELETE'
                        },
                        success: function (response) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message || 'Game deleted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Failed to delete the game. Please try again.';

                            Swal.fire({
                                title: 'Error',
                                text: message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        });
    });

</script>
@endpush