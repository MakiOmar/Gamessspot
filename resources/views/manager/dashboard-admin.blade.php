<!--begin::Row-->
<div class="row">
    <!-- Box 1 -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-dark">
            <div class="inner">
                <h3 class="stat-value d-none">{{ number_format($accountsCost, 2) }}<sup class="fs-5">EGP</sup></h3>
                <p>Mails Assets</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.58a.75.75 0 00.705.522H17.25a.75.75 0 000-1.5H7.5l-.5-1.5h9.25a.75.75 0 000-1.5H6.75l-.5-1.5h11.25a.75.75 0 000-1.5H5.25a.75.75 0 00-.705.522L2.25 2.25zM3.75 20.25a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm13.5 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
            </svg>
            <a href="#" class="small-box-footer toggle-stats link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>

    <!-- Box 2 -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-secondary">
            <div class="inner">
                <h3 class="stat-value d-none">{{ number_format($totalCodeCost, 2) }}<sup class="fs-5">EGP</sup></h3>
                <p>Codes Assets</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v7.5c0 1.036.84 1.875 1.875 1.875h.75a3 3 0 013 3v.75H3v-.75a3 3 0 013-3h.75v-7.5A1.875 1.875 0 016.375 2.25h12zM9.75 8.25a.75.75 0 00-.75.75v2.25c0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75V9a.75.75 0 00-.75-.75h-4.5z"></path>
            </svg>
            <a href="#" class="small-box-footer toggle-stats link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>

    <!-- Box 3 -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3 class="stat-value d-none">{{ number_format($total, 2) }}<sup class="fs-5">EGP</sup></h3>
                <p>Total</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.004l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM16.9 19.024c.087-.124.163-.251.227-.382 1.546-2.97 1.546-6.57 0-9.551A6.986 6.986 0 0018 5.25a6.986 6.986 0 00-1.653 2.129c-.087.124-.163.251-.227.382-1.546 2.97-1.546 6.57 0 9.551a6.986 6.986 0 001.653 2.129z"></path>
            </svg>
            <a href="#" class="small-box-footer toggle-stats link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>

    <!-- Box 4 -->
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3 class="stat-value d-none">{{ $totalOrderCount }}</h3>
                <p>Today orders</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path clip-rule="evenodd" fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 0116.5 0V15a.75.75 0 01-.75.75h-15a.75.75 0 01-.75-.75v-1.5zm8.25-3a.75.75 0 01.75-.75h3a.75.75 0 010 1.5h-3a.75.75 0 01-.75-.75zM3.75 9a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm0 3a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zM6 9a.75.75 0 01.75-.75h.75a.75.75 0 010 1.5h-.75A.75.75 0 016 9zm0 3a.75.75 0 01.75-.75h.75a.75.75 0 010 1.5h-.75A.75.75 0 016 12z"></path>
            </svg>
            <a href="#" class="small-box-footer toggle-stats link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>
</div>

<!-- Device Repair Statistics Row -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                        <h3 class="stat-value d-none">{{ $deviceRepairStats['total_repairs'] ?? 0 }}</h3>
                        <p>Total Services</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.25 4.533A9.707 9.707 0 006 3a9.735 9.735 0 00-3.25.555.75.75 0 00-.5.707v14.25a.75.75 0 001 .707A8.237 8.237 0 016 18.75c1.995 0 3.823.707 5.25 1.886V4.533zM12.75 20.636A8.214 8.214 0 0118 18.75c.966 0 1.89.166 2.75.47a.75.75 0 001-.708V4.262a.75.75 0 00-.5-.707A9.735 9.735 0 0018 3a9.707 9.707 0 00-5.25 1.533v16.103z"></path>
            </svg>
            <a href="{{ route('device-repairs.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                        <h3 class="stat-value d-none">{{ $deviceRepairStats['active_repairs'] ?? 0 }}</h3>
                        <p>Active Services</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l1.41-.513M5.106 17.785l1.15-.964m11.49-9.642l1.149-.964M7.501 19.795l.75-1.3m7.5-12.99l.75-1.3m-6.063 16.658l.26-1.477m2.605-14.772l.26-1.477m0 17.726l-.26-1.477M10.698 4.614l-.26-1.477M16.5 19.794l-.75-1.299M7.5 4.205L12 12m6.894 5.785l-1.149-.964M6.256 7.178l-1.15-.964m15.352 8.864l-1.41-.513M4.954 9.435l-1.41-.514M12.002 12l-3.75 6.495"></path>
            </svg>
            <a href="{{ route('device-repairs.index', ['status' => 'processing']) }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3 class="stat-value d-none">{{ $deviceRepairStats['delivered_today'] ?? 0 }}</h3>
                <p>Delivered Today</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <a href="{{ route('device-repairs.index', ['status' => 'delivered']) }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3 class="stat-value d-none">{{ $deviceRepairStats['processing_repairs'] ?? 0 }}</h3>
                <p>In Processing</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.414 48.414 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.589-1.202L18.75 4.97zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.589-1.202L5.25 4.97z"></path>
            </svg>
            <a href="{{ route('device-repairs.index', ['status' => 'processing']) }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sales Stat Card -->
    <div class="col-12 col-lg-8">
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="sales-states">
                    @include('manager.partials.sales_states')
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card stock-level-card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="bi bi-box-seam me-2"></i>Stock Levels Dashboard
                        </h3>
                        <span class="badge bg-primary">Live Data</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <!-- Low Stock Column -->
                            <div class="col-12 p-3 border-end">
                                <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                                    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Attention Needed</strong> - These games are running low on stock
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                
                                <h5 class="d-flex align-items-center text-warning mb-3">
                                    <i class="bi bi-speedometer2 me-2"></i>Low Stock Items
                                    <span class="badge bg-warning text-dark ms-2">{{ count($lowStockGames) }}</span>
                                </h5>
                                
                                @if($lowStockGames->isEmpty())
                                    <div class="alert alert-light mb-0">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i> All stock levels are healthy
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-warning">
                                                <tr>
                                                    <th width="70%">Game Title</th>
                                                    <th class="text-end">Stock</th>
                                                    <th class="text-end">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($lowStockGames as $index => $game)
                                                <tr class="{{ $index > 1 ? 'd-none low-stock-extra' : '' }}">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2" style="width: 24px; height: 24px; background-color: #e9ecef; border-radius: 4px;"></div>
                                                            {{ $game->title }}
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">{{ $game->total_stock }}</td>
                                                    <td class="text-end">
                                                        <span class="badge bg-warning text-dark">Low</span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        @if(count($lowStockGames) > 2)
                                            <div class="text-center mt-2">
                                                <button class="btn btn-outline-warning btn-sm" onclick="toggleStockRows('low')">Show all</button>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if(count($lowStockGames) < 1)
                            <div style="height: 100px;"></div>
                            @endif
                            <!-- High Stock Column -->
                            <div class="col-12 p-3">
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    <strong><i class="bi bi-check-circle-fill me-1"></i> Good Inventory</strong> - These items are well stocked
                                </div>
                                
                                <h5 class="d-flex align-items-center text-success mb-3">
                                    <i class="bi bi-check2-all me-2"></i>High Stock Items
                                    <span class="badge bg-success ms-2">{{ count($highStockGames) }}</span>
                                </h5>
                                
                                @if($highStockGames->isEmpty())
                                    <div class="alert alert-light mb-0">
                                        <i class="bi bi-info-circle-fill text-info me-1"></i> No high stock items to display
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-success">
                                                <tr>
                                                    <th width="70%">Game Title</th>
                                                    <th class="text-end">Stock</th>
                                                    <th class="text-end">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($highStockGames as $index => $game)
                                                <tr class="{{ $index > 1 ? 'd-none high-stock-extra' : '' }}">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2" style="width: 24px; height: 24px; background-color: #e9ecef; border-radius: 4px;"></div>
                                                            {{ $game->title }}
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">{{ $game->total_stock }}</td>
                                                    <td class="text-end">
                                                        <span class="badge bg-success">High</span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            </table>
                                            @if(count($highStockGames) > 2)
                                                <div class="text-center mt-2">
                                                    <button class="btn btn-outline-success btn-sm" onclick="toggleStockRows('high')">Show all</button>
                                                </div>
                                            @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-muted small">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-info-circle me-1"></i> Last updated: {{ now()->format('M j, Y g:i A') }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Activity Card -->
    <div class="col-12 col-lg-4">
        @include('manager.partials.history')
    </div>
</div>
<!-- System Health Monitoring -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-activity me-2"></i>System Health Monitor
                    </h5>
                    <span class="badge bg-white text-dark">Live Status</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Cache Driver Status -->
                    <div class="col-lg-3 col-md-6">
                        <div class="p-3 border rounded" style="background-color: #f8f9fa;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="text-muted small mb-1">Cache Driver</div>
                                    <h6 class="mb-1">{{ strtoupper($systemHealth['cache_driver']) }}</h6>
                                    <span class="badge 
                                        @if($systemHealth['cache_status'] === 'working') bg-success
                                        @elseif($systemHealth['cache_status'] === 'error') bg-danger
                                        @else bg-warning
                                        @endif">
                                        {{ $systemHealth['cache_status'] }}
                                    </span>
                                </div>
                                <div class="fs-3">
                                    @if($systemHealth['cache_status'] === 'working')
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @elseif($systemHealth['cache_status'] === 'error')
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    @else
                                        <i class="bi bi-question-circle-fill text-warning"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Redis Status -->
                    <div class="col-lg-3 col-md-6">
                        <div class="p-3 border rounded" style="background-color: #f8f9fa;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="text-muted small mb-1">Redis</div>
                                    @if($systemHealth['redis_status'] === 'connected')
                                        <h6 class="mb-1">{{ $systemHealth['redis_version'] ?? 'N/A' }}</h6>
                                        <small class="text-muted">Memory: {{ $systemHealth['redis_memory'] ?? 'N/A' }}</small>
                                    @else
                                        <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $systemHealth['redis_status'])) }}</h6>
                                    @endif
                                    <div class="mt-1">
                                        <span class="badge 
                                            @if($systemHealth['redis_status'] === 'connected') bg-success
                                            @elseif($systemHealth['redis_status'] === 'error') bg-danger
                                            @else bg-secondary
                                            @endif">
                                            {{ str_replace('_', ' ', $systemHealth['redis_status']) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="fs-3">
                                    <i class="bi bi-database-fill 
                                        @if($systemHealth['redis_status'] === 'connected') text-success
                                        @elseif($systemHealth['redis_status'] === 'error') text-danger
                                        @else text-secondary
                                        @endif"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Memcached Status -->
                    <div class="col-lg-3 col-md-6">
                        <div class="p-3 border rounded" style="background-color: #f8f9fa;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="text-muted small mb-1">Memcached</div>
                                    @if($systemHealth['memcached_status'] === 'connected')
                                        <h6 class="mb-1">{{ $systemHealth['memcached_version'] ?? 'N/A' }}</h6>
                                        <small class="text-muted">Memory: {{ $systemHealth['memcached_memory'] ?? 'N/A' }}</small>
                                    @else
                                        <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $systemHealth['memcached_status'])) }}</h6>
                                    @endif
                                    <div class="mt-1">
                                        <span class="badge 
                                            @if($systemHealth['memcached_status'] === 'connected') bg-success
                                            @elseif($systemHealth['memcached_status'] === 'error') bg-danger
                                            @else bg-secondary
                                            @endif">
                                            {{ str_replace('_', ' ', $systemHealth['memcached_status']) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="fs-3">
                                    <i class="bi bi-hdd-network-fill 
                                        @if($systemHealth['memcached_status'] === 'connected') text-success
                                        @elseif($systemHealth['memcached_status'] === 'error') text-danger
                                        @else text-secondary
                                        @endif"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database Status -->
                    <div class="col-lg-3 col-md-6">
                        <div class="p-3 border rounded" style="background-color: #f8f9fa;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="text-muted small mb-1">Database</div>
                                    @if($systemHealth['database_status'] === 'connected')
                                        @if(isset($systemHealth['database_connections']))
                                            <h6 class="mb-1">{{ $systemHealth['database_connections'] }} / {{ $systemHealth['database_max_connections'] ?? '?' }}</h6>
                                            <small class="text-muted">Active connections</small>
                                            @php
                                                $usage = isset($systemHealth['database_max_connections']) 
                                                    ? ($systemHealth['database_connections'] / $systemHealth['database_max_connections']) * 100 
                                                    : 0;
                                            @endphp
                                            <div class="mt-2">
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar 
                                                        @if($usage < 50) bg-success
                                                        @elseif($usage < 80) bg-warning
                                                        @else bg-danger
                                                        @endif" 
                                                        style="width: {{ $usage }}%"></div>
                                                </div>
                                            </div>
                                        @else
                                            <h6 class="mb-1">Connected</h6>
                                        @endif
                                    @else
                                        <h6 class="mb-1">{{ ucfirst($systemHealth['database_status']) }}</h6>
                                    @endif
                                    <div class="mt-1">
                                        <span class="badge 
                                            @if($systemHealth['database_status'] === 'connected') bg-success
                                            @else bg-danger
                                            @endif">
                                            {{ $systemHealth['database_status'] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="fs-3">
                                    <i class="bi bi-server 
                                        @if($systemHealth['database_status'] === 'connected') text-success
                                        @else text-danger
                                        @endif"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info Row -->
                    <div class="col-12">
                        <div class="alert alert-info mb-0 d-flex align-items-center">
                            <i class="bi bi-info-circle me-2 fs-5"></i>
                            <div class="flex-grow-1">
                                <strong>Session Driver:</strong> {{ strtoupper($systemHealth['session_driver']) }} &nbsp;|&nbsp; 
                                <strong>Queue Driver:</strong> {{ strtoupper($systemHealth['queue_driver']) }}
                                @if($systemHealth['queue_driver'] === 'sync')
                                    <span class="badge bg-warning text-dark ms-2">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Consider using database/redis for production
                                    </span>
                                @endif
                            </div>
                            <small class="text-muted">Last checked: {{ now()->format('H:i:s') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Selling Games -->
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Top Selling Games</h5>
            </div>
            <div class="card-body">
                @if($topSellingGames->isEmpty())
                    <div class="alert alert-info mb-0">No top selling games available.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="15%">Rank</th>
                                    <th>Game Title</th>
                                    <th class="text-end" width="25%">Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topSellingGames as $index => $gameData)
                                    <tr>
                                        <td><img width="40" height="40" src="{{ asset($gameData->ps4_image_url) }}" alt=""></td>
                                        <td>{{ $gameData->title ?? 'Unknown Game' }}</td>
                                        <td class="text-end">{{ number_format($gameData->total_sales) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card h-100 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Branches Monthly Revenue <small>(This Month)</small></h5>
            </div>
            <div class="card-body">
                @if($branchesWithOrders->isEmpty())
                    <div class="alert alert-info mb-0">No orders yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="15%">#</th>
                                    <th>Store</th>
                                    <th class="text-end" width="20%">Orders</th>
                                    <th class="text-end" width="25%">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branchesWithOrders as $index => $branch)
                                    <tr>
                                        <td><span class="badge bg-info rounded-pill">{{ $index + 1 }}</span></td>
                                        <td>{{ $branch->name }}</td>
                                        <td class="text-end">{{ $branch->orders_count }}</td>
                                        <td class="text-end fw-bold">{{ number_format($branch->orders_sum_price, 2) }} EGP</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Selling Branches -->
    <div class="col-xl-4 col-lg-12 col-md-12">
        <div class="card h-100 border-success">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Top Selling Branches</h5>
            </div>
            <div class="card-body">
                @if($topSellingStores->isEmpty())
                    <div class="alert alert-info mb-0">No orders yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="15%">#</th>
                                    <th>Store</th>
                                    <th class="text-end" width="20%">Orders</th>
                                    <th class="text-end" width="25%">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topSellingStores as $index => $topSellingStore)
                                    <tr>
                                        <td><span class="badge bg-success rounded-pill">{{ $index + 1 }}</span></td>
                                        <td>{{ $topSellingStore->name }}</td>
                                        <td class="text-end">{{ $topSellingStore->orders_count }}</td>
                                        <td class="text-end fw-bold">{{ number_format($topSellingStore->orders_sum_price, 2) }} EGP</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    function toggleStockRows(type) {
        const rows = document.querySelectorAll(`.${type}-stock-extra`);
        rows.forEach(row => row.classList.toggle('d-none'));

        const btn = event.currentTarget;
        if (btn.innerText === 'Show all') {
            btn.innerText = 'Hide';
        } else {
            btn.innerText = 'Show all';
        }
    }
</script>
<!-- JS to toggle visibility -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-stats').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const parent = this.closest('.small-box');
                const stat = parent.querySelector('.stat-value');
                stat.classList.toggle('d-none');
            });
        });
    });
    </script>
@endpush
