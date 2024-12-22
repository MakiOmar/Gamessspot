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
    <script>
        jQuery(document).ready(function ($) {
            const columnThreshold = 7; // Number of columns to keep visible
            // Target all tables with more than the threshold columns
            $(document).ready(function () {
                const columnStart = 5; // Starting column index to hide
                const columnEnd = 9; // Ending column index to hide

                $('table').each(function () {
                    const table = $(this);
                    const rows = table.find('tr');

                    // Create and inject the toggle button
                    const toggleButton = $('<div>')
                        .html('<i class="fa fa-chevron-right"></i>')
                        .attr('href', '#')
                        .addClass('btn btn-primary') // Optional styling
                        .css('margin-bottom', '10px') // Add some spacing
                        .click(function (e) {
                            e.preventDefault();
                            // Toggle specific columns
                            rows.each(function () {
                                $(this)
                                    .find(`th:nth-child(n+${columnStart}):nth-child(-n+${columnEnd}), td:nth-child(n+${columnStart}):nth-child(-n+${columnEnd})`)
                                    .toggleClass('d-none');
                            });
                        });

                    // Insert the button before the table
                    table.before(toggleButton);

                    // Initially hide columns 5 to 9
                    rows.each(function () {
                        $(this)
                            .find(`th:nth-child(n+${columnStart}):nth-child(-n+${columnEnd}), td:nth-child(n+${columnStart}):nth-child(-n+${columnEnd})`)
                            .addClass('d-none');
                    });
                });
            });
        });
    </script>
@endpush

{{-- Add common CSS customizations --}}

@push('css')
<script src="{{ asset('assets/js/sweetalert2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('assets/css/flatpickr.css') }}">
<link rel="stylesheet" href="{{ asset('build/assets/app-DqME6eCz.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" integrity="sha256-Qsx5lrStHZyR9REqhUF8iQt73X06c8LGIUPzpOhwRrI=" crossorigin="anonymous"><!--end::Third Party Plugin(Bootstrap Icons)--><!--begin::Required Plugin(AdminLTE)-->
<style type="text/css">

    {{-- You can add AdminLTE customizations here --}}
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