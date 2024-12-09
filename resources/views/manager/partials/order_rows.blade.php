@foreach ($orders as $order)
    <tr>
        <td>{{ $order->id }}</td>
        <td>{{ $order->seller->name }}</td>

        @if($order->account)
            <td>{{ $order->account->game->title }}</td>
            <td>{{ $order->account->mail }}</td>
            @if(! Auth::user()->roles->contains('name', 'accountant') )
            <td>{{ $order->account->password }}</td>
            @endif
        @elseif($order->card)
            <td>{{ $order->card->category->name }}</td>
            @if(! Auth::user()->roles->contains('name', 'accountant') )
            <td>{{ $order->card->code }}</td>
            @endif
            <td>--</td>
        @endif

        <td>{{ $order->buyer_phone }}</td>
        <td>{{ $order->buyer_name }}</td>
        <td>{{ $order->price }}</td>
        <td>{{ $order->sold_item }}</td>
        <td>{{ $order->notes }}</td>
        <td>{{ $order->created_at }}</td>
        <td>
            @if (isset($status) && 'needs_return' === $status)
                <!-- Button for orders with 'needs_return' -->
                <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}"
                    data-sold-item="{{ $order->sold_item }}" data-report-id="{{ $order->reports->first()->id }}">
                    Undo
                </button>
            @elseif(isset($status) && 'has_problem' === $status)
                <button class="btn btn-success btn-sm solve-problem" data-report-id="{{ $order->reports->first()->id }}">
                    Mark as Solved
                </button>
            @elseif(isset($status) && 'solved' === $status)
            @else
                @if(Auth::user()->roles->contains('name', 'admin')  || Auth::user()->roles->contains('name', 'sales'))
                    @if ( Auth::user()->roles->contains('name', 'admin') )
                    <!-- Regular undo button -->
                    <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}" data-sold-item="{{ $order->sold_item }}">
                        Undo
                    </button>
                    @endif
                    <!-- Button to open report modal for sales -->
                    <button class="btn btn-warning btn-sm report-order" data-order-id="{{ $order->id }}" data-toggle="modal" data-target="#reportOrderModal">
                        Actions
                    </button>
                @endif
            @endif
        </td>
    </tr>
@endforeach