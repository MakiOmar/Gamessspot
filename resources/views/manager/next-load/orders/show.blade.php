@extends('layouts.admin')

@section('title', 'Supplier order '.$order->reference)

@section('content_header_title', 'Next Load Supplier')
@section('content_header_subtitle', 'Order '.$order->reference)

@section('content_body')
@include('manager.next-load.partials.nav')

<div class="row">
    <div class="col-lg-5">
        {{-- Order summary --}}
        <div class="card">
            <div class="card-header"><strong>Order</strong></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5">Reference</dt><dd class="col-7 text-monospace">{{ $order->reference }}</dd>
                    <dt class="col-5">Next Load id</dt><dd class="col-7">{{ $order->next_load_order_id ?? '—' }}</dd>
                    <dt class="col-5">Status</dt><dd class="col-7">{{ str_replace('_', ' ', $order->status) }}</dd>
                    <dt class="col-5">Category</dt>
                    <dd class="col-7">
                        @if ($order->category)
                            <a href="{{ route('manager.next-load.links.edit', $order->category->id) }}">{{ $order->category->name }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-5">Product</dt><dd class="col-7">#{{ $order->product_id }} {{ $order->product_name }}</dd>
                    <dt class="col-5">Purpose</dt><dd class="col-7">{{ ucfirst($order->purpose) }}</dd>
                    <dt class="col-5">Shop order</dt><dd class="col-7">{{ $order->storefront_order_id ?? '—' }}</dd>
                    <dt class="col-5">Codes</dt><dd class="col-7">{{ $order->delivered_quantity }} / {{ $order->quantity }}</dd>
                    <dt class="col-5">Card price</dt><dd class="col-7">{{ $order->card_price ?? '—' }}</dd>
                    <dt class="col-5">Charged</dt><dd class="col-7">{{ $order->order_amount ?? '—' }}</dd>
                    <dt class="col-5">Requested by</dt><dd class="col-7">{{ $order->requester->name ?? 'Shop / system' }}</dd>
                    <dt class="col-5">Attempts</dt><dd class="col-7">{{ $order->attempts }}</dd>
                    <dt class="col-5">Created</dt><dd class="col-7">{{ $order->created_at?->format('Y-m-d H:i:s') }}</dd>
                    <dt class="col-5">Completed</dt><dd class="col-7">{{ $order->completed_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </dl>
                @if ($order->last_error)
                    <div class="alert alert-warning small mt-3 mb-0">{{ $order->last_error }}</div>
                @endif
            </div>
            <div class="card-footer">
                @if ($order->isOpen())
                    <button type="button" class="btn btn-sm btn-outline-primary" id="nlRefreshBtn">
                        <i class="bi bi-arrow-repeat"></i> Refresh status
                    </button>
                @endif
                @if ($order->isComplete() && ! $order->consumed_at)
                    <button type="button" class="btn btn-sm btn-outline-success" id="nlReleaseBtn">
                        <i class="bi bi-box-arrow-in-down"></i> Release to stock
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        {{-- Delivered codes: serials are masked; pins are only shown through the logged reveal --}}
        <div class="card">
            <div class="card-header"><strong>Delivered codes</strong></div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Serial</th>
                            <th class="text-right">Price</th>
                            <th>State</th>
                            <th style="width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->items as $item)
                            <tr>
                                <td class="text-monospace small">{{ $item->maskedSerial() }}</td>
                                <td class="text-right">{{ $item->price }}</td>
                                <td>
                                    @if (! $item->card)
                                        <span class="badge badge-light">unused</span>
                                    @elseif ($item->card->status)
                                        <span class="badge badge-success">in stock</span>
                                    @else
                                        <span class="badge badge-secondary">sold</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-dark nl-reveal"
                                            data-url="{{ route('manager.next-load.orders.reveal', array($order, $item)) }}">
                                        Reveal code
                                    </button>
                                </td>
                            </tr>
                        @empty
                            {{-- Empty state --}}
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No codes delivered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('manager.next-load.partials.helpers_js')
<script>
jQuery(function ($) {
    $('#nlRefreshBtn').on('click', function () {
        NextLoadUi.post($(this), @json(route('manager.next-load.orders.refresh', $order)));
    });

    $('#nlReleaseBtn').on('click', function () {
        NextLoadUi.post($(this), @json(route('manager.next-load.orders.release', $order)), {}, {
            title: 'Release codes to stock?',
            text: 'Unused codes on this order become available for any sale in this category.',
            confirmButtonText: 'Release'
        });
    });

    // Reveal is audited server-side; confirm first so it is never accidental.
    $('.nl-reveal').on('click', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Reveal this code?',
            text: 'The reveal is recorded in the activity log with your name.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Reveal',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return $.post(url).catch(function (xhr) {
                    Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || 'Could not reveal the code.');
                });
            },
            allowOutsideClick: function () { return !Swal.isLoading(); }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) return;
            Swal.fire({
                title: 'Code',
                html: '<p class="small text-muted mb-1">Serial: ' + NextLoadUi.escapeHtml(result.value.serial || '—') + '</p>' +
                      '<input type="text" class="form-control text-center text-monospace" readonly value="' + NextLoadUi.escapeHtml(result.value.pin) + '">',
                confirmButtonText: 'Close'
            });
        });
    });
});
</script>
@endpush
