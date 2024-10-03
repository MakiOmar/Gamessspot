@extends('layouts.admin')

@section('title', 'Manager Dashboard')

@section('content')
    <h1 class="text-center">Welcome, Manager</h1>
    <div class="container-fluid">
        <div class="row mt-4">
            <!-- Sales Stat Card -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        Sales Stat
                        <button class="btn btn-sm btn-light float-right">Export</button>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Activity Card -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        My Activity
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li>08:42 Outline meeting</li>
                            <li>14:37 Deposit $700</li>
                            <!-- More activity items... -->
                        </ul>
                    </div>
                </div>
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
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5>PS4 Games</h5>
                        <img src="ps4.png" class="img-fluid" alt="PS4">
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5>PS5 Games</h5>
                        <img src="ps5.png" class="img-fluid" alt="PS5">
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Top Buyers -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Top Buyers</h5>
                        <ul class="list-unstyled">
                            <li>Ricky Hunt - $200,000</li>
                            <!-- Add more buyers... -->
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Low Stock Items</h5>
                        <ul class="list-unstyled">
                            <li>Item 1 - Due in 2 days</li>
                            <!-- Add more items... -->
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Agents Stats -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Agents Stats</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Earnings</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Brad Simmons</td>
                                    <td>$8,000,000</td>
                                    <td>$520</td>
                                </tr>
                                <!-- Add more agents... -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
