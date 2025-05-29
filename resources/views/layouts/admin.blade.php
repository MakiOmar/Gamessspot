@extends('adminlte::page')

{{-- Extend and customize the browser title --}}

@section('title')
    {{ config('adminlte.title') }}
    @hasSection('subtitle') | @yield('subtitle') @endif
@stop

{{-- Extend and customize the page content header --}}

@section('content_header')
    @hasSection('content_header_title')
        <h1 class="text-muted">
            @yield('content_header_title')

            @hasSection('content_header_subtitle')
                <small class="text-dark">
                    <i class="fas fa-xs fa-angle-right text-muted"></i>
                    @yield('content_header_subtitle')
                </small>
            @endif
        </h1>
    @endif
@stop

{{-- Rename section content to content_body --}}

@section('content')
    @yield('content_body')
@stop

{{-- Create a common footer --}}

@section('footer')
    <div class="float-right">
        Version: {{ config('app.version', '1.0.0') }}
    </div>

    <strong>
        <a href="{{ config('app.company_url', '#') }}">
            {{ config('app.company_name', 'My company') }}
        </a>
    </strong>
@stop

{{-- Add common Javascript/Jquery code --}}
@push('js')
    <script src="{{ asset('assets/js/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/js/popperjs.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/fontawesome-all.js') }}"></script>
    <script src="{{ asset('build/assets/app-CrG75o6_.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        (function ($) {
            $.fn.toggleTableColumns = function (options) {
                // Default settings
                const settings = $.extend({
                    columnStart: 5,
                    columnEnd: 10,
                    buttonClass: 'btn btn-primary',
                    buttonText: '<i class="fa fa-chevron-right"></i>',
                    buttonMargin: '10px',
                }, options);

                return this.each(function () {
                    const table = $(this);
                    const rows = table.find('tr');

                    // Check if a toggle button already exists
                    if (table.prev('.toggle-button').length === 0) {
                        // Create and inject the toggle button
                        const toggleButton = $('<div>')
                            .html(settings.buttonText)
                            .attr('href', '#')
                            .addClass(settings.buttonClass + ' toggle-button')
                            .css('margin-bottom', settings.buttonMargin)
                            .click(function (e) {
                                e.preventDefault();
                                // Toggle specific columns
                                rows.each(function () {
                                    $(this)
                                        .find(`th:nth-child(n+${settings.columnStart}):nth-child(-n+${settings.columnEnd}), td:nth-child(n+${settings.columnStart}):nth-child(-n+${settings.columnEnd})`)
                                        .toggleClass('d-none');
                                });
                            });

                        // Insert the button before the table
                        table.before(toggleButton);
                    }

                    // Initially hide columns
                    rows.each(function () {
                        $(this)
                            .find(`th:nth-child(n+${settings.columnStart}):nth-child(-n+${settings.columnEnd}), td:nth-child(n+${settings.columnStart}):nth-child(-n+${settings.columnEnd})`)
                            .addClass('d-none');
                    });
                });
            };
        })(jQuery);
        jQuery(document).ready(function ($) {
            $('#game,#region').select2({
                width: '100%',
                dropdownParent: $('#accountModal'),
            });
            const columnThreshold = 7; // Number of columns to keep visible
            // Target all tables with more than the threshold columns
            const columnStart = 5; // Starting column index to hide
            const columnEnd = 10; // Ending column index to hide
            /*
            $('table').toggleTableColumns({
                columnStart: 5,
                columnEnd: 10
            });
            */
        });
        (function ($) {
            $.fn.mobileTableToggle = function (options) {
                const settings = $.extend({
                    maxVisibleCols: 2,
                    maxVisibleColsDesktop: null, // إذا null يتم تجاهله
                    toggleTextShow: 'chevron',
                    toggleTextHide: 'chevron',
                    mobileBreakpoint: 768,
                    enableOnDesktop: false // الخيار الجديد
                }, options);

                const isMobile = $(window).width() <= settings.mobileBreakpoint;
                const isDesktop = $(window).width() > settings.mobileBreakpoint;

                if (!isMobile && !settings.enableOnDesktop) return this;

                return this.each(function () {
                    const $table = $(this);
                    const $headers = $table.find('thead th');
                    const totalCols = $headers.length;

                    // نحدد العدد المسموح به بناءً على حجم الشاشة
                    const maxVisible = isMobile ? settings.maxVisibleCols : (settings.maxVisibleColsDesktop || totalCols);

                    if (totalCols <= maxVisible) return;

                    $table.addClass('mobile-responsive-table');

                    const hiddenIndexes = [];
                    $headers.each(function (i) {
                        if (i >= maxVisible) {
                            $(this).addClass('mobile-hidden');
                            hiddenIndexes.push(i);
                        }
                    });

                    $table.find('tbody tr').each(function () {
                        const $row = $(this);
                        const $cells = $row.find('td');

                        hiddenIndexes.forEach(function (i) {
                            $cells.eq(i).addClass('mobile-hidden');
                        });

                        const $toggleBtn = $(`
                            <button class="toggle-details-btn">
                                <span class="chevron chevron-down"></span>
                            </button>
                        `);

                        // تفادي تكرار الحدث
                        $toggleBtn.on('click', function (e) {
                            e.preventDefault();
                            const $detailRow = $(this).closest('tr').next('.mobile-detail-row');
                            $detailRow.toggle();
                            $(this).find('.chevron').toggleClass('chevron-down chevron-up');
                        });

                        $cells.eq(maxVisible - 1).append($toggleBtn);

                        let detailHTML = '<tr class="mobile-detail-row"><td colspan="' + maxVisible + '">';
                        hiddenIndexes.forEach(function (i) {
                            const key = $headers.eq(i).text();
                            const val = $cells.eq(i).html();
                            detailHTML += '<div><strong>' + key + ':</strong> ' + val + '</div>';
                        });
                        detailHTML += '</td></tr>';
                        const $detailRow = $(detailHTML).hide();
                        $row.after($detailRow);
                    });
                });
            };
        })(jQuery);

    </script>
@endpush

{{-- Add common CSS customizations --}}

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="{{ asset('assets/js/sweetalert2.js') }}"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css">
<link rel="stylesheet" href="{{ asset('assets/css/flatpickr.css') }}">
<link rel="stylesheet" href="{{ asset('build/assets/app-DqME6eCz.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" integrity="sha256-Qsx5lrStHZyR9REqhUF8iQt73X06c8LGIUPzpOhwRrI=" crossorigin="anonymous"><!--end::Third Party Plugin(Bootstrap Icons)--><!--begin::Required Plugin(AdminLTE)-->
<style>
    @font-face {
        font-family: 'Arista';
        font-style: normal;
        font-weight: normal;
        src: url('/assets/fonts/arista/[z] Arista.woff') format('woff');
    }

    @font-face {
        font-family: 'Arista ExtraFilled';
        font-style: normal;
        font-weight: normal;
        src: url('/assets/fonts/arista/[z] Arista ExtraFilled.woff') format('woff');
    }

    @font-face {
        font-family: 'Arista Light';
        font-style: normal;
        font-weight: normal;
        src: url('/assets/fonts/arista/[z] Arista light.woff') format('woff');
    }
</style>

<style type="text/css">
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 18.6px;
    }
    a{
        text-decoration:none;
    }
    .brand-link{
        font-family: 'Arista';
    }
    .mobile-results-count{
        display: none;
    }
    @media screen and ( min-width:1200px ){
        .table-bordered{
            min-width: 1200px;
        }
    }
    @media screen and ( max-width:480px ){
        .small-box h3, .small-box .h3 {
            font-size: 18px;
        }
        .mobile-results-count{
            display: block;
        }
    }
    .page-item.active .page-link {
        background-color: #db890a;
        border-color: #db890a;
    }
    .page-link {
	    color: #db890a;
	}
    .page-link:hover{
        background-color: #000;
        color: #fff;
    }
    .text-bg-warning {
        color: #000 !important;
        background-color: RGB(178, 150, 65) !important;
    }
    .text-bg-danger {
        color: #fff !important;
        background-color: RGB(163, 65, 75) !important;
    }
    .navbar-search-block {
        left: auto;
        right: 70px;
        max-width: 500px;
    }
    #search-query{
        max-width: 400px;
    }
    {{-- You can add AdminLTE customizations here --}}
    .wraptext{
        word-wrap: break-word;white-space: normal;overflow-wrap: break-word;
    }
    .table-bordered th, .table-bordered td {
        vertical-align: middle;
    }
    .mobile-responsive-table td{
            position: relative;
        }
        .mobile-hidden {
            display: none !important;
        }

        .mobile-detail-row {
            display: none;
        }
        .mobile-detail-row td > div {
            word-wrap: break-word;   /* يُقسّم الكلمة الطويلة */
            white-space: normal;     /* يسمح بلف النص */
            overflow-wrap: break-word; /* دعم أوسع للمتصفحات الحديثة */
        }
        .toggle-details-btn {
            background-color: #eee;
            border: none;
            padding: 6px 12px;
            margin-top: 6px;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
            position: absolute;
            right: 10px;
            top: 0;
            margin: 0;
        }
        .toggle-details-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
        }

        .chevron {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-right: 2px solid #333;
            border-bottom: 2px solid #333;
            transform: rotate(45deg);
            transition: transform 0.3s ease;
            margin-left: 5px;
        }

        .chevron-down {
            transform: rotate(45deg);
        }

        .chevron-up {
            transform: rotate(-135deg);
        }

    /*
    .card-header {
        border-bottom: none;
    }
    .card-title {
        font-weight: 600;
    }
    */
    .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active, .sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
        background-color: #db890a;
        color: #fff;
    }
    .btn-primary {
        --bs-btn-color: #fff;
        --bs-btn-bg: #000;
        --bs-btn-border-color: #000;
        --bs-btn-hover-color: #fff;
        --bs-btn-hover-bg: #000;
        --bs-btn-hover-border-color: #000;
        --bs-btn-focus-shadow-rgb: 49, 132, 253;
        --bs-btn-active-color: #fff;
        --bs-btn-active-bg: #000;
        --bs-btn-active-border-color: #000;
        --bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
        --bs-btn-disabled-color: #fff;
        --bs-btn-disabled-bg: #000;
        --bs-btn-disabled-border-color: #000;
    }
    .btn-primary:not(:disabled):not(.disabled):hover,.btn-primary:not(:disabled):not(.disabled):active, .btn-primary:not(:disabled):not(.disabled).active, .show > .btn-primary.dropdown-toggle {
        color: #fff;
        background-color: #db890a;
        border-color: #db890a;
    }
    [class*="sidebar-dark-"] {
        background-color: #080808;
    }
</style>
@endpush