@extends('layouts.admin')

@section('title', 'Manager - Orders')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Orders Management</h1>
    <!-- Add Search and Export Button -->
    <div class="d-flex justify-content-between mb-4 align-items-end">
        <div class="w-50">
            <div class="alert alert-warning" id="noResultsMessage" style="display: none;">
                No results found.
            </div>
            <!-- Search Box -->
            <input type="text" class="form-control" id="searchOrder" placeholder="Search orders by buyer phone">
        </div>
        <div class="d-flex justify-content-end align-items-center">
            <!-- Export Button -->
            <a href="{{ route('manager.orders.export') }}" class="d-flex -4 float-right">
                <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 48 48" width="36px" height="36px"><path fill="#4CAF50" d="M41,10H25v28h16c0.553,0,1-0.447,1-1V11C42,10.447,41.553,10,41,10z"/><path fill="#FFF" d="M32 15H39V18H32zM32 25H39V28H32zM32 30H39V33H32zM32 20H39V23H32zM25 15H30V18H25zM25 25H30V28H25zM25 30H30V33H25zM25 20H30V23H25z"/><path fill="#2E7D32" d="M27 42L6 38 6 10 27 6z"/><path fill="#FFF" d="M19.129,31l-2.411-4.561c-0.092-0.171-0.186-0.483-0.284-0.938h-0.037c-0.046,0.215-0.154,0.541-0.324,0.979L13.652,31H9.895l4.462-7.001L10.274,17h3.837l2.001,4.196c0.156,0.331,0.296,0.725,0.42,1.179h0.04c0.078-0.271,0.224-0.68,0.439-1.22L19.237,17h3.515l-4.199,6.939l4.316,7.059h-3.74V31z"/></svg>
            </a>
        </div>
    </div>

    <!-- Scrollable table container -->
    <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
        <table class="table table-striped table-bordered" style="min-width: 1200px;">
            <thead>
                <tr role="row">
                    <th>ID</th>
                    <th>Seller</th>
                    <th>Game</th>
                    <th>Account</th>
                    <th>Password</th>
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
                @foreach($orders as $order)
                <tr id="orderRow-{{ $order->id }}">
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->seller->name }}</td>
                    <td>{{ $order->account->game->title }}</td>
                    <td>{{ $order->account->mail }}</td>
                    <td>{{ $order->account->password }}</td>
                    <td>{{ $order->buyer_phone }}</td>
                    <td>{{ $order->buyer_name }}</td>
                    <td>{{ $order->price }}</td>
                    <td>{{ $order->sold_item }}</td>
                    <td>{{ $order->notes }}</td>
                    <td>{{ $order->created_at }}</td>
                    <td>
                        <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}" data-sold-item="{{ $order->sold_item }}">
                            Undo
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination links (if needed) -->
    <div class="d-flex justify-content-center mt-4">
        {{ $orders->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

@endsection

@push('scripts')
<!-- JavaScript for handling AJAX form submission and search -->
<script>
    $(document).ready(function() {
        // Handle search input for orders by phone
        $('#searchOrder').on('input', function() {
            let query = $(this).val();

            // Check if the input has 3 or more characters before running the search
            if (query.length >= 3) {
                $.ajax({
                    url: "{{ route('manager.orders.search') }}", // Add search route for orders
                    method: 'GET',
                    data: { search: query },
                    success: function(response) {
                        if (response.trim() === '') { // Check if response is empty
                            $('#noResultsMessage').show(); // Show 'No results' message
                        } else {
                            $('#noResultsMessage').hide(); // Hide 'No results' message
                            $('#orderTableBody').html(response); // Replace table rows with search results
                        }
                    }
                });
            } else if (query === '') {
                location.reload(); // Reload the page if the search is cleared
            }
        });
    });
</script>
@endpush

@push('scripts')
<script>
    $(document).on('click', '.undo-order', function(e) {
        e.preventDefault();

        let orderId = $(this).data('order-id');
        let soldItem = $(this).data('sold-item');

        if (confirm('Are you sure you want to undo this order?')) {
            $.ajax({
                url: "{{ route('manager.orders.undo') }}", // Route to handle the undo action
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    order_id: orderId,
                    sold_item: soldItem
                },
                success: function(response) {
                    if (response.success) {
                        $('#orderRow-' + orderId).remove(); // Remove the row from the table
                        alert('Order successfully undone and stock updated!');
                    } else {
                        alert('Failed to undo order. Please try again.');
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while processing your request.');
                }
            });
        }
    });
</script>
@endpush

