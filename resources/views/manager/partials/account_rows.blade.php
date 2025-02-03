@foreach($accounts as $account)
<tr>
    <td>{{ $account->id }}</td>
    <td>{{ $account->mail }}</td>
    <td>{{ $account->game->title }}</td>
    <td>{{ $regionEmojis[$account->region] ?? $account->region }}</td>
    <td>{{ $account->ps4_offline_stock }}</td>
    <td>{{ $account->ps4_primary_stock }}</td>
    <td>{{ $account->ps4_secondary_stock }}</td>
    <td>{{ $account->ps5_offline_stock }}</td>
    <td>{{ $account->ps5_primary_stock }}</td>
    <td>{{ $account->ps5_secondary_stock }}</td>
    <td>{{ $account->cost }}</td>
    <td>{{ $account->password }}</td>
    <td>
    <!-- Edit Button -->
    <button type="button" class="btn btn-warning btn-sm editAccount" 
        data-id="{{ $account->id }}"
        data-mail="{{ $account->mail }}"
        data-password="{{ $account->password }}"
        data-game_id="{{ $account->game_id }}"
        data-region="{{ $account->region }}"
        data-cost="{{ $account->cost }}"
        data-birthdate="{{ $account->birthdate }}"
        data-login_code="{{ $account->login_code }}"
        data-ps4_primary="{{ $account->ps4_primary_stock }}"
        data-ps4_secondary="{{ $account->ps4_secondary_stock }}"
        data-ps4_offline="{{ $account->ps4_offline_stock }}"
        data-ps5_primary="{{ $account->ps5_primary_stock }}"
        data-ps5_secondary="{{ $account->ps5_secondary_stock }}"
        data-ps5_offline="{{ $account->ps5_offline_stock }}"
        data-bs-toggle="modal" 
        data-bs-target="#accountModal">
        Edit
    </button>
    </td>
</tr>
@endforeach
