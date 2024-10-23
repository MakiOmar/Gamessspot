@extends('layouts.admin')

@section('title', 'Manager - Edit User')

@section('content')
<div class="container">
    @include('messages')
    <form id="editUserForm" method="POST" action="{{ route('manager.users.update', $user->id) }}">
        @csrf
        @method('PUT')
        <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
        </div>
        <div class="modal-body">
            <!-- User ID (hidden) -->
            <input type="hidden" id="userId" name="user_id" value="{{ $user->id }}">

            <!-- Name -->
            <div class="form-group mb-3">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="editName" name="name" required value="{{ old('name', $user->name) }}">
            </div>

            <!-- Email Field -->
            <div class="form-group mb-3">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="editEmail" name="email" required value="{{ old('email', $user->email) }}">
            </div>

            <!-- Phone Field -->
            <div class="form-group mb-3">
                <label for="phone">Phone</label>
                <input type="text" class="form-control" id="editPhone" name="phone" value="{{ old('phone', $user->phone) }}">
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
            @if( Auth::user()->roles->contains('name', 'admin') )
                <!-- Store Profile Dropdown -->
                <div class="form-group mb-3">
                    <label for="store_profile_id">Store Profile</label>
                    <select class="form-control" id="editStoreProfileId" name="store_profile_id">
                        <option value="">No Store Profile</option>
                        @foreach($storeProfiles as $profile)
                            <option value="{{ $profile['id'] }}" {{ $user->store_profile_id == $profile['id'] ? 'selected' : '' }}>
                                {{ $profile['name'] }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <!-- Role Checkboxes -->
                <div class="form-group mb-3">
                    <label for="roles">Roles</label>
                    <div id="rolesCheckboxes">
                        @foreach($userRoles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role['id'] }}" id="role_{{ $role['id'] }}"
                                    {{ in_array($role['id'], array_column($user->roles->toArray(), 'id')) ? 'checked' : '' }}>
                                <label class="form-check-label" for="role_{{ $role['id'] }}">{{ $role['name'] }}</label>
                            </div>
                        @endforeach


                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
@endsection