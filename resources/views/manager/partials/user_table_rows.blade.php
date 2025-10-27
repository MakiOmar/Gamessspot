@foreach($users as $user)
<tr>
    <td>{{ $user->id }}</td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->storeProfile->name ?? 'No Store Profile' }}</td>
    <td>{{ $user->phone }}</td>
    <td class="text-center">
        <span class="badge bg-primary">{{ $user->orders_count ?? 0 }}</span>
    </td>
    <td>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="{{ $user->id }}">Edit</button>
        <a href="{{ route('manager.orders', ['search' => $user->phone, 'start_date' => '2000-01-01', 'end_date' => date('Y-m-d', strtotime('+1 year'))]) }}" 
           class="btn btn-info btn-sm" 
           title="View All Orders">
            <i class="bi bi-receipt"></i> Orders
        </a>
        @if(auth()->id() !== $user->id)
        <!-- Delete Button -->
        <button type="button" class="btn btn-danger deleteUserButton" data-id="{{ $user->id }}">Delete</button>
        @endif
        @if (Auth::user()->roles->contains('name', 'admin'))
            @if ($user->is_active)
                <button type="button" class="btn btn-secondary toggleUserStatus" data-id="{{ $user->id }}" data-status="deactivate">Deactivate</button>
            @else
                <button type="button" class="btn btn-success toggleUserStatus" data-id="{{ $user->id }}" data-status="activate">Activate</button>
            @endif
        @endif

    </td>
</tr>
@endforeach