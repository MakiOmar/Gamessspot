@foreach($storeProfiles as $storeProfile)
<tr>
    <td>{{ $storeProfile->id }}</td>
    <td>{{ $storeProfile->name }}</td>
    <td>{{ $storeProfile->phone }}</td>
    <td>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editStoreProfileModal" data-id="{{ $storeProfile->id }}">Edit</button>
    </td>
</tr>
@endforeach
