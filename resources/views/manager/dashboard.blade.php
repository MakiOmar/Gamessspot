@extends('layouts.admin')

@section('title', 'Manager Dashboard')

@section('content')
    <h1 class="text-center">Welcome, Manager</h1>
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Dashboard Overview</h5>
            <p class="card-text">This is where you can manage the system. Add more links and features as needed.</p>
            <a href="#" class="btn btn-primary">Manage Users</a>
            <a href="#" class="btn btn-secondary">Manage Games</a>
        </div>
    </div>
@endsection
