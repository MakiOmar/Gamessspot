@extends('layouts.admin')

@section('title', 'Edit Device Repair')

@section('content_body')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i>
                        Edit Device Repair
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('device-repairs.show', $deviceRepair) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i>
                            View Details
                        </a>
                        <a href="{{ route('device-repairs.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to List
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <form action="{{ route('device-repairs.update', $deviceRepair) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_name">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('client_name') is-invalid @enderror" 
                                           id="client_name" name="client_name" value="{{ old('client_name', $deviceRepair->client_name) }}" required>
                                    @error('client_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="store_profile_id">Store Profile <span class="text-danger">*</span></label>
                                    @if(Auth::user()->hasRole('admin'))
                                        <input type="text" class="form-control" 
                                               value="{{ $deviceRepair->storeProfile ? $deviceRepair->storeProfile->name : 'N/A' }}" 
                                               readonly disabled>
                                        <small class="form-text text-muted">Store profile cannot be changed after creation.</small>
                                    @else
                                        <input type="text" class="form-control" 
                                               value="{{ $deviceRepair->storeProfile ? $deviceRepair->storeProfile->name : 'N/A' }}" 
                                               readonly disabled>
                                        <small class="form-text text-muted">You can only edit repairs from your own store profile.</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="device_model_id">Device Model <span class="text-danger">*</span></label>
                                    <select class="form-control @error('device_model_id') is-invalid @enderror" 
                                            id="device_model_id" name="device_model_id" required>
                                        <option value="">Select Device Model</option>
                                        @foreach($deviceModels as $model)
                                            <option value="{{ $model->id }}" {{ old('device_model_id', $deviceRepair->device_model_id) == $model->id ? 'selected' : '' }}>
                                                {{ $model->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('device_model_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="country_code">Country Code <span class="text-danger">*</span></label>
                                    <select class="form-control @error('country_code') is-invalid @enderror" 
                                            id="country_code" name="country_code" required>
                                        <option value="">Select Country</option>
                                        <option value="+20" {{ old('country_code', $deviceRepair->country_code) == '+20' ? 'selected' : '' }}>Egypt (+20)</option>
                                        <option value="+1" {{ old('country_code', $deviceRepair->country_code) == '+1' ? 'selected' : '' }}>USA (+1)</option>
                                        <option value="+44" {{ old('country_code', $deviceRepair->country_code) == '+44' ? 'selected' : '' }}>UK (+44)</option>
                                        <option value="+33" {{ old('country_code', $deviceRepair->country_code) == '+33' ? 'selected' : '' }}>France (+33)</option>
                                        <option value="+49" {{ old('country_code', $deviceRepair->country_code) == '+49' ? 'selected' : '' }}>Germany (+49)</option>
                                        <option value="+966" {{ old('country_code', $deviceRepair->country_code) == '+966' ? 'selected' : '' }}>Saudi Arabia (+966)</option>
                                        <option value="+971" {{ old('country_code', $deviceRepair->country_code) == '+971' ? 'selected' : '' }}>UAE (+971)</option>
                                        <option value="+90" {{ old('country_code', $deviceRepair->country_code) == '+90' ? 'selected' : '' }}>Turkey (+90)</option>
                                    </select>
                                    @error('country_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label for="phone_number">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control @error('phone_number') is-invalid @enderror" 
                                           id="phone_number" name="phone_number" value="{{ old('phone_number', $deviceRepair->phone_number) }}" required>
                                    @error('phone_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="device_serial_number">Device Serial Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('device_serial_number') is-invalid @enderror" 
                                           id="device_serial_number" name="device_serial_number" value="{{ old('device_serial_number', $deviceRepair->device_serial_number) }}" required>
                                    @error('device_serial_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="received" {{ old('status', $deviceRepair->status) == 'received' ? 'selected' : '' }}>Received</option>
                                        <option value="processing" {{ old('status', $deviceRepair->status) == 'processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="ready" {{ old('status', $deviceRepair->status) == 'ready' ? 'selected' : '' }}>Ready</option>
                                        <option value="delivered" {{ old('status', $deviceRepair->status) == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes or Information</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4">{{ old('notes', $deviceRepair->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Tracking Code Display (Read-only) -->
                        <div class="form-group">
                            <label for="tracking_code">Tracking Code</label>
                            <input type="text" class="form-control" id="tracking_code" value="{{ $deviceRepair->tracking_code }}" readonly>
                            <small class="form-text text-muted">Tracking code cannot be changed once created.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Update Device Repair
                            </button>
                            <a href="{{ route('device-repairs.show', $deviceRepair) }}" class="btn btn-info">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            <a href="{{ route('device-repairs.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('phone_number').addEventListener('input', function(e) {
    // Remove any non-numeric characters
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});

document.getElementById('device_serial_number').addEventListener('input', function(e) {
    // Convert to uppercase
    e.target.value = e.target.value.toUpperCase();
});
</script>
@endpush
