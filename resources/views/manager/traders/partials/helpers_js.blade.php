{{-- Shared toast / error helpers for trader and purchase order pages --}}
<script>
window.TraderUi = window.TraderUi || (function ($) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function toast(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 3000 });
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    // Validation errors (422) are listed in a SweetAlert; everything else becomes an error toast.
    function ajaxError(xhr, fallback) {
        var res = xhr.responseJSON || {};
        if (xhr.status === 422 && res.errors) {
            var items = [];
            $.each(res.errors, function (field, messages) {
                items.push('<li>' + escapeHtml(messages[0]) + '</li>');
            });
            Swal.fire({ icon: 'error', title: 'Please fix the following', html: '<ul class="text-left mb-0">' + items.join('') + '</ul>' });
            return;
        }
        toast('error', res.message || fallback || 'Something went wrong.');
    }

    function setBusy($btn, busy) {
        if (busy) {
            $btn.data('label', $btn.html()).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm mr-1" role="status"></span>Saving…');
        } else {
            $btn.prop('disabled', false).html($btn.data('label'));
        }
    }

    // Void flow: SweetAlert asking for a mandatory reason, then POST.
    function confirmVoid(url, label, onDone) {
        Swal.fire({
            title: 'Void ' + label + '?',
            text: 'Voided records stay visible but no longer affect the balance. This cannot be undone.',
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Cancellation reason (required)',
            inputAttributes: { maxlength: 500 },
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Void',
            showLoaderOnConfirm: true,
            inputValidator: function (value) {
                if (!value || value.trim().length < 3) return 'Please enter a reason (at least 3 characters).';
            },
            preConfirm: function (reason) {
                return $.post(url, { reason: reason }).catch(function (xhr) {
                    var res = xhr.responseJSON || {};
                    var msg = res.message || 'Void failed';
                    if (res.errors) msg = Object.values(res.errors)[0][0];
                    Swal.showValidationMessage(msg);
                });
            },
            allowOutsideClick: function () { return !Swal.isLoading(); }
        }).then(function (result) {
            if (result.isConfirmed && result.value) {
                toast('success', result.value.message || 'Voided');
                if (onDone) onDone(result.value);
            }
        });
    }

    return { toast: toast, ajaxError: ajaxError, setBusy: setBusy, confirmVoid: confirmVoid, escapeHtml: escapeHtml };
})(jQuery);
</script>
