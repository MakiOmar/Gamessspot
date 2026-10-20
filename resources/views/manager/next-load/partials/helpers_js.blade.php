{{-- Toast / confirm / AJAX helpers for the Next Load pages --}}
<script>
window.NextLoadUi = window.NextLoadUi || (function ($) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function toast(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 3500 });
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    // Validation errors (422 with `errors`) are listed in a SweetAlert; everything else becomes an error toast.
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

    function setBusy($btn, busy, label) {
        if (busy) {
            $btn.data('label', $btn.html()).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm mr-1" role="status"></span>' + (label || 'Working…'));
        } else {
            $btn.prop('disabled', false).html($btn.data('label'));
        }
    }

    // POST after an optional SweetAlert confirmation; toasts the result and follows `redirect` or reloads.
    function post($btn, url, data, confirmOptions, reload) {
        var run = function () {
            setBusy($btn, true);
            return $.post(url, data || {})
                .done(function (res) {
                    toast(res.success === false ? 'warning' : 'success', res.message || 'Done');
                    if (res.redirect) {
                        setTimeout(function () { window.location = res.redirect; }, 700);
                    } else if (reload !== false) {
                        setTimeout(function () { window.location.reload(); }, 900);
                    }
                })
                .fail(function (xhr) { ajaxError(xhr); })
                .always(function () { setBusy($btn, false); });
        };

        if (!confirmOptions) {
            return run();
        }

        Swal.fire($.extend({ icon: 'question', showCancelButton: true, confirmButtonText: 'Continue' }, confirmOptions))
            .then(function (result) { if (result.isConfirmed) run(); });
    }

    return { toast: toast, ajaxError: ajaxError, setBusy: setBusy, post: post, escapeHtml: escapeHtml };
})(jQuery);
</script>
