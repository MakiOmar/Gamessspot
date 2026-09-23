@extends('layouts.admin')

@section('title', 'System Ops Unlock')

@section('content_header_title', 'System Ops')
@section('content_header_subtitle', 'Unlock')

@section('content_body')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Enter System Ops password</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Admin access alone is not enough. Enter the password from
                    <code>SYSTEM_OPS_PASSWORD</code> in <code>.env</code>.
                </p>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('manager.system-ops.unlock.submit') }}">
                    @csrf
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-warning">Unlock</button>
                    <a href="{{ route('manager.dashboard') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
