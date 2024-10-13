@foreach($users as $user)
<tr>
    <td>{{ $user->id }}</td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->storeProfile->name ?? 'No Store Profile' }}</td>
    <td>
        <a href="{{ route('manager.users.edit', $user->id) }}" class="btn btn-primary">Edit</a>
    </td>
</tr>
@endforeach
