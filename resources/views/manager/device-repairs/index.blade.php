@extends('layouts.admin')

@section('title', 'Device Services Management')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('css')
<style>
    .pagination {
        margin-bottom: 0;
    }
    .pagination .page-link {
        color: #007bff;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
    }
    .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
    .pagination .page-link:hover {
        color: #0056b3;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }
</style>
@endpush

@section('content_body')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools"></i>
                        Device Repairs Management
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('device-repairs.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add New Repair
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Status Filter Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>{{ $statusCounts['received'] }}</h3>
                                    <p>Received</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <a href="{{ request()->fullUrlWithQuery(['status' => 'received']) }}" class="small-box-footer">
                                    More info <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $statusCounts['processing'] }}</h3>
                                    <p>Processing</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <a href="{{ request()->fullUrlWithQuery(['status' => 'processing']) }}" class="small-box-footer">
                                    More info <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $statusCounts['ready'] }}</h3>
                                    <p>Ready</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-hand-holding"></i>
                                </div>
                                <a href="{{ request()->fullUrlWithQuery(['status' => 'ready']) }}" class="small-box-footer">
                                    More info <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $statusCounts['delivered'] }}</h3>
                                    <p>Delivered</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <a href="{{ request()->fullUrlWithQuery(['status' => 'delivered']) }}" class="small-box-footer">
                                    More info <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search and Filter Form -->
                    <form method="GET" action="{{ route('device-repairs.index') }}" class="mb-4">
                        <div class="row">
                            <div class="{{ Auth::user()->hasRole('admin') ? 'col-md-4' : 'col-md-5' }}">
                                <div class="form-group">
                                    <label for="search">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="{{ request('search') }}" placeholder="Search by name, phone, model, serial, or tracking code">
                                </div>
                            </div>
                            <div class="{{ Auth::user()->hasRole('admin') ? 'col-md-3' : 'col-md-4' }}">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Ready</option>
                                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    </select>
                                </div>
                            </div>
                            @if(Auth::user()->hasRole('admin'))
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="store_profile_id">Store Profile</label>
                                    <select class="form-control" id="store_profile_id" name="store_profile_id">
                                        <option value="">All Stores</option>
                                        @foreach($storeProfiles as $profile)
                                            <option value="{{ $profile->id }}" {{ request('store_profile_id') == $profile->id ? 'selected' : '' }}>
                                                {{ $profile->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                            <div class="{{ Auth::user()->hasRole('admin') ? 'col-md-2' : 'col-md-3' }}">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                            Search
                                        </button>
                                        <a href="{{ route('device-repairs.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i>
                                            Clear
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Device Repairs Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Created By</th>
                                    <th>Tracking Code</th>
                                    <th>Phone</th>
                                    <th>Client Name</th>
                                    <th>Store Profile</th>
                                    <th>Device Model</th>
                                    <th>Serial Number</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deviceRepairs as $repair)
                                    <tr data-repair-id="{{ $repair->id }}">
                                        <td>{{ $repair->submittedBy ? $repair->submittedBy->name : 'N/A' }}</td>
                                        <td>
                                            <code>{{ $repair->tracking_code }}</code>
                                        </td>
                                        <td>{{ $repair->full_phone_number }}</td>
                                        <td>{{ $repair->client_name }}</td>
                                        <td>{{ $repair->storeProfile ? $repair->storeProfile->name : 'N/A' }}</td>
                                        <td>{{ $repair->deviceModel ? $repair->deviceModel->full_name : 'N/A' }}</td>
                                        <td>
                                            <code>{{ $repair->device_serial_number }}</code>
                                        </td>
                                        <td>
                                            <span class="status-badge badge badge-{{ $repair->status == 'received' ? 'primary' : ($repair->status == 'processing' ? 'warning' : ($repair->status == 'ready' ? 'info' : 'success')) }}">
                                                {{ $repair->status_display }}
                                            </span>
                                        </td>
                                        <td>{{ $repair->submitted_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('device-repairs.show', $repair) }}" class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('device-repairs.edit', $repair) }}" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <!-- Status Update Dropdown -->
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" 
                                                            data-toggle="dropdown" 
                                                            aria-haspopup="true" 
                                                            aria-expanded="false">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        @if($repair->status != 'received')
                                                            <a class="dropdown-item status-update" href="#" data-repair-id="{{ $repair->id }}" data-status="received">
                                                                <i class="fas fa-inbox text-primary"></i>
                                                                Set as Received
                                                            </a>
                                                        @endif
                                                        @if($repair->status != 'processing')
                                                            <a class="dropdown-item status-update" href="#" data-repair-id="{{ $repair->id }}" data-status="processing">
                                                                <i class="fas fa-cogs text-warning"></i>
                                                                Set as Processing
                                                            </a>
                                                        @endif
                                                        @if($repair->status != 'ready')
                                                            <a class="dropdown-item status-update" href="#" data-repair-id="{{ $repair->id }}" data-status="ready">
                                                                <i class="fas fa-hand-holding text-info"></i>
                                                                Set as Ready
                                                            </a>
                                                        @endif
                                                        @if($repair->status != 'delivered')
                                                            <a class="dropdown-item status-update" href="#" data-repair-id="{{ $repair->id }}" data-status="delivered">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                                Set as Delivered
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                @if(Auth::user()->hasRole('admin'))
                                                <form action="{{ route('device-repairs.destroy', $repair) }}" method="POST" 
                                                      style="display: inline-block;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this repair record?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No device repairs found</h5>
                                                <p class="text-muted">Try adjusting your search criteria or add a new repair record.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($deviceRepairs->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Device repairs pagination">
                                {{ $deviceRepairs->links('vendor.pagination.bootstrap-5') }}
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusForm">
                    @csrf
                    <div class="form-group">
                        <label for="new_status">New Status</label>
                        <select class="form-control" id="new_status" name="status" required>
                            <option value="received">Received</option>
                            <option value="processing">Processing</option>
                            <option value="ready">Ready</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmStatusUpdate()">Update Status</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
jQuery(document).ready(function($) {
    // Helper functions using jQuery
    function updateStatusBadge(repairId, status, newStatusText) {
        // Find the row and update the status badge
        var $row = $('tr[data-repair-id="' + repairId + '"]');
        if ($row.length) {
            var $statusCell = $row.find('.status-badge');
            if ($statusCell.length) {
                // Update badge class and text
                $statusCell.removeClass().addClass('status-badge badge badge-' + getStatusClass(status));
                $statusCell.text(getStatusText(status));
            }
        }
    }

    function getStatusClass(status) {
        switch(status) {
            case 'received': return 'primary';
            case 'processing': return 'warning';
            case 'ready': return 'info';
            case 'delivered': return 'success';
            default: return 'secondary';
        }
    }

    function getStatusText(status) {
        switch(status) {
            case 'received': return 'Received';
            case 'processing': return 'Processing';
            case 'ready': return 'Ready for Pickup';
            case 'delivered': return 'Delivered';
            default: return 'Unknown';
        }
    }

    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="close" data-dismiss="alert">' +
            '<span>&times;</span>' +
            '</button>' +
            '</div>';
        
        // Insert alert at the top of the card body
        var $cardBody = $('.card-body');
        if ($cardBody.length) {
            $cardBody.prepend(alertHtml);
            
            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $cardBody.find('.alert').first().remove();
            }, 3000);
        }
    }

    // Make function global to ensure it's accessible
    window.updateStatus = function(repairId, status) {
        console.log('updateStatus called with:', repairId, status);
        
        // Get CSRF token
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        if (!csrfToken) {
            csrfToken = $('input[name="_token"]').val();
        }
        
        if (!csrfToken) {
            console.error('CSRF token not found');
            showAlert('error', 'Security token not found. Please refresh the page.');
            return;
        }
        
        // Show loading state
        showAlert('info', 'Updating status...');
        
        // Create form data for the request
        var formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'PATCH');
        formData.append('status', status);
        
        // Use jQuery AJAX instead of fetch
        $.ajax({
            url: '/manager/device-repairs/' + repairId + '/status',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                console.log('AJAX success:', data);
                if (data.success) {
                    showAlert('success', data.message);
                    // Update the status badge in the table without full page reload
                    updateStatusBadge(repairId, status, data.new_status);
                } else {
                    showAlert('error', data.message || 'Failed to update status');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                showAlert('error', 'An error occurred while updating status: ' + error);
            }
         });
     };
     
     // Test function definition
     console.log('updateStatus function defined:', typeof window.updateStatus);
     
     // Handle status update clicks using event delegation
     $(document).on('click', '.status-update', function(e) {
        console.log('Status update clicked:', e);
         e.preventDefault();
         e.stopPropagation();
         
         var repairId = $(this).data('repair-id');
         var status = $(this).data('status');
         
         console.log('Status update clicked:', repairId, status);
         
         // Close the dropdown
         $(this).closest('.dropdown-menu').removeClass('show');
         $(this).closest('.btn-group').find('.dropdown-toggle').attr('aria-expanded', 'false');
         
         // Call the updateStatus function
         if (typeof window.updateStatus === 'function') {
             window.updateStatus(repairId, status);
         } else {
             console.error('updateStatus function not found');
         }
     });
});

    </script>
@endpush
