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
                <path d="M2.25 2.25a.75.75 0 000 1.5h1.386..."></path>
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
                <path d="M18.375 2.25c-1.035 0-1.875..."></path>
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
                <path d="M6.25 6.375a4.125 4.125..."></path>
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
                <path clip-rule="evenodd" fill-rule="evenodd" d="M2.25 13.5a8.25..."></path>
            </svg>
            <a href="#" class="small-box-footer toggle-stats link-light link-underline-opacity-0 link-underline-opacity-50-hover">
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
                                        <td><img width="40" height="40" src="{{ asset($gameData->game->ps4_image_url) }}" alt=""></td>
                                        <td>{{ $gameData->game->title ?? 'Unknown Game' }}</td>
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
