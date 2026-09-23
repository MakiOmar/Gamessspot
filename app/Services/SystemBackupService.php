<?php

namespace App\Services;

use App\Models\SystemOpsSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\Process\Process;
use ZipArchive;

class SystemBackupService
{
    /**
     * Create a database-only backup via Spatie.
     */
    public function createDatabaseBackup(): array
    {
        $exitCode = Artisan::call('backup:run', array('--only-db' => true));
        $output = Artisan::output();

        if ($exitCode !== 0) {
            Log::error('System Ops backup failed', array('output' => $output, 'exit' => $exitCode));

            return array(
                'success' => false,
                'message' => 'Backup failed. Check logs for details.',
                'output' => $output,
            );
        }

        SystemOpsSetting::current()->update(array('last_backup_at' => now()));

        return array(
            'success' => true,
            'message' => 'Database backup created successfully.',
            'output' => $output,
        );
    }

    /**
     * List backups on the configured local disk.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBackups(): array
    {
        $destination = $this->destination();
        $items = array();

        foreach ($destination->backups() as $backup) {
            if (! $backup->exists()) {
                continue;
            }

            $items[] = array(
                'path' => $backup->path(),
                'name' => basename($backup->path()),
                'size' => $backup->sizeInBytes(),
                'size_human' => $this->humanBytes($backup->sizeInBytes()),
                'date' => $backup->date()->toDateTimeString(),
                'timestamp' => $backup->date()->timestamp,
            );
        }

        usort($items, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        return $items;
    }

    /**
     * Absolute path for a backup relative path (local disk only).
     */
    public function absolutePath(string $relativePath): ?string
    {
        $relativePath = $this->sanitizePath($relativePath);
        if ($relativePath === null) {
            return null;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($relativePath)) {
            return null;
        }

        return storage_path('app/' . $relativePath);
    }

    public function deleteBackup(string $relativePath): bool
    {
        $relativePath = $this->sanitizePath($relativePath);
        if ($relativePath === null) {
            return false;
        }

        return Storage::disk('local')->delete($relativePath);
    }

    /**
     * Restore DB from a Spatie backup zip (contains .sql dump).
     */
    public function restoreFromBackup(string $relativePath): array
    {
        $absolute = $this->absolutePath($relativePath);
        if ($absolute === null) {
            return array('success' => false, 'message' => 'Backup file not found.');
        }

        $tempDir = storage_path('app/backup-temp/restore-' . Str::random(8));
        File::ensureDirectoryExists($tempDir);

        try {
            $zip = new ZipArchive();
            $opened = $zip->open($absolute);
            if ($opened !== true) {
                return array('success' => false, 'message' => 'Unable to open backup archive.');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            $sqlFiles = File::allFiles($tempDir);
            $sqlPath = null;
            foreach ($sqlFiles as $file) {
                if (strtolower($file->getExtension()) === 'sql') {
                    $sqlPath = $file->getPathname();
                    break;
                }
            }

            if ($sqlPath === null) {
                return array('success' => false, 'message' => 'No SQL dump found inside the backup.');
            }

            $imported = $this->importSqlFile($sqlPath);
            if (! $imported['success']) {
                return $imported;
            }

            return array('success' => true, 'message' => 'Database restored from backup.');
        } catch (\Throwable $e) {
            Log::error('System Ops restore failed', array('error' => $e->getMessage()));

            return array('success' => false, 'message' => 'Restore failed: ' . $e->getMessage());
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * @return array{success:bool,message:string}
     */
    protected function importSqlFile(string $sqlPath): array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? '') !== 'mysql') {
            return array('success' => false, 'message' => 'Restore currently supports MySQL only.');
        }

        $mysql = $this->findMysqlBinary();
        if ($mysql) {
            $process = new Process(array(
                $mysql,
                '--host=' . ($config['host'] ?? '127.0.0.1'),
                '--port=' . ($config['port'] ?? '3306'),
                '--user=' . ($config['username'] ?? 'root'),
                '--database=' . ($config['database'] ?? ''),
                '--default-character-set=utf8mb4',
            ));
            $process->setInput(File::get($sqlPath));
            $process->setTimeout(600);
            $env = array();
            if (! empty($config['password'])) {
                $env['MYSQL_PWD'] = $config['password'];
            }
            $process->run(null, $env);

            if (! $process->isSuccessful()) {
                return array(
                    'success' => false,
                    'message' => 'mysql import failed: ' . trim($process->getErrorOutput() ?: $process->getOutput()),
                );
            }

            return array('success' => true, 'message' => 'Imported via mysql client.');
        }

        // Fallback: execute statements (less ideal for huge dumps)
        $sql = File::get($sqlPath);
        \Illuminate\Support\Facades\DB::unprepared($sql);

        return array('success' => true, 'message' => 'Imported via PHP.');
    }

    protected function findMysqlBinary(): ?string
    {
        $candidates = array(
            'mysql',
            'C:\\wamp64\\bin\\mysql\\mysql8.3.0\\bin\\mysql.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.2.0\\bin\\mysql.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.1.0\\bin\\mysql.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysql.exe',
        );

        foreach ($candidates as $bin) {
            if ($bin === 'mysql') {
                $process = Process::fromShellCommandline(PHP_OS_FAMILY === 'Windows' ? 'where mysql' : 'which mysql');
                $process->run();
                if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
                    return 'mysql';
                }
                continue;
            }

            if (is_file($bin)) {
                return $bin;
            }
        }

        // Scan WAMP mysql folders
        $wampMysql = 'C:\\wamp64\\bin\\mysql';
        if (is_dir($wampMysql)) {
            foreach (glob($wampMysql . '\\mysql*\\bin\\mysql.exe') ?: array() as $path) {
                return $path;
            }
        }

        return null;
    }

    protected function destination(): BackupDestination
    {
        $name = config('backup.backup.name');

        return BackupDestination::create('local', $name);
    }

    protected function sanitizePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        if (str_contains($path, '..')) {
            return null;
        }

        $backupName = trim((string) config('backup.backup.name'), '/');
        if ($backupName !== '' && ! str_starts_with($path, $backupName . '/')) {
            return null;
        }

        return $path;
    }

    protected function humanBytes(float $bytes): string
    {
        $units = array('B', 'KB', 'MB', 'GB');
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
