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
</tr>
@endforeach
