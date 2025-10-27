@foreach ($customers as $customer)
    <tr>
        <td>{{ $customer->buyer_phone }}</td>
        <td>{{ $customer->buyer_name }}</td>
        <td>
            <a href="{{ route('manager.orders', ['search' => $customer->buyer_phone, 'start_date' => '2000-01-01', 'end_date' => date('Y-m-d', strtotime('+1 year'))]) }}" 
               class="btn btn-primary btn-sm" 
               title="View All Orders">
                <i class="bi bi-receipt"></i> View Orders
            </a>
        </td>
    </tr>
@endforeach
