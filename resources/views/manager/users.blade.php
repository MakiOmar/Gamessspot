@extends('layouts.admin')

@section('title', 'Manager - Users')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Users Management</h1>
    <!-- Add User Button -->
    <div class="d-flex justify-content-end mb-4">
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#editUserModal" id="createUserButton">
            Create New User
        </button>
    </div>
    <!-- Search Box -->
    <div class="d-flex justify-content-between mb-4 align-items-end">
        <div class="w-50">
            <div class="alert alert-warning" id="noResultsMessage" style="display: none;">
                No results found.
            </div>
            <!-- Search Box -->
            <input type="text" class="form-control" id="searchUser" placeholder="Search users by name or store profile">
        </div>
    </div>

    <!-- Scrollable table container -->
    <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
        <table class="table table-striped table-bordered" style="min-width: 1200px;">
            <thead>
                <tr role="row">
                    <th style="width: 57px;">ID</th>
                    <th style="width: 265px;">Name</th>
                    <th style="width: 170px;">Store Profile</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->storeProfile->name ?? 'No Store Profile' }}</td>
                    <td>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="{{ $user->id }}">Edit</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination links (if needed) -->
    <div class="d-flex justify-content-center mt-4">
        {{ $users->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- User ID (hidden) -->
                    <input type="hidden" id="userId" name="user_id">

                    <!-- Name -->
                    <div class="form-group mb-3">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group mb-3">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>

                    <!-- Phone Field -->
                    <div class="form-group mb-3">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control" id="editPhone" name="phone">
                    </div>

                    <!-- Password Field -->
                    <div class="form-group mb-3">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="editPassword" name="password" placeholder="Leave blank if not changing">
                    </div>

                    <!-- Password Confirmation Field -->
                    <div class="form-group mb-3">
                        <label for="password_confirmation">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password">
                    </div>

                    <!-- Store Profile Dropdown -->
                    <div class="form-group mb-3">
                        <label for="store_profile_id">Store Profile</label>
                        <select class="form-control" id="editStoreProfileId" name="store_profile_id">
                            <option value="">No Store Profile</option>
                            @foreach($storeProfiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Role Checkboxes -->
                    <div class="form-group mb-3">
                        <label for="roles">Roles</label>
                        <div id="rolesCheckboxes">
                            @foreach($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}">
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
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

@push('scripts')
<!-- JavaScript for handling modal edit functionality -->
<script>
    $(document).ready(function() {
        // Handle search input
        $('#searchUser').on('input', function() {
            let query = $(this).val();

            if (query.length >= 3) {
                $.ajax({
                    url: "{{ route('manager.users.search') }}", // Your search route
                    method: 'GET',
                    data: { search: query },
                    success: function(response) {
                        if (response.trim() === '') {
                            $('#noResultsMessage').show();
                        } else {
                            $('#noResultsMessage').hide();
                            $('#userTableBody').html(response);
                        }
                    }
                });
            } else if (query === '') {
                location.reload(); // Reload the page if the search is cleared
            }
        });
        // Handle opening the Create User modal
        $('#createUserButton').on('click', function () {
            $('#editUserModalLabel').text('Create New User'); // Change modal title
            $('#editUserForm').trigger('reset'); // Reset the form
            $('#userId').val(''); // Clear user ID to indicate creation
        });

        // Load user data into the modal on click of Edit button
        $('#editUserModal').on('show.bs.modal', function (event) {
            
            var button = $(event.relatedTarget); // Button that triggered the modal
            var userId = button.data('id'); // Extract info from data-* attributes
            
            if ( userId ) {
                $('#editUserModalLabel').text('Edit User'); // Change modal title for editing
                // Make AJAX request to get user data
                $.ajax({
                    url: '/manager/users/' + userId + '/edit', // Your route to fetch user data
                    method: 'GET',
                    success: function(data) {
                        // Populate the modal with user data
                        $('#userId').val(data.id);
                        $('#editName').val(data.name);
                        $('#editEmail').val(data.email);
                        $('#editPhone').val(data.phone);
                        $('#editStoreProfileId').val(data.store_profile_id);

                        // Uncheck all checkboxes first
                        $('#rolesCheckboxes input[type="checkbox"]').prop('checked', false);

                        // Loop through user roles and check the corresponding checkboxes
                        data.roles.forEach(function(role) {
                            $('#role_' + role.id).prop('checked', true);
                        });
                    }
                });
            }
        });

        // Handle form submission
        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();

            var userId = $('#userId').val();
            var formData = new FormData(this);

            if (!userId) {
                var url = '/manager/users/store';
                var method = 'POST';
                formData.append('_method', 'POST');
            } else {
                var url = '/manager/users/update/' + userId;
                var method = 'POST';
                formData.append('_method', 'PUT'); // Append PUT method for updating
            }
            $.ajax({
                url: url,
                method: method,
                data: formData,
                contentType: false, // Required for file uploads
                processData: false, // Required for file uploads
                success: function(response) {
                    alert('User saved successfully!');
                    location.reload(); // Reload the page or update the table dynamically
                },
                error: function(xhr) {
                    console.log(xhr.responseText); // Log the error for debugging
                    if (xhr.status === 422) { // Handle validation errors
                        var errors = xhr.responseJSON.errors;

                        // Loop through validation errors and display them
                        $.each(errors, function(key, value) {
                            var inputField = $('#' + key);
                            inputField.addClass('is-invalid');
                            inputField.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    } else {
                        alert('An error occurred.');
                    }
                }
            });
        });

    });
</script>
@endpush