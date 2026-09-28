{{-- Overview: ledger stat cards and opening balance --}}
<div class="row">
    <div class="col-lg col-md-4 col-sm-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h4>{{ number_format($totals['purchases'], 2) }}</h4>
                <p>Total Purchases (EGP)</p>
            </div>
            <div class="icon"><i class="bi bi-bag"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h4>{{ number_format($totals['payments'], 2) }}</h4>
                <p>Total Payments (EGP)</p>
            </div>
            <div class="icon"><i class="bi bi-cash-stack"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6">
        <div class="small-box {{ $totals['balance'] > 0 ? 'bg-danger' : 'bg-secondary' }}">
            <div class="inner">
                <h4>{{ number_format($totals['balance'], 2) }}</h4>
                <p>Balance Due (EGP)</p>
            </div>
            <div class="icon"><i class="bi bi-scale"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-6 col-sm-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h4>{{ $totals['purchase_orders'] }}</h4>
                <p>Purchase Orders</p>
            </div>
            <div class="icon"><i class="bi bi-receipt"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-6 col-sm-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h4>{{ $totals['accounts'] }}</h4>
                <p>Accounts Purchased</p>
            </div>
            <div class="icon"><i class="bi bi-person-badge"></i></div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Opening balance --}}
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header"><strong>Opening Balance</strong></div>
            <div class="card-body">
                <p class="small text-muted">
                    Amount owed to this trader before tracking started. Positive means you owe the trader.
                </p>
                @can('manage-traders')
                    <form id="openingBalanceForm" class="form-row align-items-end">
                        <div class="form-group col-sm-5">
                            <label for="openingBalance">Amount (EGP)</label>
                            <input type="number" step="0.01" id="openingBalance" class="form-control" value="{{ $trader->opening_balance }}" required>
                        </div>
                        <div class="form-group col-sm-4">
                            <label for="openingBalanceDate">Date</label>
                            <input type="date" id="openingBalanceDate" class="form-control"
                                   value="{{ $trader->opening_balance_date?->toDateString() ?? now()->toDateString() }}" required>
                        </div>
                        <div class="form-group col-sm-3">
                            <button type="submit" class="btn btn-primary btn-block" id="openingBalanceSaveBtn">Save</button>
                        </div>
                    </form>
                @else
                    <p class="h5 mb-0">
                        {{ number_format($totals['opening_balance'], 2) }} EGP
                        <small class="text-muted">{{ $trader->opening_balance_date?->toDateString() }}</small>
                    </p>
                @endcan
            </div>
        </div>
    </div>

    {{-- Audit info --}}
    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header"><strong>Record Info</strong></div>
            <div class="card-body small">
                <div>Created: {{ $trader->created_at?->toDateTimeString() }} by {{ $trader->creator?->name ?? '—' }}</div>
                <div>Last modified: {{ $trader->updated_at?->toDateTimeString() }} by {{ $trader->updater?->name ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

@can('manage-traders')
    @push('trader_tab_js')
    {{-- Opening balance save --}}
    <script>
    jQuery(function ($) {
        $('#openingBalanceForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#openingBalanceSaveBtn');
            Swal.fire({
                title: 'Update opening balance?',
                text: 'This changes the trader balance and statement.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Update'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                TraderUi.setBusy($btn, true);
                $.post(@js(route('manager.traders.opening-balance', $trader)), {
                    _method: 'PUT',
                    opening_balance: $('#openingBalance').val(),
                    opening_balance_date: $('#openingBalanceDate').val()
                }).done(function (res) {
                    TraderUi.toast('success', res.message);
                    setTimeout(function () { location.reload(); }, 600);
                }).fail(function (xhr) {
                    TraderUi.ajaxError(xhr, 'Could not update opening balance');
                }).always(function () { TraderUi.setBusy($btn, false); });
            });
        });
    });
    </script>
    @endpush
@endcan
