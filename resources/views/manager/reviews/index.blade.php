@extends('layouts.admin')

@section('title', 'Reviews Management')

@section('content_body')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-star"></i> Reviews Management</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $statusCounts['pending'] }}</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $statusCounts['approved'] }}</h3>
                            <p>Approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ $statusCounts['rejected'] }}</h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $statusCounts['all'] }}</h3>
                            <p>All</p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('manager.reviews.index') }}" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="type">Product Type</label>
                        <select name="type" id="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="game" {{ $type === 'game' ? 'selected' : '' }}>Games</option>
                            <option value="card_category" {{ $type === 'card_category' ? 'selected' : '' }}>Gift Cards</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ $search }}" placeholder="Phone, name, or comment">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Stars</th>
                            <th>Comment</th>
                            <th>Reviewer</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $review)
                            @php
                                $productName = 'N/A';
                                $productType = class_basename($review->reviewable_type);
                                if ($review->reviewable) {
                                    $productName = $review->reviewable->title
                                        ?? $review->reviewable->name
                                        ?? ('#' . $review->reviewable_id);
                                }
                            @endphp
                            <tr data-review-id="{{ $review->id }}">
                                <td>{{ $review->id }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $productType }}</span>
                                    {{ $productName }}
                                </td>
                                <td>{{ $review->stars }} / 5</td>
                                <td>{{ \Illuminate\Support\Str::limit($review->comment, 80) }}</td>
                                <td>{{ $review->user->name ?? 'Customer' }}</td>
                                <td>{{ $review->user->phone ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge review-status-badge
                                        @if($review->status === 'approved') bg-success
                                        @elseif($review->status === 'rejected') bg-danger
                                        @else bg-warning text-dark @endif">
                                        {{ ucfirst($review->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($review->created_at)->format('M d, Y H:i') }}</td>
                                <td class="text-nowrap">
                                    @if($review->status !== 'approved')
                                        <button type="button" class="btn btn-success btn-sm review-approve" data-id="{{ $review->id }}">Approve</button>
                                    @endif
                                    @if($review->status !== 'rejected')
                                        <button type="button" class="btn btn-warning btn-sm review-reject" data-id="{{ $review->id }}">Reject</button>
                                    @endif
                                    <button type="button" class="btn btn-danger btn-sm review-delete" data-id="{{ $review->id }}">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No reviews found for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($reviews->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $reviews->links('vendor.pagination.bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
jQuery(document).ready(function ($) {
    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function updateBadge($row, status) {
        var $badge = $row.find('.review-status-badge');
        $badge.removeClass('bg-success bg-danger bg-warning text-dark');
        if (status === 'approved') {
            $badge.addClass('bg-success').text('Approved');
        } else if (status === 'rejected') {
            $badge.addClass('bg-danger').text('Rejected');
        } else {
            $badge.addClass('bg-warning text-dark').text('Pending');
        }
    }

    $(document).on('click', '.review-approve', function () {
        var id = $(this).data('id');
        var $row = $('tr[data-review-id="' + id + '"]');
        $.ajax({
            url: '/manager/reviews/' + id + '/approve',
            method: 'POST',
            data: { _token: csrf() },
            success: function (res) {
                Swal.fire({ icon: 'success', title: 'Approved', text: res.message, timer: 1200, showConfirmButton: false });
                updateBadge($row, 'approved');
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to approve review.' });
            }
        });
    });

    $(document).on('click', '.review-reject', function () {
        var id = $(this).data('id');
        var $row = $('tr[data-review-id="' + id + '"]');
        $.ajax({
            url: '/manager/reviews/' + id + '/reject',
            method: 'POST',
            data: { _token: csrf() },
            success: function (res) {
                Swal.fire({ icon: 'success', title: 'Rejected', text: res.message, timer: 1200, showConfirmButton: false });
                updateBadge($row, 'rejected');
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to reject review.' });
            }
        });
    });

    $(document).on('click', '.review-delete', function () {
        var id = $(this).data('id');
        var $row = $('tr[data-review-id="' + id + '"]');
        Swal.fire({
            title: 'Delete review?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: '/manager/reviews/' + id,
                method: 'POST',
                data: { _token: csrf(), _method: 'DELETE' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 1200, showConfirmButton: false });
                    $row.remove();
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete review.' });
                }
            });
        });
    });
});
</script>
@endpush
