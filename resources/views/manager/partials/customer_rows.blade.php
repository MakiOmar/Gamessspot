@foreach ($customers as $customer)
    <tr>
        <td>{{ $customer->buyer_phone }}</td>
        <td>{{ $customer->buyer_name }}</td>
        <td>
            <a href="{{ route('manager.orders', ['search' => $customer->buyer_phone]) }}" 
               class="btn btn-primary btn-sm" 
               title="View Orders">
                <i class="bi bi-receipt"></i> View Orders
            </a>
        </td>
    </tr>
@endforeach
