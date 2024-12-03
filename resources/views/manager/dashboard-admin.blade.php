<h4>System Assets</h4>
<table class="table table-dark">
    <thead>
        <tr>
        <th scope="col">Mails Assets</th>
        <th scope="col">Codes Assets</th>
        <th scope="col">Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
        <td>{{ number_format($accountsCost, 2) }} EGP</td>
        <td>{{ number_format($totalCodeCost, 2) }} EGP</td>
        <td>{{ number_format($total, 2) }} EGP</td>
        </tr>
    </tbody>
    </table>
<div class="row mt-4">
    <!-- Sales Stat Card -->
    <div class="sales-states col-lg-4">
        @include('manager.partials.sales_states')
    </div>
    <!-- Activity Card -->
    <div class="col-lg-4">
        @include('manager.partials.history')
    </div>
</div>

<div class="row mt-4">
    <!-- Weekly Income -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body">
                <h5>750$</h5>
                <p>Weekly Income</p>
            </div>
        </div>
    </div>

    <!-- New Users -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body">
                <h5>+6.5K</h5>
                <p>New Users</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5>Top selling games</h5>
                @if($topSellingGames->isEmpty())
                    <p>No top selling games available.</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Game Title</th>
                                <th>Total Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topSellingGames as $index => $gameData)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $gameData->game->title ?? 'Unknown Game' }}</td>
                                    <td>{{ $gameData->total_sales }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5>Top buyers</h5>
                @if($topBuyers->isEmpty())
                    <p>No buyers found.</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Buyer Phone</th>
                                <th>Buyer name</th>
                                <th>Total Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topBuyers as $index => $buyer)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $buyer->buyer_phone }}</td>
                                    <td>{{ $buyer->buyer_name }}</td>
                                    <td>{{ $buyer->total_orders }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="container">
        <h2>Stock Levels for Games</h2>
    
        <h3>Low Stock (PS4/PS5 Primary and Offline)</h3>
        @if($lowStockGames->isEmpty())
            <p>No games with low stock.</p>
        @else
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Game Title</th>
                        <th>Total Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockGames as $game)
                        <tr>
                            <td>{{ $game->title }}</td>
                            <td>{{ $game->total_stock }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    
        <h3>High Stock (PS4/PS5 Secondary)</h3>
        @if($highStockGames->isEmpty())
            <p>No games with high stock.</p>
        @else
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Game Title</th>
                        <th>Total Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($highStockGames as $game)
                        <tr>
                            <td>{{ $game->title }}</td>
                            <td>{{ $game->total_stock }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>