<?php

namespace App\Console\Commands;

use App\Models\SystemOpsSetting;
use App\Services\SystemActivityLogger;
use App\Services\SystemBackupService;
use Illuminate\Console\Command;

class RunScheduledSystemBackupCommand extends Command
{
    protected $signature = 'system-ops:run-scheduled-backup';

    protected $description = 'Run automatic database backup when System Ops schedule is due';

    public function handle(SystemBackupService $backupService, SystemActivityLogger $activityLogger): int
    {
        $settings = SystemOpsSetting::current();

        if (! $settings->auto_backup_enabled) {
            $this->info('Auto backup disabled.');

            return self::SUCCESS;
        }

        if (! $this->isDue($settings)) {
            $this->info('Auto backup not due yet.');

            return self::SUCCESS;
        }

        $result = $backupService->createDatabaseBackup();
        if (! $result['success']) {
            $this->error($result['message'] ?? 'Backup failed');

            return self::FAILURE;
        }

        $activityLogger->log('backup.created', 'backup', null, 'Scheduled database backup', array(
            'scheduled' => true,
            'interval' => $settings->auto_backup_interval,
        ));

        $this->info('Scheduled database backup completed.');

        return self::SUCCESS;
    }

    protected function isDue(SystemOpsSetting $settings): bool
    {
        if ($settings->last_backup_at === null) {
            return true;
        }

        $last = $settings->last_backup_at->copy();

        return match ($settings->auto_backup_interval) {
            'hourly' => $last->lte(now()->subHour()),
            'weekly' => $last->lte(now()->subWeek()),
            default => $last->lte(now()->subDay()),
        };
    }
}
