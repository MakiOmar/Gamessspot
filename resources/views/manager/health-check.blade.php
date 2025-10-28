@extends('layouts.admin')

@section('title', 'System Health Check')

@push('css')
<style>
    .health-card {
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        transition: transform 0.2s;
    }
    
    .health-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.85rem;
    }
    
    .status-working {
        background-color: #28a745;
        color: white;
    }
    
    .status-error {
        background-color: #dc3545;
        color: white;
    }
    
    .status-warning {
        background-color: #ffc107;
        color: #333;
    }
    
    .status-not-available {
        background-color: #6c757d;
        color: white;
    }
    
    .info-row {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #555;
    }
    
    .info-value {
        color: #333;
    }
    
    .section-icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    .extension-badge {
        display: inline-block;
        padding: 3px 10px;
        margin: 3px;
        border-radius: 15px;
        font-size: 0.8rem;
    }
    
    .extension-loaded {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .extension-not-loaded {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .refresh-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
</style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4">
                <i class="fas fa-heartbeat text-danger"></i> System Health Check
            </h1>
            <p class="text-muted">Comprehensive overview of your system's health and configuration</p>
            <small class="text-muted">Last checked: {{ now()->format('Y-m-d H:i:s') }}</small>
        </div>
    </div>

    <!-- Quick Status Overview -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card health-card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-database fa-3x mb-2"></i>
                    <h5>Database</h5>
                    <span class="status-badge {{ $healthData['database']['connection'] === 'working' ? 'status-working' : 'status-error' }}">
                        {{ strtoupper($healthData['database']['connection']) }}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card health-card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-server fa-3x mb-2"></i>
                    <h5>Redis</h5>
                    <span class="status-badge 
                        {{ $healthData['redis']['status'] === 'working' ? 'status-working' : '' }}
                        {{ $healthData['redis']['status'] === 'error' ? 'status-error' : '' }}
                        {{ $healthData['redis']['status'] === 'not_available' ? 'status-not-available' : '' }}">
                        {{ strtoupper(str_replace('_', ' ', $healthData['redis']['status'])) }}
                    </span>
                </div>
            </div>
        </div>
        
        @if(isset($healthData['memcached']) && $healthData['memcached']['configured'])
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card health-card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-memory fa-3x mb-2"></i>
                    <h5>Memcached</h5>
                    <span class="status-badge 
                        {{ $healthData['memcached']['status'] === 'working' ? 'status-working' : '' }}
                        {{ $healthData['memcached']['status'] === 'error' ? 'status-error' : '' }}
                        {{ $healthData['memcached']['status'] === 'not_available' ? 'status-not-available' : '' }}
                        {{ $healthData['memcached']['status'] === 'not_configured' ? 'status-not-available' : '' }}">
                        {{ strtoupper(str_replace('_', ' ', $healthData['memcached']['status'])) }}
                    </span>
                </div>
            </div>
        </div>
        @endif
        
        @if(isset($healthData['file_cache']))
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card health-card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-file fa-3x mb-2"></i>
                    <h5>File Cache</h5>
                    <span class="status-badge status-working">
                        {{ strtoupper($healthData['file_cache']['status']) }}
                    </span>
                </div>
            </div>
        </div>
        @endif
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card health-card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-layer-group fa-3x mb-2"></i>
                    <h5>Cache</h5>
                    <span class="status-badge {{ $healthData['cache']['test'] === 'working' ? 'status-working' : 'status-error' }}">
                        {{ strtoupper($healthData['cache']['test']) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="row">
        <!-- PHP Information -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-info text-white">
                    <h4><i class="fab fa-php"></i> PHP Configuration</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Version:</span>
                        <span class="info-value float-right">{{ $healthData['php']['version'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Memory Limit:</span>
                        <span class="info-value float-right">{{ $healthData['php']['memory_limit'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Max Execution Time:</span>
                        <span class="info-value float-right">{{ $healthData['php']['max_execution_time'] }}s</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Post Max Size:</span>
                        <span class="info-value float-right">{{ $healthData['php']['post_max_size'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Upload Max Filesize:</span>
                        <span class="info-value float-right">{{ $healthData['php']['upload_max_filesize'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laravel Information -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-danger text-white">
                    <h4><i class="fab fa-laravel"></i> Laravel Configuration</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Version:</span>
                        <span class="info-value float-right">{{ $healthData['laravel']['version'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Environment:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['laravel']['environment'] === 'production' ? 'success' : 'warning' }}">
                                {{ strtoupper($healthData['laravel']['environment']) }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Debug Mode:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['laravel']['debug_mode'] ? 'danger' : 'success' }}">
                                {{ $healthData['laravel']['debug_mode'] ? 'ENABLED' : 'DISABLED' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Timezone:</span>
                        <span class="info-value float-right">{{ $healthData['laravel']['timezone'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Locale:</span>
                        <span class="info-value float-right">{{ $healthData['laravel']['locale'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Information -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-database"></i> Database</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Default Connection:</span>
                        <span class="info-value float-right">{{ $healthData['database']['default'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value float-right">
                            <span class="status-badge {{ $healthData['database']['connection'] === 'working' ? 'status-working' : 'status-error' }}">
                                {{ strtoupper($healthData['database']['connection']) }}
                            </span>
                        </span>
                    </div>
                    @if($healthData['database']['connection'] === 'working')
                        <div class="info-row">
                            <span class="info-label">Driver:</span>
                            <span class="info-value float-right">{{ $healthData['database']['driver'] }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Database:</span>
                            <span class="info-value float-right">{{ $healthData['database']['database'] }}</span>
                        </div>
                    @else
                        <div class="alert alert-danger mt-2">
                            <strong>Error:</strong> {{ $healthData['database']['message'] ?? 'Unknown error' }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Redis Information -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-danger text-white">
                    <h4><i class="fas fa-server"></i> Redis</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Configured:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['redis']['configured'] ? 'success' : 'secondary' }}">
                                {{ $healthData['redis']['configured'] ? 'YES' : 'NO' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Extension Loaded:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['redis']['extension_loaded'] ? 'success' : 'danger' }}">
                                {{ $healthData['redis']['extension_loaded'] ? 'YES' : 'NO' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value float-right">
                            <span class="status-badge 
                                {{ $healthData['redis']['status'] === 'working' ? 'status-working' : '' }}
                                {{ $healthData['redis']['status'] === 'error' ? 'status-error' : '' }}
                                {{ $healthData['redis']['status'] === 'not_available' ? 'status-not-available' : '' }}
                                {{ $healthData['redis']['status'] === 'not_checked' ? 'status-warning' : '' }}">
                                {{ strtoupper(str_replace('_', ' ', $healthData['redis']['status'])) }}
                            </span>
                        </span>
                    </div>
                    @if($healthData['redis']['status'] === 'working')
                        <div class="info-row">
                            <span class="info-label">Host:</span>
                            <span class="info-value float-right">{{ $healthData['redis']['host'] }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Port:</span>
                            <span class="info-value float-right">{{ $healthData['redis']['port'] }}</span>
                        </div>
                    @elseif(isset($healthData['redis']['message']))
                        <div class="alert alert-{{ $healthData['redis']['status'] === 'error' ? 'danger' : 'warning' }} mt-2 mb-0">
                            {{ $healthData['redis']['message'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- File Cache Information -->
        @if(isset($healthData['file_cache']))
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-secondary text-white">
                    <h4><i class="fas fa-file"></i> File Cache</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value float-right">
                            <span class="status-badge status-working">
                                {{ strtoupper($healthData['file_cache']['status']) }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cache Path:</span>
                        <span class="info-value float-right">
                            <small>{{ basename($healthData['file_cache']['path']) }}</small>
                        </span>
                    </div>
                    
                    @if(isset($healthData['file_cache']['statistics']))
                        <div class="info-row">
                            <span class="info-label">Total Files:</span>
                            <span class="info-value float-right">
                                <span class="badge badge-info">{{ number_format($healthData['file_cache']['statistics']['total_files']) }}</span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total Size:</span>
                            <span class="info-value float-right">{{ $healthData['file_cache']['statistics']['total_size_formatted'] }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Writable:</span>
                            <span class="info-value float-right">
                                <span class="badge badge-{{ $healthData['file_cache']['statistics']['writable'] ? 'success' : 'danger' }}">
                                    {{ $healthData['file_cache']['statistics']['writable'] ? 'YES' : 'NO' }}
                                </span>
                            </span>
                        </div>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <strong><i class="fas fa-info-circle"></i> File Cache Info</strong>
                            <ul class="mb-0 mt-2">
                                <li>Cache files are stored on disk</li>
                                <li>Slower than Redis/Memcached but requires no setup</li>
                                <li>Files are automatically cleaned up by Laravel</li>
                                <li>Great for development, consider Redis/Memcached for production</li>
                            </ul>
                        </div>
                    @endif
                    
                    @if(isset($healthData['file_cache']['error']))
                        <div class="alert alert-danger mt-2 mb-0">
                            <strong>Error:</strong> {{ $healthData['file_cache']['error'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
        
        <!-- Memcached Information (Only if configured) -->
        @if(isset($healthData['memcached']) && $healthData['memcached']['configured'])
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-info text-white">
                    <h4><i class="fas fa-memory"></i> Memcached</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Configured:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['memcached']['configured'] ? 'success' : 'secondary' }}">
                                {{ $healthData['memcached']['configured'] ? 'YES' : 'NO' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Extension Loaded:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['memcached']['extension_loaded'] ? 'success' : 'danger' }}">
                                {{ $healthData['memcached']['extension_loaded'] ? 'YES' : 'NO' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value float-right">
                            <span class="status-badge 
                                {{ $healthData['memcached']['status'] === 'working' ? 'status-working' : '' }}
                                {{ $healthData['memcached']['status'] === 'error' ? 'status-error' : '' }}
                                {{ $healthData['memcached']['status'] === 'not_available' ? 'status-not-available' : '' }}
                                {{ $healthData['memcached']['status'] === 'not_checked' ? 'status-warning' : '' }}">
                                {{ strtoupper(str_replace('_', ' ', $healthData['memcached']['status'])) }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Host:</span>
                        <span class="info-value float-right">{{ $healthData['memcached']['host'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Port:</span>
                        <span class="info-value float-right">{{ $healthData['memcached']['port'] }}</span>
                    </div>
                    
                    @if($healthData['memcached']['status'] === 'working' && isset($healthData['memcached']['memory']))
                        <hr>
                        <h6 class="font-weight-bold mb-2"><i class="fas fa-memory"></i> Memory Usage</h6>
                        <div class="info-row">
                            <span class="info-label">Used:</span>
                            <span class="info-value float-right">{{ $healthData['memcached']['memory']['used_formatted'] }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Max:</span>
                            <span class="info-value float-right">{{ $healthData['memcached']['memory']['max_formatted'] }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Free:</span>
                            <span class="info-value float-right">{{ $healthData['memcached']['memory']['free_formatted'] }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Usage:</span>
                            <span class="info-value float-right">
                                <div class="progress" style="width: 100px; height: 20px;">
                                    <div class="progress-bar bg-{{ $healthData['memcached']['memory']['usage_percent'] > 80 ? 'danger' : ($healthData['memcached']['memory']['usage_percent'] > 60 ? 'warning' : 'success') }}" 
                                         role="progressbar" 
                                         style="width: {{ $healthData['memcached']['memory']['usage_percent'] }}%">
                                        {{ $healthData['memcached']['memory']['usage_percent'] }}%
                                    </div>
                                </div>
                            </span>
                        </div>
                        
                        @if(isset($healthData['memcached']['performance']))
                            <hr>
                            <h6 class="font-weight-bold mb-2"><i class="fas fa-chart-line"></i> Performance</h6>
                            <div class="info-row">
                                <span class="info-label">Current Items:</span>
                                <span class="info-value float-right">{{ number_format($healthData['memcached']['performance']['curr_items']) }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Items:</span>
                                <span class="info-value float-right">{{ number_format($healthData['memcached']['performance']['total_items']) }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Evictions:</span>
                                <span class="info-value float-right">
                                    <span class="badge badge-{{ $healthData['memcached']['performance']['evictions'] > 100 ? 'danger' : ($healthData['memcached']['performance']['evictions'] > 10 ? 'warning' : 'success') }}">
                                        {{ number_format($healthData['memcached']['performance']['evictions']) }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Hit Rate:</span>
                                <span class="info-value float-right">
                                    <span class="badge badge-{{ $healthData['memcached']['performance']['hit_rate'] > 80 ? 'success' : ($healthData['memcached']['performance']['hit_rate'] > 60 ? 'warning' : 'danger') }}">
                                        {{ $healthData['memcached']['performance']['hit_rate'] }}%
                                    </span>
                                </span>
                            </div>
                            
                            @if($healthData['memcached']['memory']['usage_percent'] > 80)
                                <div class="alert alert-danger mt-2 mb-0">
                                    <strong><i class="fas fa-exclamation-triangle"></i> High Memory Usage!</strong><br>
                                    Memcached is using {{ $healthData['memcached']['memory']['usage_percent'] }}% of available memory.
                                    Consider clearing cache or increasing Memcached memory allocation.
                                </div>
                            @endif
                            
                            @if($healthData['memcached']['performance']['evictions'] > 100)
                                <div class="alert alert-warning mt-2 mb-0">
                                    <strong><i class="fas fa-exclamation-triangle"></i> High Eviction Rate!</strong><br>
                                    {{ number_format($healthData['memcached']['performance']['evictions']) }} items have been evicted due to memory pressure.
                                    This may indicate insufficient Memcached memory.
                                </div>
                            @endif
                        @endif
                    @endif
                    
                    @if($healthData['memcached']['status'] === 'error' && isset($healthData['memcached']['message']))
                        <div class="alert alert-danger mt-2 mb-2">
                            <strong><i class="fas fa-exclamation-triangle"></i> Error:</strong><br>
                            {{ $healthData['memcached']['message'] }}
                            
                            @if(isset($healthData['memcached']['solution']))
                                <hr>
                                <strong><i class="fas fa-lightbulb"></i> Solution:</strong><br>
                                {{ $healthData['memcached']['solution'] }}
                            @endif
                            
                            @if(isset($healthData['memcached']['result_message']))
                                <hr>
                                <small><strong>Details:</strong> {{ $healthData['memcached']['result_message'] }}</small>
                            @endif
                            
                            @if(isset($healthData['memcached']['result_code']))
                                <br><small><strong>Result Code:</strong> {{ $healthData['memcached']['result_code'] }}</small>
                            @endif
                        </div>
                        
                        <div class="alert alert-info mt-2 mb-0">
                            <strong><i class="fas fa-info-circle"></i> Troubleshooting Steps:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Check if Memcached service is running:
                                    <br><code class="text-dark">netstat -an | findstr :11211</code> (Windows)
                                    <br><code class="text-dark">service memcached status</code> (Linux)
                                </li>
                                <li>Start Memcached service if not running:
                                    <br>Windows: Check WAMP/XAMPP services
                                    <br>Linux: <code class="text-dark">sudo service memcached start</code>
                                </li>
                                <li>Verify host and port in your <code class="text-dark">.env</code> file:
                                    <br><code class="text-dark">MEMCACHED_HOST={{ $healthData['memcached']['host'] }}</code>
                                    <br><code class="text-dark">MEMCACHED_PORT={{ $healthData['memcached']['port'] }}</code>
                                </li>
                                <li>Check firewall settings if using remote Memcached server</li>
                            </ol>
                        </div>
                    @elseif($healthData['memcached']['status'] === 'not_available' && isset($healthData['memcached']['message']))
                        <div class="alert alert-warning mt-2 mb-0">
                            {{ $healthData['memcached']['message'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Cache Configuration -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-success text-white">
                    <h4><i class="fas fa-layer-group"></i> Cache Configuration</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Default Driver:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-primary">{{ strtoupper($healthData['cache']['default_driver']) }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Test Status:</span>
                        <span class="info-value float-right">
                            <span class="status-badge {{ $healthData['cache']['test'] === 'working' ? 'status-working' : 'status-error' }}">
                                {{ strtoupper($healthData['cache']['test']) }}
                            </span>
                        </span>
                    </div>
                    @if(isset($healthData['cache']['test_message']))
                        <div class="alert alert-danger mt-2 mb-0">
                            <strong>Error:</strong> {{ $healthData['cache']['test_message'] }}
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Available Stores:</span>
                        <div class="mt-2">
                            @foreach($healthData['cache']['stores'] as $store)
                                <span class="badge badge-secondary mr-1">{{ $store }}</span>
                            @endforeach
                        </div>
                    </div>
                    
                    @if(isset($healthData['cache']['stats']) && !isset($healthData['cache']['stats']['error']))
                        <hr>
                        <h6 class="font-weight-bold mt-3">📊 Cache Statistics</h6>
                        <div class="info-row">
                            <span class="info-label">Total Cache Keys:</span>
                            <span class="info-value float-right">
                                <span class="badge badge-info">{{ $healthData['cache']['stats']['total_keys'] }}</span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cache Statistics Breakdown -->
        @if(isset($healthData['cache']['stats']) && !isset($healthData['cache']['stats']['error']))
        <div class="col-12 mb-4">
            <div class="card health-card">
                <div class="card-header bg-success text-white">
                    <h4><i class="fas fa-chart-bar"></i> Cache Keys by Category</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Shows the number of cache entries stored for each category. 
                        The number represents cached entries, not the values they store.
                    </p>
                    
                    <div class="row">
                        @foreach($healthData['cache']['stats']['keys_by_prefix'] as $prefix => $count)
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card {{ $count > 0 ? 'border-success' : 'border-secondary' }}">
                                <div class="card-body text-center">
                                    <h3 class="mb-0 {{ $count > 0 ? 'text-success' : 'text-muted' }}">
                                        {{ $count }}
                                    </h3>
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> {{ ucfirst($prefix) }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <strong><i class="fas fa-lightbulb"></i> What do these numbers mean?</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Dashboard:</strong> Cached dashboard statistics (today's orders, etc.)</li>
                            <li><strong>Users:</strong> Cached user counts and statistics</li>
                            <li><strong>Orders:</strong> Cached order statistics (unique buyers, etc.)</li>
                            <li><strong>Accounts:</strong> Cached account costs and statistics</li>
                            <li><strong>Cards:</strong> Cached card costs and statistics</li>
                            <li><strong>Games:</strong> Cached game listings (PS4/PS5 pages)</li>
                            <li><strong>Devices:</strong> Cached device repair statistics</li>
                        </ul>
                        <hr>
                        <p class="mb-0">
                            <strong>Example:</strong> "Users: 1" means there is 1 cached entry about users 
                            (e.g., total user count = {{ \App\Models\User::count() }} users). 
                            The number shows cache entries, not the actual values stored.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Session Configuration -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-warning text-dark">
                    <h4><i class="fas fa-cookie"></i> Session Configuration</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Driver:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-primary">{{ strtoupper($healthData['session']['driver']) }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lifetime:</span>
                        <span class="info-value float-right">{{ $healthData['session']['lifetime'] }} minutes</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Secure:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['session']['secure'] ? 'success' : 'secondary' }}">
                                {{ $healthData['session']['secure'] ? 'YES' : 'NO' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">SameSite:</span>
                        <span class="info-value float-right">{{ $healthData['session']['same_site'] ?? 'null' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Configuration -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-secondary text-white">
                    <h4><i class="fas fa-tasks"></i> Queue Configuration</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Default Connection:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-primary">{{ strtoupper($healthData['queue']['default']) }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Available Connections:</span>
                        <div class="mt-2">
                            @foreach($healthData['queue']['connections'] as $connection)
                                <span class="badge badge-secondary mr-1">{{ $connection }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage Information -->
        <div class="col-lg-6 mb-4">
            <div class="card health-card">
                <div class="card-header bg-dark text-white">
                    <h4><i class="fas fa-hdd"></i> Storage</h4>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Default Disk:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-primary">{{ strtoupper($healthData['storage']['disk']) }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Writable:</span>
                        <span class="info-value float-right">
                            <span class="badge badge-{{ $healthData['storage']['writable'] ? 'success' : 'danger' }}">
                                {{ $healthData['storage']['writable'] ? 'YES' : 'NO' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Free Space:</span>
                        <span class="info-value float-right">{{ $healthData['storage']['free_space'] }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Space:</span>
                        <span class="info-value float-right">{{ $healthData['storage']['total_space'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHP Extensions -->
        <div class="col-12 mb-4">
            <div class="card health-card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-puzzle-piece"></i> PHP Extensions</h4>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Required Extensions</h5>
                    <div class="mb-4">
                        @foreach($healthData['extensions']['required'] as $ext => $loaded)
                            <span class="extension-badge {{ $loaded ? 'extension-loaded' : 'extension-not-loaded' }}">
                                <i class="fas fa-{{ $loaded ? 'check-circle' : 'times-circle' }}"></i> {{ $ext }}
                            </span>
                        @endforeach
                    </div>
                    
                    <h5 class="mb-3">Optional Extensions</h5>
                    <div>
                        @foreach($healthData['extensions']['optional'] as $ext => $loaded)
                            <span class="extension-badge {{ $loaded ? 'extension-loaded' : 'extension-not-loaded' }}">
                                <i class="fas fa-{{ $loaded ? 'check-circle' : 'times-circle' }}"></i> {{ $ext }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Refresh Button -->
    <a href="{{ route('manager.health-check') }}" class="btn btn-primary refresh-btn" title="Refresh Health Check">
        <i class="fas fa-sync-alt fa-2x"></i>
    </a>
</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000); // 5 minutes
</script>
@endpush

