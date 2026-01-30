@extends('layouts.admin')

@section('title', 'Manager - Orders')
@push('css')
<style>
    @media screen and ( min-width:1200px ){
        .orders-reponsive-table{
            min-width: 1200px;
        }
    }
</style>
@endpush
@php
    $status = $status ?? 'all';
@endphp

@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">
            @if (! isset($status))
            Orders Management
            @else
            Reports Management
            @endif
        </h1>
        <!-- Display Success Message -->
        @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        <!-- Display Error Message -->
        @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif
        <div class="container-fluid">
            <form action="{{ route('manager.orders.export') }}" method="GET">
                <div class="row g-2">
                    <!-- Search Input -->
                    <div class="col-12 col-md-4">
                        <input type="text" class="form-control" name="searchOrder" id="searchOrder" placeholder="Search orders by buyer phone">
                        <input type="hidden" id="storeId" value="@if( ! empty( $_GET['id'] ) ){{ $_GET['id'] }}@else{{0}}@endif">
                    </div>

                    <!-- Date Range -->
                    @if( ! Auth::user()->roles->contains('name', 'sales') )
                        <div class="col-12 col-md-6 d-flex flex-column flex-md-row align-items-md-center">
                            <input type="date" class="form-control mb-2 mb-md-0 me-md-2"
                                id="startDate" name="start_date"
                                value="{{ request('start_date', date('Y-m-d')) }}">
                            
                            <input type="date" class="form-control"
                                id="endDate" name="end_date"
                                value="{{ request('end_date', date('Y-m-d')) }}">
                        </div>
                    @endif

                    <!-- Show All Checkbox -->
                    <div class="col-12 col-md-2 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showAllCheckbox" name="show_all">
                            <label class="form-check-label" for="showAllCheckbox">
                                Show All (No Pagination)
                            </label>
                        </div>
                    </div>

                    <!-- Custom Search Button -->
                    <div class="col-12 col-md-2">
                        <button type="button" id="customSearchBtn" class="btn btn-primary w-100">Search</button>
                    </div>

                </div>
        
                @if(Auth::user()->roles->contains('name', 'admin') || Auth::user()->roles->contains('name', 'accountant'))
                <div class="row mt-3">
                    <div class="col-12 d-flex justify-content-end">
                        @if(!empty($_GET['id']))
                            <input type="hidden" name="id" value="{{ $_GET['id'] }}">
                        @endif
                        <button type="submit" class="btn p-0 border-0 bg-transparent">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="36px" height="36px">
                                <path fill="#4CAF50" d="M41,10H25v28h16c0.553,0,1-0.447,1-1V11C42,10.447,41.553,10,41,10z" />
                                <path fill="#FFF"
                                      d="M32 15H39V18H32zM32 25H39V28H32zM32 30H39V33H32zM32 20H39V23H32zM25 15H30V18H25zM25 25H30V28H25zM25 30H30V33H25zM25 20H30V23H25z" />
                                <path fill="#2E7D32" d="M27 42L6 38 6 10 27 6z" />
                                <path fill="#FFF"
                                      d="M19.129,31l-2.411-4.561c-0.092-0.171-0.186-0.483-0.284-0.938h-0.037c-0.046,0.215-0.154,0.541-0.324,0.979L13.652,31H9.895l4.462-7.001L10.274,17h3.837l2.001,4.196c0.156,0.331,0.296,0.725,0.42,1.179h0.04c0.078-0.271,0.224-0.68,0.439-1.22L19.237,17h3.515l-4.199,6.939l4.316,7.059h-3.74V31z" />
                            </svg>
                        </button>
                    </div>
                </div>
                @endif
            </form>
        </div>
        

        <!-- Scrollable table container -->
        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            <form method="post" action="{{ route('manager.orders.sendToPos') }}" id="sendToPosForm">
                @csrf
                <table class="table table-striped table-bordered orders-responsive-table">
                    <thead>
                        <tr role="row">
                            <th><input type="checkbox" id="select_all" /></th>
                            <th>ID</th>
                            <th>Seller</th>
                            <th>Product</th>
                            <th>Account</th>
                            @if(! Auth::user()->roles->contains('name', 'accountant') )
                            <th>Password</th>
                            @endif
                            <th>Buyer Phone</th>
                            <th>Buyer Name</th>
                            <th>Price</th>
                            <th>Sold Item</th>
                            <th>Notes</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="orderTableBody">
                        @include('manager.partials.order_rows', ['orders' => $orders, 'status' => $status])
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="100%">
                                <div id="orderPagination">
                                    @if(request()->has('id'))
                                        {{ $orders->appends(['id' => request()->get('id')])->links('vendor.pagination.bootstrap-5') }}
                                    @else
                                        {{ $orders->links('vendor.pagination.bootstrap-5') }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                @unless(Auth::user()->hasRole('accountant'))
                    <p>
                        <input type="submit" name="bulk_send_odoo" class="btn btn-primary" value="{{ __('Send to POS') }}" id="sendToPosBtn" />
                    </p>
                @endunless

            </form>
            
            @unless(Auth::user()->hasRole('accountant'))
            <!-- Form for unsending orders from POS -->
            <form method="post" action="{{ route('manager.orders.unsendFromPos') }}" id="unsendPosForm" style="display: none;">
                @csrf
                <div id="unsendOrderIds"></div>
                <p>
                    <input type="submit" name="bulk_unsend_pos" class="btn btn-warning" value="{{ __('Unsend from POS') }}" />
                </p>
            </form>
            @endunless
        </div>
        @if (isset($status))
        <input type="hidden" id="currentReportStatus" value="{{$status}}"/>
        @endif
    </div>
    <!-- Report Order Modal -->
    <div class="modal fade" id="reportOrderModal" tabindex="-1" aria-labelledby="reportOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportOrderModalLabel">Report an Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm" method="POST" action="{{ route('manager.reports.store') }}">
                        @csrf
                        <!-- Order ID (hidden) -->
                        <input type="hidden" name="order_id" id="reportOrderId">

                        <!-- Report Status -->
                        <div class="mb-3">
                            <label for="reportStatus" class="form-label">Report Status</label>
                            <select name="status" id="reportStatus" class="form-control" required>
                                <option value="has_problem">Has Problem</option>
                                <option value="needs_return">Needs Return</option>
                            </select>
                        </div>

                        <!-- Report Note -->
                        <div class="mb-3">
                            <label for="reportNote" class="form-label">Note</label>
                            <textarea name="note" id="reportNote" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">Submit Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        jQuery(document).ready(function($) {
            $('.orders-responsive-table').mobileTableToggle({
                maxVisibleCols: 3,
                maxVisibleColsDesktop: 5,
                enableOnDesktop: true
            });
            // Initialize Flatpickr for startDate and endDate inputs
            flatpickr("#startDate", {
                altInput: true,
                altFormat: "F j, Y",
                dateFormat: "Y-m-d",
            });

            flatpickr("#endDate", {
                altInput: true,
                altFormat: "F j, Y",
                dateFormat: "Y-m-d",
            });
            // When the report-order button is clicked, populate the hidden input with the order ID
            $(document).on('click', '.report-order', function(e) {
                e.preventDefault();
                let orderId = $(this).data('order-id');
                $('#reportOrderId').val(orderId);
            });

            // Handle form submission with AJAX
            $('#reportForm').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Report submitted successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload(); // Reload the page to reflect the changes
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while submitting the report.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            $(document).on('click', '.solve-problem', function(e) {
                e.preventDefault();

                let reportId = $(this).data('report-id');
                let $row = $(this).closest('tr');
                let $prevRow = $row.prev('tr');
                // Use SweetAlert2 for confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to mark this problem as solved?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, mark it!',
                    cancelButtonText: 'No, cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Make the AJAX request if confirmed
                        $.ajax({
                            url: "{{ route('reports.solve_problem') }}", // Route to handle the status change
                            method: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                report_id: reportId
                            },
                            success: function(response) {
                                if (response.success) {
                                    $row.remove();
                                    $prevRow.remove();
                                    // Use SweetAlert2 for success notification
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Report status successfully updated to solved!',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    });
                                } else {
                                    // Use SweetAlert2 for failure notification
                                    Swal.fire({
                                        title: 'Failed',
                                        text: 'Failed to update report status. Please try again.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function(xhr) {
                                // Use SweetAlert2 for error notification
                                Swal.fire({
                                    title: 'Error',
                                    text: 'An error occurred while processing your request.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });

            // Archive report: set status to archived and remove row
            $(document).on('click', '.archive-report', function(e) {
                e.preventDefault();
                let reportId = $(this).data('report-id');
                let $row = $(this).closest('tr');
                let $prevRow = $row.prev('tr');
                Swal.fire({
                    title: 'Archive report?',
                    text: 'This report will be moved to Archived.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, archive',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('reports.archive') }}",
                            method: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                report_id: reportId
                            },
                            success: function(response) {
                                if (response.success) {
                                    $row.remove();
                                    if ($prevRow && $prevRow.hasClass('mobile-detail-row')) {
                                        $prevRow.remove();
                                    }
                                    Swal.fire({
                                        title: 'Archived',
                                        text: 'Report has been archived.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Failed',
                                        text: 'Could not archive report.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'An error occurred.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });

            // Define the search button click handler FIRST
            $('#customSearchBtn').on('click', function() {
                let query = $('#searchOrder').val();
                let startDate = $('#startDate').val();
                let endDate = $('#endDate').val();
                let storeProfileId = $('#storeId').val();
                let status = $('#currentReportStatus').length > 0 ? $('#currentReportStatus').val() : 'all';
                let showAll = $('#showAllCheckbox').is(':checked') ? 1 : 0;

                $.ajax({
                    url: "{{ route('manager.orders.search') }}",
                    method: 'GET',
                    data: {
                        search: query,
                        start_date: startDate,
                        end_date: endDate,
                        status: status,
                        store_profile_id: storeProfileId,
                        show_all: showAll,
                    },
                    success: function(response) {
                        if (!response.rows || response.rows.trim() === '') {
                            Swal.fire({
                                title: 'No Results',
                                text: 'No orders found matching your search criteria.',
                                icon: 'info',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            $('#orderTableBody').html(response.rows);
                            $('#orderPagination').html(response.pagination);
                            $('.orders-responsive-table').mobileTableToggle({
                                maxVisibleCols: 3,
                                maxVisibleColsDesktop: 5,
                                enableOnDesktop: true
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while processing your request.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            $(document).on('click', '#search-pagination .pagination a', function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                let queryParams = new URLSearchParams(url.split('?')[1]);
                let search     = $('#searchOrder').val();
                let startDate  = $('#startDate').val();
                let endDate    = $('#endDate').val();
                let storeId    = $('#storeId').val();
                let status     = $('#currentReportStatus').length > 0 ? $('#currentReportStatus').val() : 'all';
                let showAll    = $('#showAllCheckbox').is(':checked') ? 1 : 0;
                // Add any manually selected filters
                queryParams.set('search', search);
                queryParams.set('start_date', startDate);
                queryParams.set('end_date', endDate);
                queryParams.set('store_profile_id', storeId);
                queryParams.set('status', status);
                queryParams.set('show_all', showAll);

                $.ajax({
                    url: url.split('?')[0] + '?' + queryParams.toString(),
                    method: 'GET',
                    success: function(response) {
                        $('#orderTableBody').html(response.rows);
                        $('#orderPagination').html(response.pagination);
                        $('.orders-responsive-table').mobileTableToggle({
                            maxVisibleCols: 3,
                            maxVisibleColsDesktop: 5,
                            enableOnDesktop: true
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'Could not load page', 'error');
                    }
                });
            });

            // NOW check for URL parameters and trigger search AFTER the handler is defined
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            const startDateParam = urlParams.get('start_date');
            const endDateParam = urlParams.get('end_date');
            
            if (searchParam) {
                $('#searchOrder').val(searchParam);
                
                // Set date range if provided in URL
                if (startDateParam) {
                    $('#startDate').val(startDateParam);
                }
                if (endDateParam) {
                    $('#endDate').val(endDateParam);
                }
                
                // Trigger the search automatically
                $('#customSearchBtn').trigger('click');
            }

        });
    </script>
@endpush

@push('js')
    <script>
        jQuery(document).ready(
            function($) {
                $(document).on('click', '.undo-order', function(e) {
                    e.preventDefault();

                    let orderId = $(this).data('order-id');
                    let soldItem = $(this).data('sold-item');
                    let reportId = $(this).data('report-id'); // Get the report_id if available

                    // Use SweetAlert2 for confirmation dialog
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you really want to undo this order?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, undo it!',
                        cancelButtonText: 'No, keep it'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Make the AJAX request if confirmed
                            $.ajax({
                                url: "{{ route('manager.orders.undo') }}", // Route to handle the undo action
                                method: 'POST',
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    order_id: orderId,
                                    sold_item: soldItem,
                                    report_id: reportId // Pass the report_id only if available
                                },
                                success: function(response) {
                                    if (response.success) {
                                        // Success alert with SweetAlert2
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Order successfully undone and stock updated!',
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            $('#orderRow-' + orderId).next('.mobile-detail-row').remove();
                                            $('#orderRow-' + orderId).remove(); // Remove the row from the table
                                            
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Failed',
                                            text: 'Failed to undo order. Please try again.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'An error occurred while processing your request.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            });
                        }
                    });
                });
            }
        );
    </script>
    <script>
        // JavaScript to handle "Check All" functionality
        // Only select checkboxes that are in the same form context
        document.getElementById('select_all').addEventListener('change', function() {
            // Get the form that contains the select_all checkbox
            const form = this.closest('form');
            if (form) {
                // Only select checkboxes within this form
                var checkboxes = form.querySelectorAll('input[name="order_ids[]"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = document.getElementById('select_all').checked;
                });
            }
            
            // Also handle unsend checkboxes separately (they're in the same table but different form)
            var unsendCheckboxes = document.querySelectorAll('input[name="unsend_order_ids[]"]');
            unsendCheckboxes.forEach(function(checkbox) {
                checkbox.checked = document.getElementById('select_all').checked;
            });
        });

        // Handle Send to POS form submission - validate that order_ids are selected
        jQuery(document).ready(function($) {
            $('#sendToPosForm').on('submit', function(e) {
                const checkedOrderIds = $('input[name="order_ids[]"]:checked');
                
                console.log('Send to POS form submitted, checked order_ids:', checkedOrderIds.length);
                
                if (checkedOrderIds.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'No Orders Selected',
                        text: 'Please select at least one order that has not been sent to POS. Orders that have already been sent to POS cannot be sent again.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    return false;
                }
                
                // Log the order IDs being sent
                const orderIds = checkedOrderIds.map(function() {
                    return $(this).val();
                }).get();
                console.log('Sending order IDs to POS:', orderIds);
            });
        });

        // Handle unsend checkboxes - show/hide unsend form
        jQuery(document).ready(function($) {
            function updateUnsendForm() {
                const checkedUnsendBoxes = $('input[name="unsend_order_ids[]"]:checked');
                const unsendForm = $('#unsendPosForm');
                
                if (checkedUnsendBoxes.length > 0) {
                    // Populate hidden inputs with checked order IDs
                    const unsendOrderIdsDiv = $('#unsendOrderIds');
                    unsendOrderIdsDiv.empty();
                    checkedUnsendBoxes.each(function() {
                        unsendOrderIdsDiv.append('<input type="hidden" name="order_ids[]" value="' + $(this).val() + '" />');
                    });
                    unsendForm.show();
                } else {
                    unsendForm.hide();
                }
            }

            // Listen for changes on unsend checkboxes
            $(document).on('change', 'input[name="unsend_order_ids[]"]', function() {
                updateUnsendForm();
            });

            // Handle unsend form submission with confirmation
            $('#unsendPosForm').on('submit', function(e) {
                e.preventDefault();
                
                const checkedCount = $('input[name="unsend_order_ids[]"]:checked').length;
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to unsend ${checkedCount} order(s) from POS? This will set their pos_order_id to null.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, unsend them!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form
                        this.submit();
                    }
                });
            });

            // Initialize form visibility
            updateUnsendForm();
        });
    </script>
@endpush
