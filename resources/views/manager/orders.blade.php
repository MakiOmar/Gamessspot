@extends('layouts.admin')

@section('title', 'Manager - Orders')

@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">
            @if (! isset($status))
            Orders Management
            @else
            Reports Management
            @endif
        </h1>
        <div>
            <form action="{{ route('manager.orders.export') }}" method="GET">
                <div class="d-flex ">
                    <!-- Add Search and Export Button -->
                    <div class="w-50 me-1">
                        <!-- Search Box -->
                        <input type="text" class="form-control" name="searchOrder" id="searchOrder" placeholder="Search orders by buyer phone">
                        <input type="hidden" id="storeId" value="@if( ! empty( $_GET['id'] ) ){{ $_GET['id'] }}@else{{0}}@endif">
                    </div>
                    @if( ! Auth::user()->roles->contains('name', 'sales') )
                    <div class="w-50 d-flex align-items-center mb-2">
                        <!-- Start Date -->
                        <input type="date" class="form-control me-2" id="startDate" name="startDate" placeholder="Start Date">
                        <!-- End Date -->
                        <input type="date" class="form-control" id="endDate" name="endDate" placeholder="End Date">
                    </div>
                    @endif
                </div>
                @if(Auth::user()->roles->contains('name', 'admin') || Auth::user()->roles->contains('name', 'accountant'))
                    <div class="d-flex justify-content-end align-items-center">
                        <!-- Export Button -->
                        
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
                @endif
            </form>
        </div>

        <!-- Scrollable table container -->
        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            <table class="table table-striped table-bordered" style="min-width: 1200px;">
                <thead>
                    <tr role="row">
                        <th>ID</th>
                        <th>Seller</th>
                        <th>Product</th>
                        <th>Account</th>
                        @if(! Auth::user()->roles->contains('name', 'accountant') )
                        <th>Password</th>
                        @endif
                        @if (! isset($status))
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
                            <td class="{{ $order->seller ? '' : 'text-danger' }}">{{ $order->seller?->name ?? 'Maybe deleted' }}</td>
                            @if($order->account)
                                <td>{{ $order->account->game->title }}</td>
                                <td>{{ $order->account->mail }}</td>
                                @if(! Auth::user()->roles->contains('name', 'accountant') )
                                <td>{{ $order->account->password }}</td>
                                @endif
                            @elseif($order->card)
                                <td>{{ $order->card->category->name }}</td>
                                @if(! Auth::user()->roles->contains('name', 'accountant') )
                                <td>{{ $order->card->code }}</td>
                                @endif
                                <td>--</td>
                            @endif
                            @if (! isset($status))
                            <td>{{ $order->buyer_phone }}</td>
                            <td>{{ $order->buyer_name }}</td>
                            @endif
                            <td>{{ $order->price }}</td>
                            <td>{{ $order->sold_item }}</td>
                            <td>
                                @if (! isset($status))
                                    {{ $order->notes }}
                                @else
                                {{ optional($order->reports)->note }}
                                @endif
                            </td>
                            <td>{{ $order->created_at }}</td>
                            <td>
                                <!-- Check if 'needs_return' flag is true -->
                                @if (isset($status) && 'needs_return' === $status)
                                    <!-- Button for orders with 'needs_return' -->
                                    <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}"
                                        data-sold-item="{{ $order->sold_item }}"
                                        data-report-id="{{ $order->reports->id }}">
                                        Undo
                                    </button>
                                @elseif(isset($status) && 'has_problem' === $status)
                                    <button class="btn btn-success btn-sm solve-problem"
                                        data-report-id="{{ $order->reports->id }}">
                                        Mark as Solved
                                    </button>
                                @elseif(isset($status) && 'solved' === $status)
                                    @if(Auth::user()->roles->contains('name', 'admin'))
                                            <!-- Regular undo button -->
                                            <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}" data-sold-item="{{ $order->sold_item }}">
                                                Undo
                                            </button>
                                    @endif
                                @else
                                    @if(! Auth::user()->roles->contains('name', 'accountant') )
                                        @if ( Auth::user()->roles->contains('name', 'admin') )
                                        <!-- Regular undo button -->
                                        <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}" data-sold-item="{{ $order->sold_item }}">
                                            Undo
                                        </button>
                                        @endif
                                        <!-- Button to open report modal for sales -->
                                        <button class="btn btn-warning btn-sm report-order" data-order-id="{{ $order->id }}" data-toggle="modal" data-target="#reportOrderModal">
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
        @if (isset($status))
        <input type="hidden" id="currentReportStatus" value="{{$status}}"/>
        @endif
        <!-- Pagination links (if needed) -->
        <div class="d-flex justify-content-center mt-4">
            @if(request()->has('id'))
                {{ $orders->appends(['id' => request()->get('id')])->links('vendor.pagination.bootstrap-5') }}
            @else
                {{ $orders->links('vendor.pagination.bootstrap-5') }}
            @endif
        </div>
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
                                    // Use SweetAlert2 for success notification
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Report status successfully updated to solved!',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        location
                                    .reload(); // Reload the page to reflect the changes
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
