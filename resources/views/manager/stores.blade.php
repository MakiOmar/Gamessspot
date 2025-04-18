@extends('layouts.admin')

@section('title', 'Manager - Stores Profiles')

@section('content')
    <div class="mt-5">
        <h1 class="text-center mb-4">Stores Profiles Management</h1>
        <!-- Add Store Profile Button -->
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#editStoreProfileModal" id="createStoreProfileButton">
                Create New Store Profile
            </button>
        </div>
        <!-- Search Box -->
        <div class="d-flex justify-content-between mb-4 align-items-end">
            <div class="w-50">
                <div class="alert alert-warning" id="noResultsMessage" style="display: none;">
                    No results found.
                </div>
                <!-- Search Box -->
                <input type="text" class="form-control" id="searchStoreProfile" placeholder="Search store profiles by name or phone">
            </div>
        </div>

        <!-- Scrollable table container -->
        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            <table class="table table-striped table-bordered stores-responsive-table">
                <thead>
                    <tr role="row">
                        <th style="width: 57px;">ID</th>
                        <th style="width: 265px;">Name</th>
                        <th style="width: 170px;">Phone</th>
                        <th style="width: 170px;">NO. Orders</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="storeProfileTableBody">
                    @include('manager.partials.store_profile_rows', ['storeProfiles' => $storeProfiles])
                </tbody>
            </table>
        </div>

        <!-- Pagination links (if needed) -->
        <div class="d-flex justify-content-center mt-4" id="storePaginationWrapper">
            {{ $storeProfiles->links('vendor.pagination.bootstrap-5') }}
        </div>
        
    </div>

    <!-- Edit Store Profile Modal -->
    <div class="modal fade" id="editStoreProfileModal" tabindex="-1" aria-labelledby="editStoreProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editStoreProfileForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStoreProfileModalLabel">Create New Store Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Store Profile ID (hidden) -->
                        <input type="hidden" id="storeProfileId" name="store_profile_id">

                        <!-- Name -->
                        <div class="form-group mb-3">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>

                        <!-- Phone Field -->
                        <div class="form-group mb-3">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="editPhone" name="phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('js')
<script>
    jQuery(document).ready(function($) {
        $('.stores-responsive-table').mobileTableToggle({
            maxVisibleCols: 3,
        });
        // Handle search input
        function fetchStoreProfiles(query, page = 1) {
            $.ajax({
                url: "{{ route('manager.storeProfiles.search') }}",
                method: 'GET',
                data: { search: query, page: page },
                success: function(response) {
                    if (response.rows.trim() === '') {
                        $('#noResultsMessage').show();
                        $('#storeProfileTableBody').empty();
                        $('#storePaginationWrapper').empty();
                    } else {
                        $('#noResultsMessage').hide();
                        $('#storeProfileTableBody').html(response.rows);
                        $('#storePaginationWrapper').html(response.pagination);
                        $('.stores-responsive-table').mobileTableToggle({
                            maxVisibleCols: 3,
                        });
                    }
                }
            });
        }
        let currentQuery = '';
        $('#searchStoreProfile').on('input', function() {
            currentQuery = $(this).val();

            if (currentQuery.length >= 3) {
                fetchStoreProfiles(currentQuery);
            } else if (currentQuery === '') {
                location.reload();
            }
        });

        // Delegate AJAX pagination
        $(document).on('click', '#search-pagination .pagination a', function(e) {
            e.preventDefault();
            const page = $(this).attr('href').split('page=')[1];
            fetchStoreProfiles(currentQuery, page);
        });

        // Handle opening the Create Store Profile modal
        $('#createStoreProfileButton').on('click', function () {
            $('#editStoreProfileModalLabel').text('Create New Store Profile'); // Change modal title
            $('#editStoreProfileForm').trigger('reset'); // Reset the form
            $('#storeProfileId').val(''); // Clear store profile ID to indicate creation
        });

        // Load store profile data into the modal on click of Edit button
        $('#editStoreProfileModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var storeProfileId = button.data('id'); // Extract info from data-* attributes

            if (storeProfileId) {
                $('#editStoreProfileModalLabel').text('Edit Store Profile'); // Change modal title for editing

                // Make AJAX request to get store profile data
                $.ajax({
                    url: '/manager/storeProfiles/' + storeProfileId + '/edit', // Your route to fetch store profile data
                    method: 'GET',
                    success: function(data) {
                        // Populate the modal with store profile data
                        $('#storeProfileId').val(data.id);
                        $('#editName').val(data.name);
                        $('#editPhone').val(data.phone_number);
                    }
                });
            }
        });

        // Handle form submission
        $('#editStoreProfileForm').on('submit', function(e) {
            e.preventDefault();

            var storeProfileId = $('#storeProfileId').val();
            var formData = new FormData(this);

            if (!storeProfileId) {
                var url = '/manager/storeProfiles/store';
                var method = 'POST';
                formData.append('_method', 'POST');
            } else {
                var url = '/manager/storeProfiles/update/' + storeProfileId;
                var method = 'POST';
                formData.append('_method', 'PUT'); // Append PUT method for updating
            }

            $.ajax({
                url: url,
                method: method,
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    Swal.fire({
                        title: 'Success',
                        text: 'Store Profile saved successfully!',
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
