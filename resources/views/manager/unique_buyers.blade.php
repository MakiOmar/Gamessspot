@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">
            Customers
        </h1>
        <div>
            <form action="{{ route('manager.customers.export') }}" method="GET">
                <div class="d-flex ">
                    <!-- Add Search and Export Button -->
                    <div class="w-50 me-1">
                        <!-- Search Box -->
                        <input type="text" class="form-control" name="searchOrder" id="searchOrder" placeholder="Search...">
                    </div>
                </div>
                @if(Auth::user()->roles->contains('name', 'admin'))
                    <div class="d-flex justify-content-end align-items-center">
                        <!-- Export Button -->
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
                        <th>Phone</th>
                        <th>Name</th>
                    </tr>
                </thead>
                <tbody id="orderTableBody">
                    @foreach ($uniqueBuyers as $order)
                        <tr id="orderRow-{{ $order->id }}">
                            <td>{{ $order->buyer_phone }}</td>
                            <td>{{ $order->buyer_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination links (if needed) -->
        <div class="d-flex justify-content-center mt-4">
            @if(request()->has('id'))
                {{ $uniqueBuyers->appends(['id' => request()->get('id')])->links('vendor.pagination.bootstrap-5') }}
            @else
                {{ $uniqueBuyers->links('vendor.pagination.bootstrap-5') }}
            @endif
        </div>
    </div>
@endsection

@push('js')
    <script>
        jQuery(document).ready(function($) {
            // Handle search input and date range filter
            $('#searchOrder').on('input change', function() {
                let query = $('#searchOrder').val();
                if (query.length >= 3) {
                    $.ajax({
                        url: "{{ route('manager.orders.searchCustomers') }}",
                        method: 'GET',
                        data: { search: query },
                        success: function(response) {
                            if (response.trim() === '') {
                                Swal.fire({
                                    title: 'No Results',
                                    text: 'No customers found matching your search criteria.',
                                    icon: 'info',
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                $('#orderTableBody').html(response);
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
                } else if (!query) {
                    location.reload();
                }
            });

        });
    </script>
@endpush

