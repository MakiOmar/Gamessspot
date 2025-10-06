@extends('layouts.admin')

@section('title', 'Manager - Settings')
@push('css')
<style>
    .settings-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .settings-section h4 {
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
</style>
@endpush

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Application Settings</h1>
    
    <!-- Display Success Message -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Display Error Message -->
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        
        <!-- Application Settings -->
        <div class="settings-section">
            <h4><i class="fas fa-cog"></i> Application Settings</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="app_name" class="form-label">Application Name</label>
                        <input type="text" class="form-control @error('app.name') is-invalid @enderror" 
                               id="app_name" name="app[name]" value="{{ old('app.name', $settings['app']['name']) }}">
                        @error('app.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="app_timezone" class="form-label">Timezone</label>
                        <select class="form-select @error('app.timezone') is-invalid @enderror" 
                                id="app_timezone" name="app[timezone]">
                            <option value="Africa/Cairo" {{ $settings['app']['timezone'] == 'Africa/Cairo' ? 'selected' : '' }}>Africa/Cairo</option>
                            <option value="UTC" {{ $settings['app']['timezone'] == 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ $settings['app']['timezone'] == 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                            <option value="Europe/London" {{ $settings['app']['timezone'] == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                        </select>
                        @error('app.timezone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="app_locale" class="form-label">Locale</label>
                        <select class="form-select @error('app.locale') is-invalid @enderror" 
                                id="app_locale" name="app[locale]">
                            <option value="en" {{ $settings['app']['locale'] == 'en' ? 'selected' : '' }}>English</option>
                            <option value="ar" {{ $settings['app']['locale'] == 'ar' ? 'selected' : '' }}>العربية</option>
                        </select>
                        @error('app.locale')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Settings -->
        <div class="settings-section">
            <h4><i class="fas fa-building"></i> Business Information</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control @error('business.company_name') is-invalid @enderror" 
                               id="company_name" name="business[company_name]" 
                               value="{{ old('business.company_name', $settings['business']['company_name']) }}">
                        @error('business.company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="business_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control @error('business.phone') is-invalid @enderror" 
                               id="business_phone" name="business[phone]" 
                               value="{{ old('business.phone', $settings['business']['phone']) }}">
                        @error('business.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="business_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('business.email') is-invalid @enderror" 
                               id="business_email" name="business[email]" 
                               value="{{ old('business.email', $settings['business']['email']) }}">
                        @error('business.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="business_address" class="form-label">Address</label>
                        <textarea class="form-control @error('business.address') is-invalid @enderror" 
                                  id="business_address" name="business[address]" rows="3">{{ old('business.address', $settings['business']['address']) }}</textarea>
                        @error('business.address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Settings -->
        <div class="settings-section">
            <h4><i class="fas fa-shopping-cart"></i> Order Settings</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_approve" name="orders[auto_approve]" 
                                   value="1" {{ old('orders.auto_approve', $settings['orders']['auto_approve']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_approve">
                                Auto-approve orders
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="notification_email" class="form-label">Order Notification Email</label>
                        <input type="email" class="form-control @error('orders.notification_email') is-invalid @enderror" 
                               id="notification_email" name="orders[notification_email]" 
                               value="{{ old('orders.notification_email', $settings['orders']['notification_email']) }}">
                        @error('orders.notification_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="max_order_amount" class="form-label">Maximum Order Amount</label>
                        <input type="number" class="form-control @error('orders.max_order_amount') is-invalid @enderror" 
                               id="max_order_amount" name="orders[max_order_amount]" 
                               value="{{ old('orders.max_order_amount', $settings['orders']['max_order_amount']) }}">
                        @error('orders.max_order_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="settings-section">
            <h4><i class="fas fa-bell"></i> Notification Settings</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="email_enabled" name="notifications[email_enabled]" 
                                   value="1" {{ old('notifications.email_enabled', $settings['notifications']['email_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_enabled">
                                Enable Email Notifications
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sms_enabled" name="notifications[sms_enabled]" 
                                   value="1" {{ old('notifications.sms_enabled', $settings['notifications']['sms_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="sms_enabled">
                                Enable SMS Notifications
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="order_notifications" name="notifications[order_notifications]" 
                                   value="1" {{ old('notifications.order_notifications', $settings['notifications']['order_notifications']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="order_notifications">
                                Order Notifications
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- POS Settings -->
        <div class="settings-section">
            <h4><i class="fas fa-cash-register"></i> POS (Point of Sale) Settings</h4>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">SKU Codes</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_offline_sku" class="form-label">Offline SKU</label>
                                <input type="text" class="form-control @error('pos.offline_sku') is-invalid @enderror" 
                                       id="pos_offline_sku" name="pos[offline_sku]" 
                                       value="{{ old('pos.offline_sku', $settings['pos']['offline_sku']) }}">
                                @error('pos.offline_sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_secondary_sku" class="form-label">Secondary SKU</label>
                                <input type="text" class="form-control @error('pos.secondary_sku') is-invalid @enderror" 
                                       id="pos_secondary_sku" name="pos[secondary_sku]" 
                                       value="{{ old('pos.secondary_sku', $settings['pos']['secondary_sku']) }}">
                                @error('pos.secondary_sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_primary_sku" class="form-label">Primary SKU</label>
                                <input type="text" class="form-control @error('pos.primary_sku') is-invalid @enderror" 
                                       id="pos_primary_sku" name="pos[primary_sku]" 
                                       value="{{ old('pos.primary_sku', $settings['pos']['primary_sku']) }}">
                                @error('pos.primary_sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_card_sku" class="form-label">Card SKU</label>
                                <input type="text" class="form-control @error('pos.card_sku') is-invalid @enderror" 
                                       id="pos_card_sku" name="pos[card_sku]" 
                                       value="{{ old('pos.card_sku', $settings['pos']['card_sku']) }}">
                                @error('pos.card_sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">POS IDs</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_offline_id" class="form-label">Offline ID</label>
                                <input type="text" class="form-control @error('pos.offline_id') is-invalid @enderror" 
                                       id="pos_offline_id" name="pos[offline_id]" 
                                       value="{{ old('pos.offline_id', $settings['pos']['offline_id']) }}">
                                @error('pos.offline_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_secondary_id" class="form-label">Secondary ID</label>
                                <input type="text" class="form-control @error('pos.secondary_id') is-invalid @enderror" 
                                       id="pos_secondary_id" name="pos[secondary_id]" 
                                       value="{{ old('pos.secondary_id', $settings['pos']['secondary_id']) }}">
                                @error('pos.secondary_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_primary_id" class="form-label">Primary ID</label>
                                <input type="text" class="form-control @error('pos.primary_id') is-invalid @enderror" 
                                       id="pos_primary_id" name="pos[primary_id]" 
                                       value="{{ old('pos.primary_id', $settings['pos']['primary_id']) }}">
                                @error('pos.primary_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pos_card_id" class="form-label">Card ID</label>
                                <input type="text" class="form-control @error('pos.card_id') is-invalid @enderror" 
                                       id="pos_card_id" name="pos[card_id]" 
                                       value="{{ old('pos.card_id', $settings['pos']['card_id']) }}">
                                @error('pos.card_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between">
            <a href="{{ route('settings.reset') }}" class="btn btn-warning" 
               onclick="return confirm('Are you sure you want to reset all settings to default values?')">
                <i class="fas fa-undo"></i> Reset to Defaults
            </a>
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
