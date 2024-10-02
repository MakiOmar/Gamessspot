@extends('layouts.admin')

@section('title', 'Manager - Games')

@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">Games Management</h1>

        <!-- Scrollable table container -->
        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            <table class="table table-striped table-bordered" style="min-width: 800px;">
                <thead>
                    <tr>
                        <th style="width: 21px;">id</th>
                        <th style="width: 388px;">Game Name</th>
                        <th style="width: 286px;">Code</th>
                        <th style="width: 98px;">Reports</th>
                        <th style="width: 73px;">Edit</th>
                        <th style="width: 106px;">ps4 Poster Edit</th>
                        <th style="width: 106px;">ps5 Poster Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($games as $game)
                        <tr>
                            <td>{{ $game->id }}</td>
                            <td>{{ $game->title }}</td>
                            <td>{{ $game->code }}</td>
                            <td><a href="#">View Reports</a></td>
                            <td><a href="#">Edit</a></td>
                            <td><a href="#">Edit PS4 Poster</a></td>
                            <td><a href="#">Edit PS5 Poster</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination links -->
        <div class="d-flex justify-content-center mt-4">
            {{ $games->links() }}
        </div>
    </div>
@endsection
