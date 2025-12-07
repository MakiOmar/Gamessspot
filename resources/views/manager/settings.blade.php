@extends('layouts.admin')

@section('title', 'Manager - Settings')
@push('css')
<style>
    .settings-container {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .settings-sidebar {
        background: #f8f9fa;
        border-right: 1px solid #dee2e6;
        min-height: 600px;
    }
    
    .settings-nav {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .settings-nav li {
        border-bottom: 1px solid #e9ecef;
    }
    
    .settings-nav li:last-child {
        border-bottom: none;
    }
    
    .settings-nav a {
        display: block;
        padding: 15px 20px;
        color: #495057;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    
    .settings-nav a:hover {
        background: #e9ecef;
        color: #007bff;
        border-left-color: #007bff;
    }
    
    .settings-nav a.active {
        background: #007bff;
        color: white;
        border-left-color: #007bff;
    }
    
    .settings-nav i {
        width: 20px;
        margin-right: 10px;
    }
    
    .settings-content {
        padding: 30px;
        min-height: 600px;
    }
    
    .settings-tab {
        display: none;
    }
    
    .settings-tab.active {
        display: block;
    }
    
    .settings-tab h3 {
        color: #495057;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
    }
    
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .settings-actions {
        position: sticky;
        bottom: 0;
        background: #fff;
        padding: 20px 0;
        border-top: 1px solid #dee2e6;
        margin-top: 30px;
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

    <div class="settings-container">
        <div class="row g-0">
            <!-- Sidebar Navigation -->
            <div class="col-md-3">
                <div class="settings-sidebar">
                    <ul class="settings-nav">
                        <li>
                            <a href="#application" class="settings-nav-link active" data-tab="application">
                                <i class="fas fa-cog"></i>
                                Application
                            </a>
                        </li>
                        <li>
                            <a href="#business" class="settings-nav-link" data-tab="business">
                                <i class="fas fa-building"></i>
                                Business
                            </a>
                        </li>
                        <li>
                            <a href="#orders" class="settings-nav-link" data-tab="orders">
                                <i class="fas fa-shopping-cart"></i>
                                Orders
                            </a>
                        </li>
                        <li>
                            <a href="#notifications" class="settings-nav-link" data-tab="notifications">
                                <i class="fas fa-bell"></i>
                                Notifications
                            </a>
                        </li>
                        <li>
                            <a href="#pos" class="settings-nav-link" data-tab="pos">
                                <i class="fas fa-cash-register"></i>
                                POS Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-md-9">
                <div class="settings-content">
                    <form action="{{ route('settings.update') }}" method="POST" id="settings-form">
                        @csrf
                        <!-- Application Settings Tab -->
                        <div id="application" class="settings-tab active">
                            <h3><i class="fas fa-cog"></i> Application Settings</h3>
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

                        <!-- Business Settings Tab -->
                        <div id="business" class="settings-tab">
                            <h3><i class="fas fa-building"></i> Business Information</h3>
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

                        <!-- Order Settings Tab -->
                        <div id="orders" class="settings-tab">
                            <h3><i class="fas fa-shopping-cart"></i> Order Settings</h3>
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

                        <!-- Notification Settings Tab -->
                        <div id="notifications" class="settings-tab">
                            <h3><i class="fas fa-bell"></i> Notification Settings</h3>
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

                        <!-- POS Settings Tab -->
                        <div id="pos" class="settings-tab">
                            <h3><i class="fas fa-cash-register"></i> POS (Point of Sale) Settings</h3>
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
                            
                            @if(!empty($posLocationProfiles))
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6 class="text-muted mb-3">Store Profile POS Locations</h6>
                                    <p class="text-sm text-muted">
                                        Configure the POS location ID for each store profile. Defaults mirror the original mapping and can be adjusted as needed.
                                    </p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 35%;">Store Profile</th>
                                                    <th style="width: 20%;">Default ID</th>
                                                    <th style="width: 25%;">POS Location ID</th>
                                                    <th style="width: 20%;">&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($posLocationProfiles as $profile)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $profile['name'] ?? 'Store #' . $profile['id'] }}</strong><br>
                                                            <small class="text-muted">Key: {{ $profile['key'] }}</small>
                                                        </td>
                                                        <td>
                                                            @if($profile['default'] !== null)
                                                                <span class="badge bg-secondary">{{ $profile['default'] }}</span>
                                                            @else
                                                                <span class="text-muted">Not set</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                class="form-control form-control-sm @error('pos_location.' . $profile['key']) is-invalid @enderror"
                                                                name="pos_location[{{ $profile['key'] }}]"
                                                                value="{{ old('pos_location.' . $profile['key'], $profile['value']) }}"
                                                                placeholder="POS Location ID">
                                                            @error('pos_location.' . $profile['key'])
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            @if(($profile['value'] ?? null) !== null && $profile['default'] !== null && (int) $profile['value'] !== (int) $profile['default'])
                                                                <span class="badge bg-info text-dark">Custom</span>
                                                            @else
                                                                <span class="text-muted">Using default</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- POS Credentials Section -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6 class="text-muted mb-3">POS Credentials</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="pos_username" class="form-label">Username</label>
                                                <input type="text" class="form-control @error('pos.username') is-invalid @enderror" 
                                                       id="pos_username" name="pos[username]" 
                                                       value="{{ old('pos.username', $settings['pos']['username']) }}">
                                                @error('pos.username')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="pos_password" class="form-label">Password</label>
                                                <input type="password" class="form-control @error('pos.password') is-invalid @enderror" 
                                                       id="pos_password" name="pos[password]" 
                                                       value="{{ old('pos.password', $settings['pos']['password']) }}">
                                                @error('pos.password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="pos_base_url" class="form-label">Base URL</label>
                                                <input type="url" class="form-control @error('pos.base_url') is-invalid @enderror" 
                                                       id="pos_base_url" name="pos[base_url]" 
                                                       value="{{ old('pos.base_url', $settings['pos']['base_url']) }}">
                                                @error('pos.base_url')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="settings-actions">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('settings.reset') }}" class="btn btn-warning" 
                                       onclick="return confirm('Are you sure you want to reset all settings to default values?')">
                                        <i class="fas fa-undo"></i> Reset to Defaults
                                    </a>
                                    <a href="{{ route('settings.export') }}" class="btn btn-info text-white">
                                        <i class="fas fa-download"></i> Export Settings
                                    </a>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importSettingsModal">
                                        <i class="fas fa-upload"></i> Import Settings
                                    </button>
                                    <button type="button" class="btn btn-danger" id="clearAllSettingsBtn">
                                        <i class="fas fa-trash-alt"></i> Clear All Settings
                                    </button>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Settings Modal -->
<div class="modal fade" id="importSettingsModal" tabindex="-1" aria-labelledby="importSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importSettingsModalLabel">
                    <i class="fas fa-upload"></i> Import Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('settings.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Importing settings will overwrite your current settings. Make sure to export your current settings first as a backup.
                    </div>
                    <div class="mb-3">
                        <label for="settings_file" class="form-label">Select Settings File (JSON)</label>
                        <input type="file" class="form-control @error('settings_file') is-invalid @enderror" 
                               id="settings_file" name="settings_file" accept=".json" required>
                        @error('settings_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Maximum file size: 5MB. Only JSON files are accepted.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Import Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab navigation functionality
    const navLinks = document.querySelectorAll('.settings-nav-link');
    const tabs = document.querySelectorAll('.settings-tab');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all nav links and tabs
            navLinks.forEach(navLink => navLink.classList.remove('active'));
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked nav link
            this.classList.add('active');
            
            // Show corresponding tab
            const targetTab = this.getAttribute('data-tab');
            const tabElement = document.getElementById(targetTab);
            if (tabElement) {
                tabElement.classList.add('active');
            }
        });
    });
    
    // Handle URL hash for direct tab access
    const hash = window.location.hash;
    if (hash) {
        const tabName = hash.substring(1);
        const tabLink = document.querySelector(`[data-tab="${tabName}"]`);
        if (tabLink) {
            tabLink.click();
        }
    }

    // Clear All Settings with SweetAlert confirmation
    const clearAllSettingsBtn = document.getElementById('clearAllSettingsBtn');
    if (clearAllSettingsBtn) {
        clearAllSettingsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you absolutely sure?',
                html: '<div class="text-start">' +
                      '<p class="mb-3"><strong>This action will permanently delete ALL settings!</strong></p>' +
                      '<p class="mb-2">This includes:</p>' +
                      '<ul class="text-start mb-3">' +
                      '<li>Application settings</li>' +
                      '<li>Business information</li>' +
                      '<li>Order settings</li>' +
                      '<li>Notification preferences</li>' +
                      '<li>POS configuration</li>' +
                      '</ul>' +
                      '<p class="text-danger"><strong>This action cannot be undone!</strong></p>' +
                      '<p class="text-muted">Make sure to export your settings first as a backup.</p>' +
                      '</div>',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete all settings!',
                cancelButtonText: 'No, cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true,
                allowOutsideClick: false,
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Clearing Settings...',
                        text: 'Please wait while we clear all settings.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Create a form and submit it
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("settings.clear") }}';
                    
                    // Add CSRF token
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }
});
</script>
@endpush
