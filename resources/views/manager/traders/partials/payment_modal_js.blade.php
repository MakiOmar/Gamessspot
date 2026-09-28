{{-- Add payment submit (multipart, supports attachment) --}}
<script>
jQuery(function ($) {
    $('#paymentForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#paymentSaveBtn');
        var amount = parseFloat($('#paymentAmount').val() || '0');

        Swal.fire({
            title: 'Record payment?',
            text: amount.toFixed(2) + ' EGP will be credited to this trader.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Save'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            TraderUi.setBusy($btn, true);
            $.ajax({
                url: @js(route('manager.trader-payments.store', $trader)),
                method: 'POST',
                data: new FormData($('#paymentForm')[0]),
                processData: false,
                contentType: false
            }).done(function (res) {
                $('#paymentModal').modal('hide');
                TraderUi.toast('success', res.message);
                setTimeout(function () {
                    window.location = @js(route('manager.traders.show', array('trader' => $trader->id, 'tab' => 'payments')));
                }, 600);
            }).fail(function (xhr) {
                TraderUi.ajaxError(xhr, 'Could not save payment');
            }).always(function () { TraderUi.setBusy($btn, false); });
        });
    });
});
</script>
