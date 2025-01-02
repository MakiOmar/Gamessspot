@foreach ($customers as $customer)
    <tr>
        <td>{{ $customer->buyer_phone }}</td>
        <td>{{ $customer->buyer_name }}</td>
    </tr>
@endforeach
