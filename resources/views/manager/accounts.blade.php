@extends('layouts.admin')

@section('title', 'Manager - Accounts')

@section('content')
<div class="container mt-5">
    <h1 class="text-center mb-4">Accounts Management</h1>

    <!-- Scrollable table container -->
    <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
        <table class="table table-striped table-bordered" style="min-width: 1200px;">
            <thead>
                <tr role="row">
                    <th style="width: 57px;">ID</th>
                    <th style="width: 265px;">Mail</th>
                    <th style="width: 170px;">Game Name</th>
                    <th style="width: 52px;">Region</th> <!-- Region will display an emoji -->
                    <th style="width: 111px;">Offline (PS4)</th>
                    <th style="width: 104px;">Primary (PS4)</th>
                    <th style="width: 114px;">Secondary (PS4)</th>
                    <th style="width: 99px;">Offline (PS5)</th>
                    <th style="width: 104px;">Primary (PS5)</th>
                    <th style="width: 114px;">Secondary (PS5)</th>
                    <th style="width: 99px;">Cost</th>
                    <th style="width: 120px;">Password</th>
                </tr>
            </thead>
            <tbody>
                <!-- Region with Emoji -->
                @php
                    $regionEmojis = [
                        'US' => '🇺🇸',
                        'UK' => '🇬🇧',
                        'JP' => '🇯🇵',
                        'EU' => '🇪🇺',
                        'CA' => '🇨🇦',
                        // Add more region codes and their corresponding emojis here
                    ];
                @endphp
                @foreach($accounts as $account)
                <tr>
                    <td>{{ $account->id }}</td>
                    <td>{{ $account->mail }}</td>
                    <td>{{ $account->game->title }}</td>
                    <td>{{ $regionEmojis[$account->region] ?? $account->region }}</td>
                    <td>{{ $account->ps4_offline_stock }}</td>
                    <td>{{ $account->ps4_primary_stock }}</td>
                    <td>{{ $account->ps4_secondary_stock }}</td>
                    <td>{{ $account->ps5_offline_stock }}</td>
                    <td>{{ $account->ps5_primary_stock }}</td>
                    <td>{{ $account->ps5_secondary_stock }}</td>
                    <td></td>
                    <td></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination links (if needed) -->
    <div class="d-flex justify-content-center mt-4">
        {{ $accounts->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
@endsection
