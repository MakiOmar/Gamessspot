{{-- Purchase orders of this trader --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Purchase Orders</h5>
    @can('manage-traders')
        @if ($trader->isActive())
            <a href="{{ route('manager.purchase-orders.create', array('trader' => $trader->id)) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </a>
        @endif
    @endcan
</div>

<div class="table-responsive">
    <table class="table table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>PO Number</th>
                <th>Date</th>
                <th class="text-center">Accounts (imported / ordered)</th>
                <th class="text-right">Total Cost</th>
                <th>Status</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchaseOrders as $order)
                <tr class="{{ $order->isCancelled() ? 'text-muted' : '' }}">
                    <td>
                        <a href="{{ route('manager.purchase-orders.show', $order) }}" class="{{ $order->isCancelled() ? 'text-muted' : '' }}">
                            @if ($order->isCancelled())<del>{{ $order->po_number }}</del>@else{{ $order->po_number }}@endif
                        </a>
                    </td>
                    <td>{{ $order->purchase_date?->toDateString() }}</td>
                    <td class="text-center">{{ $order->accounts_count }} / {{ $order->total_quantity }}</td>
                    <td class="text-right">
                        @if ($order->isCancelled())<del>{{ number_format($order->total_cost, 2) }}</del>@else{{ number_format($order->total_cost, 2) }}@endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $order->isCancelled() ? 'secondary' : 'success' }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td>{{ $order->creator?->name ?? '—' }}</td>
                </tr>
            @empty
                {{-- Empty state --}}
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No purchase orders yet for this trader.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $purchaseOrders->links('vendor.pagination.bootstrap-5') }}
