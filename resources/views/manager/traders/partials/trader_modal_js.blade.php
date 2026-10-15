{{-- Create / edit trader modal behaviour --}}
<script>
jQuery(function ($) {
    var $modal = $('#traderModal');
    var $form = $('#traderForm');
    var storeUrl = @json(route('manager.traders.store'));
    var updateUrlTemplate = @js(route('manager.traders.update', array('trader' => '__ID__')));

    function openModal(trader) {
        $form[0].reset();
        $('#traderId').val(trader ? trader.id : '');
        $('#traderModalTitle').text(trader ? 'Edit Trader' : 'New Trader');
        $('#traderOpeningBalanceRow').toggle(!trader);
        if (trader) {
            $('#traderName').val(trader.name);
            $('#traderPhone').val(trader.phone || '');
            $('#traderWhatsapp').val(trader.whatsapp || '');
            $('#traderNotes').val(trader.notes || '');
            $('#traderStatus').val(trader.status);
        }
        $modal.modal('show');
    }

    $('#createTraderBtn').on('click', function () { openModal(null); });
    $(document).on('click', '.edit-trader', function () { openModal($(this).data('trader')); });

    $form.on('submit', function (e) {
        e.preventDefault();
        var id = $('#traderId').val();
        var data = {
            name: $('#traderName').val(),
            phone: $('#traderPhone').val(),
            whatsapp: $('#traderWhatsapp').val(),
            notes: $('#traderNotes').val(),
            status: $('#traderStatus').val()
        };
        if (!id && $('#traderOpeningBalance').val() !== '') {
            data.opening_balance = $('#traderOpeningBalance').val();
            data.opening_balance_date = $('#traderOpeningBalanceDate').val();
        }
        if (id) data._method = 'PUT';

        var $btn = $('#traderSaveBtn');
        TraderUi.setBusy($btn, true);
        $.post(id ? updateUrlTemplate.replace('__ID__', id) : storeUrl, data)
            .done(function (res) {
                $modal.modal('hide');
                TraderUi.toast('success', res.message);
                setTimeout(function () { window.location = res.redirect || window.location.href; }, 600);
            })
            .fail(function (xhr) { TraderUi.ajaxError(xhr, 'Could not save trader'); })
            .always(function () { TraderUi.setBusy($btn, false); });
    });

    // Blocked delete; admins (force_delete present) may continue to the force delete confirmation.
    function showDeleteBlocked(res, name) {
        var items = $.map(res.reasons || [], function (reason) {
            return '<li>' + TraderUi.escapeHtml(reason) + '</li>';
        });
        Swal.fire({
            icon: 'error',
            title: TraderUi.escapeHtml(res.message || 'Trader cannot be deleted'),
            html: '<ul class="text-left mb-2">' + items.join('') + '</ul>'
                + '<p class="small text-muted mb-0">Set the trader to Inactive instead to hide it from new purchases.</p>',
            showDenyButton: !!res.force_delete,
            denyButtonText: 'Force delete everything',
            confirmButtonText: 'OK'
        }).then(function (result) {
            if (result.isDenied && res.force_delete) confirmForceDelete(res.force_delete, name);
        });
    }

    function forceDeleteSummaryHtml(summary) {
        var rows = [
            summary.purchase_orders + ' purchase order(s)',
            summary.payments + ' payment(s) and their receipts',
            summary.accounts + ' game account(s)'
        ];
        var html = '<ul class="text-left mb-2">' + $.map(rows, function (row) { return '<li>' + row + '</li>'; }).join('') + '</ul>';
        if (summary.sold_accounts > 0) {
            html += '<p class="small text-danger text-left">' + summary.sold_accounts + ' of these accounts were sold ('
                + summary.sales + ' sale(s)). The sales stay, but will no longer show which account was sold.</p>';
        }
        return html;
    }

    // Final step: type the trader name + give a reason; the server re-checks both.
    function confirmForceDelete(force, name) {
        Swal.fire({
            icon: 'warning',
            title: 'Force delete "' + TraderUi.escapeHtml(name) + '"?',
            html: '<p class="text-left mb-1">This permanently deletes the trader and:</p>'
                + forceDeleteSummaryHtml(force.summary)
                + '<p class="text-left font-weight-bold text-danger mb-2">This cannot be undone.</p>'
                + '<input id="forceDeleteName" class="swal2-input" autocomplete="off" placeholder="Type the trader name to confirm">'
                + '<textarea id="forceDeleteReason" class="swal2-textarea" maxlength="500" placeholder="Reason (required)"></textarea>',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Delete everything',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                var typed = $.trim($('#forceDeleteName').val());
                var reason = $.trim($('#forceDeleteReason').val());
                if (typed !== $.trim(name)) return Swal.showValidationMessage('The typed name does not match the trader name.');
                if (reason.length < 3) return Swal.showValidationMessage('Please enter a reason (at least 3 characters).');
                return $.post(force.url, { confirm_name: typed, reason: reason }).catch(function (xhr) {
                    var res = xhr.responseJSON || {};
                    var msg = res.message || 'Force delete failed';
                    if (res.errors) msg = Object.values(res.errors)[0][0];
                    Swal.showValidationMessage(msg);
                });
            },
            allowOutsideClick: function () { return !Swal.isLoading(); }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) return;
            TraderUi.toast('success', result.value.message || 'Trader deleted.');
            setTimeout(function () { window.location = result.value.redirect || window.location.href; }, 600);
        });
    }

    $(document).on('click', '.delete-trader', function () {
        var url = $(this).data('url');
        var name = $(this).data('name');
        Swal.fire({
            title: 'Delete trader "' + TraderUi.escapeHtml(name) + '"?',
            text: 'This permanently removes the trader. It is only allowed when the trader has no purchase orders, accounts or payments.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Delete',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return $.post(url, { _method: 'DELETE' }).catch(function (xhr) {
                    return { failed: true, xhr: xhr };
                });
            },
            allowOutsideClick: function () { return !Swal.isLoading(); }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) return;
            if (result.value.failed) {
                var xhr = result.value.xhr;
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.reasons) {
                    showDeleteBlocked(xhr.responseJSON, name);
                } else {
                    TraderUi.ajaxError(xhr, 'Could not delete trader');
                }
                return;
            }
            TraderUi.toast('success', result.value.message || 'Trader deleted.');
            setTimeout(function () { window.location = result.value.redirect || window.location.href; }, 600);
        });
    });
});
</script>
