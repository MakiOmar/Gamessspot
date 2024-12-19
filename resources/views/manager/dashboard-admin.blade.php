<!--begin::Row-->
<div class="row"> <!--begin::Col-->
    <div class="col-lg-3 col-6"> <!--begin::Small Box Widget 1-->
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3>{{ number_format($accountsCost, 2) }}<sup class="fs-5">EGP</sup></h3>
                <p>Mails Assets</p>
            </div> <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"></path>
            </svg> <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i> </a>
        </div> <!--end::Small Box Widget 1-->
    </div> <!--end::Col-->
    <div class="col-lg-3 col-6"> <!--begin::Small Box Widget 2-->
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3>{{ number_format($totalCodeCost, 2) }}<sup class="fs-5">EGP</sup></h3>
                <p>Codes Assets</p>
            </div> <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>
            </svg> <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i> </a>
        </div> <!--end::Small Box Widget 2-->
    </div> <!--end::Col-->
    <div class="col-lg-3 col-6"> <!--begin::Small Box Widget 3-->
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3>{{ number_format($total, 2) }}<sup class="fs-5">EGP</sup></h3>
                <p>Total</p>
            </div> <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5 0v2.25H16a.75.75 0 000 1.5h2.25v2.25a.75.75 0 001.5 0v-2.25H22a.75.75 0 000-1.5h-2.25V7.5z"></path>
            </svg> <a href="#" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i> </a>
        </div> <!--end::Small Box Widget 3-->
    </div> <!--end::Col-->
    <div class="col-lg-3 col-6"> <!--begin::Small Box Widget 4-->
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3>65</h3>
                <p>Today orders</p>
            </div> <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path clip-rule="evenodd" fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z"></path>
                <path clip-rule="evenodd" fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z"></path>
            </svg> <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-link-45deg"></i> </a>
        </div> <!--end::Small Box Widget 4-->
    </div> <!--end::Col-->
</div> <!--end::Row-->
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