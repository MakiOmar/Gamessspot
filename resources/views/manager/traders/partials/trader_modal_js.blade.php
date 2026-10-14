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

    function showDeleteBlocked(res) {
        var items = $.map(res.reasons || [], function (reason) {
            return '<li>' + TraderUi.escapeHtml(reason) + '</li>';
        });
        Swal.fire({
            icon: 'error',
            title: TraderUi.escapeHtml(res.message || 'Trader cannot be deleted'),
            html: '<ul class="text-left mb-2">' + items.join('') + '</ul>'
                + '<p class="small text-muted mb-0">Set the trader to Inactive instead to hide it from new purchases.</p>'
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
                    showDeleteBlocked(xhr.responseJSON);
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
