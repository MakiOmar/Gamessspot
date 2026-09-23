@extends('layouts.admin')

@section('title', 'System Ops')

@section('content_header_title', 'System Ops')
@section('content_header_subtitle', 'Backups & Activity')

@section('content_body')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="mb-0 text-muted">
        Database backups and deletion / nullification activity. Requires cron:
        <code>php artisan schedule:run</code>
    </p>
    <form method="POST" action="{{ route('manager.system-ops.lock') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">Lock System Ops</button>
    </form>
</div>

<ul class="nav nav-tabs" id="systemOpsTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="backups-tab" data-toggle="tab" href="#backups" role="tab">Backups</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="activity-tab" data-toggle="tab" href="#activity" role="tab">Activity</a>
    </li>
</ul>

<div class="tab-content border border-top-0 p-3 bg-white">
    <div class="tab-pane fade show active" id="backups" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><strong>Automatic backup</strong></div>
                    <div class="card-body">
                        <form id="backupSettingsForm">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="autoBackupEnabled" name="auto_backup_enabled" value="1"
                                    {{ $settings->auto_backup_enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="autoBackupEnabled">Enable automatic backups</label>
                            </div>
                            <div class="form-group">
                                <label for="autoBackupInterval">Interval</label>
                                <select id="autoBackupInterval" name="auto_backup_interval" class="form-control">
                                    <option value="hourly" {{ $settings->auto_backup_interval === 'hourly' ? 'selected' : '' }}>Hourly</option>
                                    <option value="daily" {{ $settings->auto_backup_interval === 'daily' ? 'selected' : '' }}>Daily (default)</option>
                                    <option value="weekly" {{ $settings->auto_backup_interval === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                </select>
                            </div>
                            <p class="small text-muted mb-2">
                                Last backup:
                                {{ $settings->last_backup_at ? $settings->last_backup_at->toDateTimeString() : 'never' }}
                            </p>
                            <button type="submit" class="btn btn-primary btn-sm">Save schedule</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Database backups</h5>
                    <button type="button" class="btn btn-success btn-sm" id="createBackupBtn">Create backup now</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="backupsTable">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th style="width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($backups as $backup)
                                <tr data-path="{{ $backup['path'] }}">
                                    <td><code>{{ $backup['name'] }}</code></td>
                                    <td>{{ $backup['size_human'] }}</td>
                                    <td>{{ $backup['date'] }}</td>
                                    <td>
                                        <a class="btn btn-xs btn-outline-primary"
                                           href="{{ route('manager.system-ops.backups.download', ['path' => $backup['path']]) }}">Download</a>
                                        <button type="button" class="btn btn-xs btn-outline-danger delete-backup">Delete</button>
                                        <button type="button" class="btn btn-xs btn-warning restore-backup">Restore</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">No backups yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="activity" role="tabpanel">
        <form method="GET" action="{{ route('manager.system-ops.index') }}" class="form-row align-items-end mb-3" id="activityFilters">
            <input type="hidden" name="tab" value="activity">
            <div class="form-group col-md-3">
                <label>Action</label>
                <select name="action" class="form-control form-control-sm">
                    <option value="">All</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <label>From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="form-group col-md-2">
                <label>To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <div class="form-group col-md-3">
                <label>Actor</label>
                <input type="text" name="actor" value="{{ request('actor') }}" class="form-control form-control-sm" placeholder="Name or ID">
            </div>
            <div class="form-group col-md-2">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('manager.system-ops.index') }}" class="btn btn-sm btn-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Actor</th>
                        <th>Meta</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $row)
                        <tr>
                            <td>{{ $row->created_at?->toDateTimeString() }}</td>
                            <td><code>{{ $row->action }}</code></td>
                            <td>
                                {{ $row->subject_label }}
                                @if ($row->subject_id)
                                    <small class="text-muted">#{{ $row->subject_id }}</small>
                                @endif
                            </td>
                            <td>{{ $row->actor_name }}</td>
                            <td><small>{{ $row->meta ? json_encode($row->meta) : '—' }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">No activity logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $activities->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
@endsection

@push('js')
<script>
jQuery(function ($) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    @if (request('tab') === 'activity' || request()->hasAny(['action', 'from', 'to', 'actor']))
        $('#activity-tab').tab('show');
    @endif

    function toast(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 2500 });
    }

    $('#backupSettingsForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: @json(route('manager.system-ops.backup-settings')),
            method: 'POST',
            data: {
                auto_backup_enabled: $('#autoBackupEnabled').is(':checked') ? 1 : 0,
                auto_backup_interval: $('#autoBackupInterval').val()
            },
            success: function (res) {
                toast('success', res.message || 'Saved');
            },
            error: function (xhr) {
                toast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Save failed');
            }
        });
    });

    $('#createBackupBtn').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        Swal.fire({ title: 'Creating backup…', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        $.ajax({
            url: @json(route('manager.system-ops.backups.create')),
            method: 'POST',
            success: function (res) {
                Swal.close();
                toast('success', res.message || 'Backup created');
                location.reload();
            },
            error: function (xhr) {
                Swal.close();
                toast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Backup failed');
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.delete-backup', function () {
        var path = $(this).closest('tr').data('path');
        Swal.fire({
            title: 'Delete this backup?',
            text: path,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: @json(route('manager.system-ops.backups.delete')),
                method: 'POST',
                data: { _method: 'DELETE', path: path },
                success: function (res) {
                    toast('success', res.message || 'Deleted');
                    location.reload();
                },
                error: function (xhr) {
                    toast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed');
                }
            });
        });
    });

    $(document).on('click', '.restore-backup', function () {
        var path = $(this).closest('tr').data('path');
        Swal.fire({
            title: 'Restore this backup?',
            html: 'This <strong>overwrites the live database</strong>. Type <code>RESTORE</code> to confirm.',
            input: 'text',
            inputPlaceholder: 'RESTORE',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Continue'
        }).then(function (step1) {
            if (!step1.isConfirmed || step1.value !== 'RESTORE') {
                if (step1.isConfirmed) toast('error', 'Confirmation text mismatch');
                return;
            }
            Swal.fire({
                title: 'Final confirmation',
                text: 'Really restore and overwrite the database?',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Yes, restore now'
            }).then(function (step2) {
                if (!step2.isConfirmed) return;
                Swal.fire({ title: 'Restoring…', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
                $.ajax({
                    url: @json(route('manager.system-ops.backups.restore')),
                    method: 'POST',
                    data: { path: path, confirm: 1 },
                    success: function (res) {
                        Swal.close();
                        toast('success', res.message || 'Restored');
                    },
                    error: function (xhr) {
                        Swal.close();
                        toast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Restore failed');
                    }
                });
            });
        });
    });
});
</script>
@endpush
