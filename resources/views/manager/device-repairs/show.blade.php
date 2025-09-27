@extends('layouts.admin')

@section('title', 'Device Repair Details')

@section('content_body')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools"></i>
                        Device Repair Details
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('device-repairs.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to List
                        </a>
                        <a href="{{ route('device-repairs.edit', $deviceRepair) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i>
                            Edit
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-info-circle"></i>
                                        Basic Information
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Tracking Code:</strong></td>
                                            <td>
                                                <code class="text-primary">{{ $deviceRepair->tracking_code }}</code>
                                                <button class="btn btn-sm btn-outline-secondary ml-2" onclick="copyToClipboard('{{ $deviceRepair->tracking_code }}')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Client Name:</strong></td>
                                            <td>{{ $deviceRepair->client_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone Number:</strong></td>
                                            <td>{{ $deviceRepair->full_phone_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Device Model:</strong></td>
                                            <td>{{ $deviceRepair->device_model }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Serial Number:</strong></td>
                                            <td>
                                                <code>{{ $deviceRepair->device_serial_number }}</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge badge-{{ $deviceRepair->status == 'received' ? 'primary' : ($deviceRepair->status == 'processing' ? 'warning' : ($deviceRepair->status == 'ready' ? 'info' : 'success')) }}">
                                                    <i class="fas fa-{{ $deviceRepair->status == 'delivered' ? 'check-circle' : ($deviceRepair->status == 'ready' ? 'hand-holding' : ($deviceRepair->status == 'processing' ? 'cogs' : 'clock')) }}"></i>
                                                    {{ $deviceRepair->status_display }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Submitted:</strong></td>
                                            <td>{{ $deviceRepair->submitted_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td>{{ $deviceRepair->status_updated_at ? $deviceRepair->status_updated_at->format('M d, Y H:i') : 'Never' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Management -->
                        <div class="col-md-6">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-cogs"></i>
                                        Status Management
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form id="statusForm">
                                        @csrf
                                        <div class="form-group">
                                            <label for="status">Current Status</label>
                                            <select class="form-control" id="status" name="status" onchange="updateStatus({{ $deviceRepair->id }}, this.value)">
                                                <option value="received" {{ $deviceRepair->status == 'received' ? 'selected' : '' }}>Received</option>
                                                <option value="processing" {{ $deviceRepair->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                                <option value="ready" {{ $deviceRepair->status == 'ready' ? 'selected' : '' }}>Ready</option>
                                                <option value="delivered" {{ $deviceRepair->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                            </select>
                                        </div>
                                    </form>
                                    
                                    <!-- Status Progress -->
                                    <div class="mt-4">
                                        <h5>Repair Progress</h5>
                                        <div class="progress" style="height: 25px;">
                                            @php
                                                $progress = match($deviceRepair->status) {
                                                    'received' => 25,
                                                    'processing' => 50,
                                                    'ready' => 75,
                                                    'delivered' => 100,
                                                    default => 0
                                                };
                                            @endphp
                                            <div class="progress-bar bg-{{ $deviceRepair->status == 'delivered' ? 'success' : 'info' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $progress }}%" 
                                                 aria-valuenow="{{ $progress }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $progress }}%
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Timeline -->
                                    <div class="mt-4">
                                        <h5>Timeline</h5>
                                        <div class="timeline">
                                            <div class="time-label">
                                                <span class="bg-primary">{{ $deviceRepair->submitted_at->format('M d') }}</span>
                                            </div>
                                            <div>
                                                <i class="fas fa-inbox bg-primary"></i>
                                                <div class="timeline-item">
                                                    <span class="time">{{ $deviceRepair->submitted_at->format('H:i') }}</span>
                                                    <h3 class="timeline-header">Device Received</h3>
                                                    <div class="timeline-body">
                                                        Device submitted for repair
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if($deviceRepair->status != 'received')
                                                <div>
                                                    <i class="fas fa-cogs bg-warning"></i>
                                                    <div class="timeline-item">
                                                        <span class="time">{{ $deviceRepair->status_updated_at ? $deviceRepair->status_updated_at->format('H:i') : 'N/A' }}</span>
                                                        <h3 class="timeline-header">Processing Started</h3>
                                                        <div class="timeline-body">
                                                            Device is being processed
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if($deviceRepair->status == 'ready' || $deviceRepair->status == 'delivered')
                                                <div>
                                                    <i class="fas fa-hand-holding bg-info"></i>
                                                    <div class="timeline-item">
                                                        <span class="time">{{ $deviceRepair->status_updated_at ? $deviceRepair->status_updated_at->format('H:i') : 'N/A' }}</span>
                                                        <h3 class="timeline-header">Ready for Pickup</h3>
                                                        <div class="timeline-body">
                                                            Device is ready for customer pickup
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if($deviceRepair->status == 'delivered')
                                                <div>
                                                    <i class="fas fa-check-circle bg-success"></i>
                                                    <div class="timeline-item">
                                                        <span class="time">{{ $deviceRepair->status_updated_at ? $deviceRepair->status_updated_at->format('H:i') : 'N/A' }}</span>
                                                        <h3 class="timeline-header">Delivered</h3>
                                                        <div class="timeline-body">
                                                            Device has been delivered to customer
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes Section -->
                    @if($deviceRepair->notes)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-sticky-note"></i>
                                            Notes & Information
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">{{ $deviceRepair->notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Customer Information -->
                    @if($deviceRepair->user)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-user"></i>
                                            Customer Information
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td><strong>Customer ID:</strong></td>
                                                <td>{{ $deviceRepair->user->id }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Name:</strong></td>
                                                <td>{{ $deviceRepair->user->name }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Email:</strong></td>
                                                <td>{{ $deviceRepair->user->email ?: 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phone:</strong></td>
                                                <td>{{ $deviceRepair->user->full_phone_number ?? $deviceRepair->full_phone_number }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Member Since:</strong></td>
                                                <td>{{ $deviceRepair->user->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Public Tracking Link -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card card-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-external-link-alt"></i>
                                        Public Tracking Information
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Share this link with the customer for real-time tracking:</p>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="trackingLink" 
                                               value="{{ route('device.tracking', ['code' => $deviceRepair->tracking_code]) }}" 
                                               readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(document.getElementById('trackingLink').value)">
                                                <i class="fas fa-copy"></i>
                                                Copy Link
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateStatus(repairId, status) {
    fetch(`/manager/device-repairs/${repairId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('success', data.message);
            
            // Reload the page to update the status
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert('error', 'Failed to update status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while updating status');
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('success', 'Copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
        showAlert('error', 'Failed to copy to clipboard');
    });
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // Insert alert at the top of the card body
    const cardBody = document.querySelector('.card-body');
    cardBody.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        const alert = cardBody.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 3000);
}
</script>
@endpush
