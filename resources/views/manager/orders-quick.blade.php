@extends('layouts.admin')

@section('title', 'Manager - Orders')

@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">Search Management</h1>
        @if ($orders && !empty($orders))
            @if ($buyer)
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <div class="card-title">
                            <h3 class="card-label"><span class="card-icon">
                                    <i class="fa-solid fa-user"></i>
                                </span>User Info</h3>
                        </div>
                        <div class="card-toolbar"></div>
                    </div>
                    <div class="card-body">
                        <!--begin: Datatable-->
                        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
                            <table class="table table-striped table-bordered" style="min-width: 1200px;">
                                <thead>
                                    <tr role="row">
                                        <th>Buyer Phone</th>
                                        <th>Buyer Name</th>

                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="buyerTableBody">
                                    <tr>
                                        <td>{{ $buyer->buyer_phone }}</td>
                                        <td>{{ $buyer->buyer_name }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!--end: Datatable-->
                    </div>

                </div>
            @endif
            <div class="card mb-4">
                <div class="card-header py-3">
                    <div class="card-title">
                        <h3 class="card-label"><span class="card-icon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            </span>Search results</h3>
                    </div>
                    <div class="card-toolbar"></div>
                </div>
                <div class="card-body">
                    <!--begin: Datatable-->
                    <!-- Scrollable table container -->
                    <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
                        <table class="table table-striped table-bordered" style="min-width: 1200px;">
                            <thead>
                                <tr role="row">
                                    <th>ID</th>
                                    <th>Seller</th>
                                    <th>Product</th>
                                    <th>Account</th>
                                    @if (!Auth::user()->roles->contains('name', 'accountant'))
                                        <th>Password</th>
                                    @endif
                                    @if (!isset($status))
                                        <th>Buyer Phone</th>
                                        <th>Buyer Name</th>
                                    @endif
                                    <th>Price</th>
                                    <th>Sold Item</th>
                                    <th>Notes</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="orderTableBody">
                                @foreach ($orders as $order)
                                    <tr id="orderRow-{{ $order->id }}">
                                        <td>{{ $order->id }}</td>
                                        <td>{{ optional($order->seller)->name ?? '—' }}</td>
                                        @if ($order->account)
                                            <td>{{ $order->account->game->title }}</td>
                                            <td>{{ $order->account->mail }}</td>
                                            @if (!Auth::user()->roles->contains('name', 'accountant'))
                                                <td>{{ $order->account->password }}</td>
                                            @endif
                                        @elseif($order->card)
                                            <td>{{ $order->card->category->name }}</td>
                                            @if (!Auth::user()->roles->contains('name', 'accountant'))
                                                <td>{{ $order->card->code }}</td>
                                            @endif
                                            <td>--</td>
                                        @endif
                                        @if (!isset($status))
                                            <td>{{ $order->buyer_phone }}</td>
                                            <td>{{ $order->buyer_name }}</td>
                                        @endif
                                        <td>{{ $order->price }}</td>
                                        <td>{{ $order->sold_item }}</td>
                                        <td>
                                            @if (!isset($status))
                                                {{ $order->notes }}
                                            @else
                                                {{ $order->reports->first()->note }}
                                            @endif
                                        </td>
                                        <td>{{ $order->created_at }}</td>
                                        <td>
                                            <!-- Check if 'needs_return' flag is true -->
                                            @if (isset($status) && 'needs_return' === $status)
                                                <!-- Button for orders with 'needs_return' -->
                                                <button class="btn btn-danger btn-sm undo-order"
                                                    data-order-id="{{ $order->id }}"
                                                    data-sold-item="{{ $order->sold_item }}"
                                                    data-report-id="{{ $order->reports->first()->id }}">
                                                    Undo
                                                </button>
                                            @elseif(isset($status) && 'has_problem' === $status)
                                                <button class="btn btn-success btn-sm solve-problem"
                                                    data-report-id="{{ $order->reports->first()->id }}">
                                                    Mark as Solved
                                                </button>
                                                <button class="btn btn-secondary btn-sm archive-report"
                                                    data-report-id="{{ $order->reports->first()->id }}">
                                                    Archive
                                                </button>
                                            @elseif(isset($status) && 'solved' === $status)
                                                @if (Auth::user()->roles->contains('name', 'admin'))
                                                    <!-- Regular undo button -->
                                                    <button class="btn btn-danger btn-sm undo-order"
                                                        data-order-id="{{ $order->id }}"
                                                        data-sold-item="{{ $order->sold_item }}">
                                                        Undo
                                                    </button>
                                                @endif
                                                <button class="btn btn-secondary btn-sm archive-report"
                                                    data-report-id="{{ $order->reports->first()->id }}">
                                                    Archive
                                                </button>
                                            @elseif(isset($status) && 'archived' === $status)
                                                <span class="badge bg-secondary">Archived</span>
                                            @else
                                                @if (!Auth::user()->roles->contains('name', 'accountant'))
                                                    @if (Auth::user()->roles->contains('name', 'admin'))
                                                        <!-- Regular undo button -->
                                                        <button class="btn btn-danger btn-sm undo-order"
                                                            data-order-id="{{ $order->id }}"
                                                            data-sold-item="{{ $order->sold_item }}">
                                                            Undo
                                                        </button>
                                                    @endif
                                                    <!-- Button to open report modal for sales -->
                                                    <button class="btn btn-warning btn-sm report-order"
                                                        data-order-id="{{ $order->id }}" data-toggle="modal"
                                                        data-target="#reportOrderModal">
                                                        Actions
                                                    </button>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!--end: Datatable-->
                </div>

            </div>

            <!-- Pagination links (if needed) -->
            <div class="d-flex justify-content-center mt-4">
                @if (request()->has('search'))
                    {{ $orders->appends(['search' => request()->get('search')])->links('vendor.pagination.bootstrap-5') }}
                @else
                    {{ $orders->links('vendor.pagination.bootstrap-5') }}
                @endif
            </div>
        @endif
    </div>
    @if ($orders && !empty($orders))
        <!-- Report Order Modal -->
        <div class="modal fade" id="reportOrderModal" tabindex="-1" aria-labelledby="reportOrderModalLabel"
            aria-hidden="true">
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
    @endif

@endsection
@if ($orders && !empty($orders))
    @push('js')
        <script>
            jQuery(document).ready(function($) {
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
                $(document).on('click', '.report-order', function() {
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
                                url: "{{ route('reports.solve_problem') }}",
                                method: 'POST',
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    report_id: reportId
                                },
                                success: function(response) {
                                    console.log($row);
                                    if (response.success) {
                                        $row.remove();
                                        $prevRow.remove();
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Report status successfully updated to solved!',
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Failed',
                                            text: 'Failed to update report status. Please try again.',
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

                // Archive report: set status to archived and remove row
                $(document).on('click', '.archive-report', function(e) {
                    e.preventDefault();
                    let reportId = $(this).data('report-id');
                    let $row = $(this).closest('tr');
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

                // Handle search input and date range filter
                $('#searchOrder, #startDate, #endDate').on('input change', function() {
                    let query = $('#searchOrder').val();
                    let startDate = $('#startDate').val();
                    let endDate = $('#endDate').val();
                    let storeProfileId = $('#storeId').val();
                    let status = $('#currentReportStatus').length > 0 ? $('#currentReportStatus').val() : 'all';

                    // Check if we have a valid date range or search query
                    if ((startDate && endDate) || query.length >= 3) {
                        $.ajax({
                            url: "{{ route('manager.orders.search') }}", // Search route for orders
                            method: 'GET',
                            data: {
                                search: query,
                                start_date: startDate,
                                end_date: endDate,
                                status: status,
                                store_profile_id: storeProfileId,
                            },
                            success: function(response) {
                                if (response.trim() === '') {
                                    Swal.fire({
                                        title: 'No Results',
                                        text: 'No orders found matching your search criteria.',
                                        icon: 'info',
                                        confirmButtonText: 'OK'
                                    }); // Show 'No results' message
                                } else {
                                    $('#orderTableBody').html(
                                        response); // Replace table rows with search results
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
                    } else if (!query && !startDate && !endDate) {
                        location.reload(); // Reload the page if both search and date range are cleared
                    }
                });

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
                                                $('#orderRow-' + orderId)
                                                    .remove(); // Remove the row from the table
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
    @endpush
@endif
