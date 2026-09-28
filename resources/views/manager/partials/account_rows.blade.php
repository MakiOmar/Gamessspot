@php
    $regionEmojis = $regionEmojis ?? config('flags.flags');
@endphp
@foreach($accounts as $account)
<tr data-account-id="{{ $account->id }}">
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
    <!-- Purchase source: trader and PO link to their pages; legacy accounts have none -->
    <td class="small">
        @if ($account->trader_id)
            @can('view-traders')
                <a href="{{ route('manager.traders.show', $account->trader_id) }}">{{ $account->trader?->name }}</a>
                / <a href="{{ route('manager.purchase-orders.show', $account->purchase_order_id) }}">{{ $account->purchaseOrder?->po_number }}</a>
            @else
                {{ $account->trader?->name }} / {{ $account->purchaseOrder?->po_number }}
            @endcan
            <br><span class="text-muted">{{ $account->purchase_date?->toDateString() }} · {{ number_format((float) $account->original_cost, 2) }}</span>
        @else
            <span class="text-muted">No source</span>
        @endif
    </td>
    <td>{{ $account->password }}</td>
    @can('manage-accounts')
    <td>
        <div class="d-flex flex-wrap gap-2">
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
                data-linked="{{ $account->hasPurchaseSource() ? '1' : '0' }}"
                data-source="{{ $account->hasPurchaseSource() ? ($account->trader?->name . ' / ' . $account->purchaseOrder?->po_number . ' / ' . $account->purchase_date?->toDateString() . ' / ' . number_format((float) $account->original_cost, 2)) : '' }}"
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

            <button type="button"
                class="btn btn-secondary btn-sm editStock"
                data-id="{{ $account->id }}"
                data-ps4_primary_stock="{{ $account->ps4_primary_stock }}"
                data-ps4_secondary_stock="{{ $account->ps4_secondary_stock }}"
                data-ps4_offline_stock="{{ $account->ps4_offline_stock }}"
                data-ps5_primary_stock="{{ $account->ps5_primary_stock }}"
                data-ps5_secondary_stock="{{ $account->ps5_secondary_stock }}"
                data-ps5_offline_stock="{{ $account->ps5_offline_stock }}"
                data-is_full="{{ $account->is_full ? '1' : '0' }}"
                data-update-url="{{ route('manager.accounts.updateStock', $account->id) }}"
                data-bs-toggle="modal"
                data-bs-target="#stockModal">
                Edit Stock
            </button>

            <button type="button"
                class="btn btn-danger btn-sm deleteAccount"
                data-id="{{ $account->id }}"
                data-mail="{{ $account->mail }}">
                Delete
            </button>
        </div>
    </td>
    @endcan
</tr>
@endforeach
