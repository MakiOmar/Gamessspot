{{-- Payment history --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Payments</h5>
    @can('manage-traders')
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-cash-coin"></i> Add Payment
        </button>
    @endcan
</div>

<div class="table-responsive">
    <table class="table table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Number</th>
                <th>Date</th>
                <th class="text-right">Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Notes</th>
                <th>Added By</th>
                <th>Status</th>
                <th style="width: 140px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr class="{{ $payment->isCancelled() ? 'text-muted' : '' }}">
                    <td>@if ($payment->isCancelled())<del>{{ $payment->payment_number }}</del>@else{{ $payment->payment_number }}@endif</td>
                    <td>{{ $payment->payment_date?->toDateString() }}</td>
                    <td class="text-right">
                        @if ($payment->isCancelled())<del>{{ number_format($payment->amount, 2) }}</del>@else{{ number_format($payment->amount, 2) }}@endif
                    </td>
                    <td>{{ $payment->methodLabel() }}</td>
                    <td>{{ $payment->reference_number ?: '—' }}</td>
                    <td class="small">{{ $payment->notes ?: '—' }}</td>
                    <td>{{ $payment->creator?->name ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ $payment->isCancelled() ? 'secondary' : 'success' }}">{{ ucfirst($payment->status) }}</span>
                        @if ($payment->isCancelled())
                            <div class="small" title="{{ $payment->cancellation_reason }}">
                                by {{ $payment->canceller?->name ?? '—' }} {{ $payment->cancelled_at?->toDateString() }}
                                <br>{{ \Illuminate\Support\Str::limit($payment->cancellation_reason, 60) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if ($payment->attachment_path)
                            <a href="{{ route('manager.trader-payments.attachment', $payment) }}" class="btn btn-xs btn-outline-primary" title="Download attachment">
                                <i class="bi bi-paperclip"></i>
                            </a>
                        @endif
                        @can('void-trader-transactions')
                            @unless ($payment->isCancelled())
                                <button type="button" class="btn btn-xs btn-outline-danger void-payment"
                                        data-url="{{ route('manager.trader-payments.void', $payment) }}"
                                        data-label="{{ $payment->payment_number }}">Void</button>
                            @endunless
                        @endcan
                    </td>
                </tr>
            @empty
                {{-- Empty state --}}
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No payments recorded for this trader.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $payments->links('vendor.pagination.bootstrap-5') }}

@can('void-trader-transactions')
    @push('trader_tab_js')
    {{-- Void payment (admin only) --}}
    <script>
    jQuery(function ($) {
        $(document).on('click', '.void-payment', function () {
            TraderUi.confirmVoid($(this).data('url'), 'payment ' + $(this).data('label'), function () {
                setTimeout(function () { location.reload(); }, 600);
            });
        });
    });
    </script>
    @endpush
@endcan
