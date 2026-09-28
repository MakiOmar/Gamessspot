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
});
</script>
