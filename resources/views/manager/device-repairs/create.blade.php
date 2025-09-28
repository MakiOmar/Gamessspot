@extends('layouts.admin')

@section('title', 'Add New Device Repair')

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/intlTelInput.min.css') }}">
<style>
    label{
        display:block
    }
    div.iti{
        width: 100%
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
                        <i class="fas fa-plus"></i>
                        Add New Device Repair
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('device-repairs.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to List
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <form action="{{ route('device-repairs.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number">Client Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control @error('phone_number') is-invalid @enderror" 
                                           id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required>
                                    @error('phone_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_name">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('client_name') is-invalid @enderror" 
                                           id="client_name" name="client_name" value="{{ old('client_name') }}" required>
                                    @error('client_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="device_model">Device Model <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('device_model') is-invalid @enderror" 
                                           id="device_model" name="device_model" value="{{ old('device_model') }}" required>
                                    @error('device_model')
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
                                           id="device_serial_number" name="device_serial_number" value="{{ old('device_serial_number') }}" required>
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
                                        <option value="received" {{ old('status', 'received') == 'received' ? 'selected' : '' }}>Received</option>
                                        <option value="processing" {{ old('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="ready" {{ old('status') == 'ready' ? 'selected' : '' }}>Ready</option>
                                        <option value="delivered" {{ old('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
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
                                      id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Create Device Repair
                            </button>
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

@push('js')
<script src="{{ asset('assets/js/intlTelInput.min.js') }}"></script>
<script src="{{ asset('assets/js/utils.js') }}"></script>
<script>
jQuery(document).ready(function($) {
    // Initialize intlTelInput
    const $form = $('form');
    const $input = $("#phone_number");
    
    if ($input.length && window.intlTelInput) {
        const iti = window.intlTelInput($input[0], {
            initialCountry: "auto",
            separateDialCode: true,
            countrySearch: true,
            preferredCountries: ["us", "gb", "eg"],
            allowDropdown: true,
            geoIpLookup: function(success, failure) {
                fetch("https://ipinfo.io/json", {mode: "cors"})
                .then(response => response.json())
                .then((response) => {
                    const countryCode = (response && response.country) ? response.country : "eg";
                    success(countryCode);
                })
                .catch(() => failure());
            }
        });
        
        window.iti = iti;
        
        // Update phone number on input change
        $input.on('input', function() {
            const phoneNumber = iti.getNumber();
        });
        
        // Update phone number on country change
        $input.on('countrychange', function() {
            const phoneNumber = iti.getNumber();
        });
        
        // Check for existing user when phone number loses focus
        $input.on('blur', function() {
            const phoneNumber = iti.getNumber();
            if (phoneNumber && iti.isValidNumber()) {
                // Make AJAX request to check if user exists
                $.ajax({
                    url: '{{ route("device-repairs.check-user") }}',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        phone_number: phoneNumber
                    },
                    success: function(response) {
                        if (response.user) {
                            $('#client_name').val(response.user.name);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle error silently
                    }
                });
            }
        });
        
        // Handle form submission
        $form.on('submit', function(e) {
            let finalPhoneNumber = '';
            
            // Check if the phone number is valid
            if (iti.isValidNumber()) {
                finalPhoneNumber = iti.getNumber();
            } else {
                const phoneNumber = iti.getNumber();
                
                if (phoneNumber) {
                    finalPhoneNumber = phoneNumber;
                } else {
                    // If getNumber() fails, try to get the full number manually
                    const selectedCountry = iti.getSelectedCountryData();
                    const dialCode = selectedCountry.dialCode;
                    const nationalNumber = $input.val().replace(/\D/g, '');
                    const fullNumber = '+' + dialCode + nationalNumber;
                    finalPhoneNumber = fullNumber;
                }
            }
            
            // Update the input value directly
            $input.val(finalPhoneNumber);
        });
    }
    
    // Convert device serial number to uppercase
    $('#device_serial_number').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
});
</script>
@endpush
