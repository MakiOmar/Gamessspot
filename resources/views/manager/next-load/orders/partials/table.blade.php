{{-- Supplier orders table (overview, orders list, link page) --}}
<table class="table table-sm table-hover mb-0">
    <thead class="thead-light">
        <tr>
            <th>#</th>
            <th>Reference</th>
            <th>Category</th>
            <th>Purpose</th>
            <th class="text-center">Codes</th>
            <th class="text-right">Card price</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $order)
            <tr>
                <td><a href="{{ route('manager.next-load.orders.show', $order) }}">{{ $order->id }}</a></td>
                <td class="small text-monospace">{{ $order->reference }}</td>
                <td>{{ $order->category->name ?? '—' }}</td>
                <td>{{ ucfirst($order->purpose) }}{{ $order->storefront_order_id ? ' (shop)' : '' }}</td>
                <td class="text-center">{{ $order->delivered_quantity }} / {{ $order->quantity }}</td>
                <td class="text-right">{{ $order->card_price ?? '—' }}</td>
                <td>
                    @php($color = array('complete' => 'success', 'in_progress' => 'info', 'pending_send' => 'warning', 'failed' => 'danger')[$order->status] ?? 'secondary')
                    <span class="badge badge-{{ $color }}">{{ str_replace('_', ' ', $order->status) }}</span>
                    @if ($order->isComplete() && ! $order->consumed_at)
                        <span class="badge badge-light" title="Delivered codes not yet sold or in stock">unused</span>
                    @endif
                </td>
                <td class="small">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            {{-- Empty state --}}
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-receipt d-block mb-2" style="font-size: 2rem;"></i>
                    No supplier orders yet.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
