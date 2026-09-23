<?php

namespace App\Http\Controllers;

use App\Models\SystemActivityLog;
use App\Models\SystemOpsSetting;
use App\Services\SystemActivityLogger;
use App\Services\SystemBackupService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class SystemOpsController extends Controller
{
    public function __construct(
        protected SystemBackupService $backupService,
        protected SystemActivityLogger $activityLogger
    ) {
    }

    public function showUnlock()
    {
        return view('manager.system_ops.unlock');
    }

    public function unlock(Request $request)
    {
        $request->validate(array(
            'password' => 'required|string',
        ));

        $configured = (string) config('system_ops.password', '');
        if ($configured === '') {
            return back()->withErrors(array(
                'password' => 'SYSTEM_OPS_PASSWORD is not configured in .env.',
            ));
        }

        $key = 'system-ops-unlock:' . ($request->user('admin')?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, (int) config('system_ops.unlock_rate_limit', 5))) {
            return back()->withErrors(array(
                'password' => 'Too many unlock attempts. Try again shortly.',
            ));
        }

        if (! hash_equals($configured, $request->input('password'))) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(array(
                'password' => 'Invalid System Ops password.',
            ));
        }

        RateLimiter::clear($key);
        $request->session()->put(config('system_ops.session_key', 'system_ops_unlocked'), true);

        return redirect()->route('manager.system-ops.index');
    }

    public function lock(Request $request)
    {
        $request->session()->forget(config('system_ops.session_key', 'system_ops_unlocked'));

        return redirect()->route('manager.system-ops.unlock')
            ->with('status', 'System Ops locked.');
    }

    public function index(Request $request)
    {
        $settings = SystemOpsSetting::current();
        $backups = $this->paginateBackups($request);

        $activityQuery = SystemActivityLog::query()->orderByDesc('id');

        if ($request->filled('action')) {
            $activityQuery->where('action', $request->input('action'));
        }
        if ($request->filled('from')) {
            $activityQuery->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $activityQuery->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->filled('actor')) {
            $actor = $request->input('actor');
            $activityQuery->where(function ($q) use ($actor) {
                $q->where('actor_name', 'like', '%' . $actor . '%')
                    ->orWhere('actor_id', $actor);
            });
        }

        $activities = $activityQuery->paginate(25, ['*'], 'activity_page')->appends($request->query());
        $actions = SystemActivityLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('manager.system_ops.index', compact('settings', 'backups', 'activities', 'actions'));
    }

    /**
     * Paginate filesystem backup list (not an Eloquent query).
     */
    protected function paginateBackups(Request $request): LengthAwarePaginator
    {
        $all = $this->backupService->listBackups();
        $perPage = 15;
        $page = max(1, (int) $request->input('backup_page', 1));
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);

        return (new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            array(
                'path' => $request->url(),
                'pageName' => 'backup_page',
            )
        ))->appends($request->except('backup_page'));
    }

    public function updateBackupSettings(Request $request)
    {
        $validated = $request->validate(array(
            'auto_backup_enabled' => 'nullable|boolean',
            'auto_backup_interval' => array('required', Rule::in(array('hourly', 'daily', 'weekly'))),
        ));

        $settings = SystemOpsSetting::current();
        $settings->auto_backup_enabled = $request->boolean('auto_backup_enabled');
        $settings->auto_backup_interval = $validated['auto_backup_interval'];
        $settings->save();

        return response()->json(array(
            'success' => true,
            'message' => 'Backup schedule settings saved.',
            'settings' => $settings,
        ));
    }

    public function createBackup()
    {
        $result = $this->backupService->createDatabaseBackup();

        if ($result['success']) {
            $this->activityLogger->log('backup.created', 'backup', null, 'Database backup', array(
                'manual' => true,
            ));
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function downloadBackup(Request $request)
    {
        $path = $request->query('path');
        $absolute = $this->backupService->absolutePath((string) $path);
        if ($absolute === null) {
            abort(404, 'Backup not found.');
        }

        return response()->download($absolute, basename($absolute));
    }

    public function deleteBackup(Request $request)
    {
        $validated = $request->validate(array(
            'path' => 'required|string',
        ));

        $ok = $this->backupService->deleteBackup($validated['path']);
        if (! $ok) {
            return response()->json(array('success' => false, 'message' => 'Unable to delete backup.'), 404);
        }

        $this->activityLogger->log('backup.deleted', 'backup', null, basename($validated['path']), array(
            'path' => $validated['path'],
        ));

        return response()->json(array('success' => true, 'message' => 'Backup deleted.'));
    }

    public function restoreBackup(Request $request)
    {
        $validated = $request->validate(array(
            'path' => 'required|string',
            'confirm' => 'required|accepted',
        ));

        $result = $this->backupService->restoreFromBackup($validated['path']);

        if ($result['success']) {
            $this->activityLogger->log('backup.restored', 'backup', null, basename($validated['path']), array(
                'path' => $validated['path'],
            ));
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
