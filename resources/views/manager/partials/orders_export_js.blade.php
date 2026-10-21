{{-- Queued sell log export: POST the filters, poll the job, then download the file. Survives page reloads via localStorage. --}}
<script>
(function ($) {
    var STORAGE_KEY = 'accounts_orders_export_pending';
    var POLL_MS = 3000;
    var MAX_POLLS = 400; // ~20 minutes, longer than the job timeout
    var $form = $('#ordersFilterForm');
    var $btn = $('#ordersExportBtn');
    var $status = $('#ordersExportStatus');
    var timer = null;
    var polls = 0;

    function toast(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 4000 });
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function setBusy(busy) {
        $btn.prop('disabled', busy);
        $status.toggleClass('d-none', !busy);
    }

    function remember(statusUrl) {
        try {
            if (statusUrl) {
                localStorage.setItem(STORAGE_KEY, statusUrl);
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) {
            // Storage unavailable: polling still works until the page is left.
        }
    }

    function finish() {
        clearTimeout(timer);
        remember(null);
        setBusy(false);
    }

    function poll(statusUrl) {
        setBusy(true);
        remember(statusUrl);
        clearTimeout(timer);
        if (++polls > MAX_POLLS) {
            finish();
            Swal.fire({ icon: 'warning', title: 'Export is taking too long', text: 'Please try exporting again in a few minutes.' });
            return;
        }
        timer = setTimeout(function () {
            $.getJSON(statusUrl)
                .done(function (res) {
                    if (res.status === 'completed' && res.download_url) {
                        finish();
                        toast('success', 'Export ready (' + (res.row_count || 0) + ' rows). Downloading…');
                        window.location = res.download_url;
                    } else if (res.status === 'failed') {
                        finish();
                        Swal.fire({ icon: 'error', title: 'Export failed', text: res.error || 'Please try again.' });
                    } else {
                        poll(statusUrl);
                    }
                })
                .fail(function (xhr) {
                    if (xhr.status === 403 || xhr.status === 404) {
                        finish();
                        return;
                    }
                    poll(statusUrl);
                });
        }, POLL_MS);
    }

    // Enter in a filter field searches instead of submitting (the only submit button is Export).
    $form.on('keydown', 'input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#customSearchBtn').trigger('click');
        }
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        setBusy(true);
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { Accept: 'application/json' }
        }).done(function (res) {
            toast('info', res.message);
            polls = 0;
            poll(res.status_url);
        }).fail(function (xhr) {
            setBusy(false);
            var res = xhr.responseJSON || {};
            if (xhr.status === 422 && res.errors) {
                var items = [];
                $.each(res.errors, function (field, messages) {
                    items.push('<li>' + escapeHtml(messages[0]) + '</li>');
                });
                Swal.fire({ icon: 'error', title: 'Please fix the following', html: '<ul class="text-start mb-0">' + items.join('') + '</ul>' });
            } else if (xhr.status === 429) {
                toast('warning', 'Too many export requests. Please wait a minute.');
            } else {
                toast('error', res.message || 'Could not start the export.');
            }
        });
    });

    $(function () {
        var pending = null;
        try {
            pending = localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            pending = null;
        }
        if (pending) {
            poll(pending);
        }
    });
})(jQuery);
</script>
