@extends('layouts.admin')

@section('title', $order ? 'Edit ' . $order->po_number : 'New Purchase Order')

@section('content_header_title', $order ? 'Edit ' . $order->po_number : 'New Purchase Order')
@section('content_header_subtitle', 'Traders')

@section('content_body')
<form id="purchaseOrderForm" novalidate>
    {{-- Order header --}}
    <div class="card">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="poTrader">Trader <span class="text-danger">*</span></label>
                    <select id="poTrader" class="form-control" required>
                        <option value="">Select trader…</option>
                        @foreach ($traders as $trader)
                            <option value="{{ $trader->id }}" {{ (int) $selectedTraderId === $trader->id ? 'selected' : '' }}>{{ $trader->name }}</option>
                        @endforeach
                    </select>
                    @if ($traders->isEmpty())
                        <small class="text-danger">No active traders. <a href="{{ route('manager.traders.index') }}">Create a trader first.</a></small>
                    @endif
                </div>
                <div class="form-group col-md-3">
                    <label for="poDate">Purchase date <span class="text-danger">*</span></label>
                    <input type="date" id="poDate" class="form-control" required max="{{ now()->toDateString() }}"
                           value="{{ $order?->purchase_date?->toDateString() ?? now()->toDateString() }}">
                </div>
                <div class="form-group col-md-5">
                    <label for="poNotes">Notes</label>
                    <input type="text" id="poNotes" class="form-control" maxlength="2000" value="{{ $order?->notes }}">
                </div>
            </div>
            @if ($order && $lines->sum('imported') > 0)
                <div class="alert alert-info small mb-0">
                    <i class="bi bi-lock"></i> Accounts were already imported: trader and purchase date are locked, and lines with imported
                    accounts keep their game and cost (quantity can't go below the imported count).
                </div>
            @endif
        </div>
    </div>

    {{-- Game lines --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Games</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addLineBtn"><i class="bi bi-plus-lg"></i> Add game</button>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm mb-0" id="linesTable">
                <thead class="thead-light">
                    <tr>
                        <th style="min-width: 260px;">Game</th>
                        <th style="width: 130px;">Quantity</th>
                        <th style="width: 160px;">Cost / account</th>
                        <th class="text-right" style="width: 160px;">Line total</th>
                        <th style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody id="linesBody"></tbody>
                <tfoot>
                    {{-- Empty state row, toggled by JS --}}
                    <tr id="linesEmpty"><td colspan="5" class="text-center text-muted py-3">No games yet. Click “Add game”.</td></tr>
                    <tr class="font-weight-bold">
                        <td class="text-right">Total</td>
                        <td id="totalQuantity">0</td>
                        <td></td>
                        <td class="text-right" id="totalCost">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ $order ? route('manager.purchase-orders.show', $order) : ($selectedTraderId ? route('manager.traders.show', array('trader' => $selectedTraderId, 'tab' => 'purchase-orders')) : route('manager.traders.index')) }}"
           class="btn btn-secondary mr-2">Cancel</a>
        <button type="submit" class="btn btn-primary" id="poSaveBtn">{{ $order ? 'Save changes' : 'Create purchase order' }}</button>
    </div>
</form>

{{-- Game options template reused by every line --}}
<template id="gameOptionsTemplate">
    <option value="">Select game…</option>
    @foreach ($games as $game)
        <option value="{{ $game->id }}">{{ $game->title }}</option>
    @endforeach
</template>
@endsection

@push('js')
@include('manager.traders.partials.helpers_js')
<script>
jQuery(function ($) {
    var isEdit = @json((bool) $order);
    var existingLines = @json($lines);
    var hasImported = existingLines.some(function (l) { return l.imported > 0; });
    var optionsHtml = $('#gameOptionsTemplate').html();
    var $body = $('#linesBody');

    function money(value) {
        return (Math.round(value * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalc() {
        var qty = 0, total = 0;
        $body.find('tr').each(function () {
            var q = parseInt($(this).find('.line-qty').val() || '0', 10);
            var c = parseFloat($(this).find('.line-cost').val() || '0');
            var lineTotal = (q > 0 && c >= 0) ? q * c : 0;
            $(this).find('.line-total').text(money(lineTotal));
            qty += q > 0 ? q : 0;
            total += lineTotal;
        });
        $('#totalQuantity').text(qty);
        $('#totalCost').text(money(total));
        $('#linesEmpty').toggle($body.find('tr').length === 0);
    }

    function addLine(line) {
        line = line || { id: '', game_id: '', quantity: 1, cost_per_account: '', imported: 0 };
        var locked = line.imported > 0;
        var $row = $(
            '<tr>' +
                '<td><select class="form-control form-control-sm line-game"></select>' +
                    (locked ? '<small class="text-muted"><i class="bi bi-lock"></i> ' + line.imported + ' imported</small>' : '') + '</td>' +
                '<td><input type="number" class="form-control form-control-sm line-qty" min="' + Math.max(1, line.imported) + '" step="1"></td>' +
                '<td><input type="number" class="form-control form-control-sm line-cost" min="0" step="0.01"></td>' +
                '<td class="text-right align-middle line-total">0.00</td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Remove"><i class="bi bi-trash"></i></button></td>' +
            '</tr>'
        );
        $row.data('id', line.id).data('imported', line.imported);
        $row.find('.line-game').html(optionsHtml).val(String(line.game_id || '')).prop('disabled', locked);
        $row.find('.line-qty').val(line.quantity);
        $row.find('.line-cost').val(line.cost_per_account).prop('readonly', locked);
        $row.find('.remove-line').prop('disabled', locked);
        $body.append($row);
        $row.find('.line-game').select2({ width: '100%' });
        recalc();
    }

    $('#addLineBtn').on('click', function () { addLine(); });
    $body.on('input change', '.line-qty, .line-cost', recalc);
    $body.on('click', '.remove-line', function () {
        var $row = $(this).closest('tr');
        Swal.fire({ title: 'Remove this game line?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Remove' })
            .then(function (r) { if (r.isConfirmed) { $row.remove(); recalc(); } });
    });

    if (existingLines.length) {
        existingLines.forEach(addLine);
    } else {
        addLine();
    }

    if (hasImported) {
        $('#poTrader, #poDate').prop('disabled', true);
    }

    $('#purchaseOrderForm').on('submit', function (e) {
        e.preventDefault();
        var items = [];
        $body.find('tr').each(function () {
            items.push({
                id: $(this).data('id') || null,
                game_id: $(this).find('.line-game').val(),
                quantity: $(this).find('.line-qty').val(),
                cost_per_account: $(this).find('.line-cost').val()
            });
        });

        var payload = {
            trader_id: $('#poTrader').val(),
            purchase_date: $('#poDate').val(),
            notes: $('#poNotes').val(),
            items: items
        };

        var $btn = $('#poSaveBtn');
        Swal.fire({
            title: isEdit ? 'Save changes?' : 'Create purchase order?',
            html: 'Total: <strong>' + $('#totalCost').text() + ' EGP</strong> for <strong>' + $('#totalQuantity').text() + '</strong> account(s).',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isEdit ? 'Save' : 'Create'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            TraderUi.setBusy($btn, true);
            $.ajax({
                url: @js($order ? route('manager.purchase-orders.update', $order) : route('manager.purchase-orders.store')),
                method: isEdit ? 'PUT' : 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(payload)
            }).done(function (res) {
                TraderUi.toast('success', res.message);
                setTimeout(function () { window.location = res.redirect; }, 600);
            }).fail(function (xhr) {
                TraderUi.ajaxError(xhr, 'Could not save purchase order');
                TraderUi.setBusy($btn, false);
            });
        });
    });
});
</script>
@endpush
