<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemOpsSetting;
use App\Models\User;
use App\Services\Backup\GoogleDriveBackupService;
use App\Services\RolePermissionService;
use App\Services\SystemBackupService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Off-site Google Drive copy of System Ops backups (Drive is faked; Google is never called).
 */
class GoogleDriveBackupTest extends TestCase
{
    use DatabaseTransactions;

    private string $dir;

    private const DRIVE_ENV = [
        'BACKUP_GOOGLE_DRIVE_ENABLED' => 'true',
        'GOOGLE_DRIVE_CLIENT_ID' => 'client-id',
        'GOOGLE_DRIVE_CLIENT_SECRET' => 'client-secret',
        'GOOGLE_DRIVE_REFRESH_TOKEN' => 'refresh-token',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['backup.google_drive' => ['enabled' => true, 'disk' => 'google', 'keep_days' => 14]]);
        Storage::fake('local');
        Storage::fake('google');
        $this->dir = (string) config('backup.backup.name');
        SystemOpsSetting::current()->update([
            'drive_last_at' => null,
            'drive_last_status' => null,
            'drive_last_message' => null,
        ]);
    }

    private function putArchive(string $disk, string $name, int $daysAgo): void
    {
        $path = $this->dir.'/'.$name;
        Storage::disk($disk)->put($path, 'zip:'.$name);
        touch(Storage::disk($disk)->path($path), now()->subDays($daysAgo)->getTimestamp());
    }

    /**
     * @param  array<string, string>  $env
     */
    private function loadBackupConfig(array $env): array
    {
        foreach ($env as $key => $value) {
            $_SERVER[$key] = $_ENV[$key] = $value;
        }
        try {
            return require config_path('backup.php');
        } finally {
            foreach (array_keys($env) as $key) {
                unset($_SERVER[$key], $_ENV[$key]);
            }
        }
    }

    private function fakeSuccessfulSpatieRun(): void
    {
        Artisan::shouldReceive('call')->once()->with('backup:run', ['--only-db' => true])->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('Backup completed!');
    }

    public function test_drive_is_enabled_only_with_flag_and_credentials(): void
    {
        $this->assertTrue($this->loadBackupConfig(self::DRIVE_ENV)['google_drive']['enabled']);
        $this->assertFalse($this->loadBackupConfig(['GOOGLE_DRIVE_CLIENT_ID' => ''] + self::DRIVE_ENV)['google_drive']['enabled']);
        $this->assertFalse($this->loadBackupConfig(['BACKUP_GOOGLE_DRIVE_ENABLED' => 'false'] + self::DRIVE_ENV)['google_drive']['enabled']);
        $this->assertNotContains('google', $this->loadBackupConfig(self::DRIVE_ENV)['backup']['destination']['disks']);
    }

    public function test_sync_uploads_newest_archive_and_prunes_old_drive_copies(): void
    {
        $this->putArchive('local', '2026-10-01-01-00-00.zip', 2);
        $this->putArchive('local', '2026-10-03-01-00-00.zip', 0);
        $this->putArchive('google', '2026-09-01-01-00-00.zip', 30);

        $result = app(GoogleDriveBackupService::class)->syncLatest('local', $this->dir);

        $this->assertSame(['file' => '2026-10-03-01-00-00.zip', 'pruned' => 1], $result);
        Storage::disk('google')->assertExists($this->dir.'/2026-10-03-01-00-00.zip');
        Storage::disk('google')->assertMissing($this->dir.'/2026-09-01-01-00-00.zip');
    }

    public function test_database_backup_copies_newest_archive_to_drive(): void
    {
        $this->putArchive('local', '2026-10-20-01-00-00.zip', 0);
        $this->fakeSuccessfulSpatieRun();

        $result = app(SystemBackupService::class)->createDatabaseBackup();

        $this->assertTrue($result['success']);
        Storage::disk('google')->assertExists($this->dir.'/2026-10-20-01-00-00.zip');
        $settings = SystemOpsSetting::current()->fresh();
        $this->assertSame('success', $settings->drive_last_status);
        $this->assertNotNull($settings->drive_last_at);
    }

    public function test_drive_failure_keeps_backup_successful_and_records_status(): void
    {
        $this->putArchive('local', '2026-10-20-01-00-00.zip', 0);
        $this->fakeSuccessfulSpatieRun();
        $drive = Mockery::mock(GoogleDriveBackupService::class)->makePartial();
        $drive->shouldReceive('syncLatest')->once()->andThrow(new RuntimeException('{"error":{"code":403,"message":"The user\'s Drive storage quota has been exceeded."}}'));
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        $result = app(SystemBackupService::class)->createDatabaseBackup();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Google Drive copy failed', $result['message']);
        Storage::disk('local')->assertExists($this->dir.'/2026-10-20-01-00-00.zip');
        $settings = SystemOpsSetting::current()->fresh();
        $this->assertSame('failed', $settings->drive_last_status);
        $this->assertSame("The user's Drive storage quota has been exceeded.", $settings->drive_last_message);
    }

    public function test_drive_check_command(): void
    {
        $this->artisan('backup:drive-check')->assertSuccessful();
        $this->assertSame([], Storage::disk('google')->allFiles());

        config(['backup.google_drive.enabled' => false]);
        $this->artisan('backup:drive-check')->assertFailed();
    }

    public function test_system_ops_page_shows_drive_status(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $matrix = app(RolePermissionService::class)->defaultMatrix();
        if (isset($matrix['admin'])) {
            $role->capabilities = $matrix['admin'];
            $role->save();
        }
        $admin = User::factory()->create();
        $admin->roles()->sync([$role->id]);
        SystemOpsSetting::current()->update([
            'drive_last_at' => now(),
            'drive_last_status' => 'failed',
            'drive_last_message' => 'Drive quota exceeded',
        ]);

        $this->actingAs($admin->fresh()->load('roles'), 'admin')
            ->withSession([config('system_ops.session_key') => true])
            ->get(route('manager.system-ops.index'))
            ->assertOk()
            ->assertSee('Google Drive copy:')
            ->assertSee('Drive quota exceeded')
            ->assertSee('Drive keeps copies for 14 day(s).');
    }
}
