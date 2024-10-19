@extends('layouts.admin')

@section('title', 'Orders with Needs Return Reports')

@section('content')
<div class="container">
    <h1>Orders Reported as Needs Return</h1>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Seller</th>
                    <th>Buyer Name</th>
                    <th>Buyer Phone</th>
                    <th>Price</th>
                    <th>Report Note</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->seller->name }}</td>
                        <td>{{ $order->buyer_name }}</td>
                        <td>{{ $order->buyer_phone }}</td>
                        <td>{{ $order->price }}</td>

                        {{-- Fetch the "needs_return" report --}}
                        @php
                            $needsReturnReport = $order->reports->where('status', 'needs_return')->first();
                        @endphp

                        <td>{{ $needsReturnReport->note ?? 'N/A' }}</td>
                        <td>
                            <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}" data-sold-item="{{ $order->sold_item }}">
                                Undo
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No orders reported as "Needs Return".</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
