@foreach($storeProfiles as $storeProfile)
<tr>
    <td>{{ $storeProfile->id }}</td>
    <td>{{ $storeProfile->name }}</td>
    <td>{{ $storeProfile->phone_number }}</td>
    <td>{{ $storeProfile->orders_count  }}</td>
    <td>
        @if(Auth::user()->roles->contains('name', 'admin'))
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editStoreProfileModal" data-id="{{ $storeProfile->id }}">Edit</button>
            <a href="{{ route('manager.special-prices', $storeProfile->id) }}" class="btn btn-success">Prices</a>
        @endif
        @if(Auth::user()->roles->contains('name', 'accountant') || Auth::user()->roles->contains('name', 'admin'))
            <a href="{{ route('manager.orders') }}/?id={{ $storeProfile->id }}" class="btn btn-primary">Sell log</a>
        @endif
    </td>
</tr>
@endforeach
