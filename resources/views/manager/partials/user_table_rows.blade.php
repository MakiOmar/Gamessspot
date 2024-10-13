@foreach($users as $user)
<tr>
    <td>{{ $user->id }}</td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->storeProfile->name ?? 'No Store Profile' }}</td>
    <td>
        <!-- Edit Button -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="{{ $user->id }}">Edit</button>
        <!-- Delete Button -->
        <button type="button" class="btn btn-danger deleteUserButton" data-id="{{ $user->id }}">Delete</button>
    </td>
</tr>
@endforeach
