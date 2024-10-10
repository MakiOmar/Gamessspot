@foreach($orders as $order)
<tr>
    <td>{{ $order->id }}</td>
    <td>{{ $order->seller->name }}</td>
    <td>{{ $order->account->game->title }}</td>
    <td>{{ $order->buyer_phone }}</td>
    <td>{{ $order->buyer_name }}</td>
    <td>{{ $order->price }}</td>
    <td>{{ $order->sold_item }}</td>
    <td>{{ $order->notes }}</td>
</tr>
@endforeach
