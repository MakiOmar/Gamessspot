<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Order;
use App\Models\Role;
use App\Models\SystemActivityLog;
use App\Models\SystemOpsSetting;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SystemOpsTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $matrix = app(RolePermissionService::class)->defaultMatrix();
        if (isset($matrix['admin'])) {
            $role->capabilities = $matrix['admin'];
            $role->save();
        }

        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }

    public function test_guest_cannot_access_system_ops_unlock(): void
    {
        $this->get(route('manager.system-ops.unlock'))
            ->assertRedirect();
    }

    public function test_admin_without_unlock_is_redirected_from_index(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('manager.system-ops.index'))
            ->assertRedirect(route('manager.system-ops.unlock'));
    }

    public function test_admin_can_unlock_with_correct_password(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->post(route('manager.system-ops.unlock.submit'), [
                'password' => 'ops-secret-test',
            ])
            ->assertRedirect(route('manager.system-ops.index'));

        $this->actingAs($admin, 'admin')
            ->withSession([config('system_ops.session_key') => true])
            ->get(route('manager.system-ops.index'))
            ->assertOk();
    }

    public function test_wrong_ops_password_is_rejected(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->from(route('manager.system-ops.unlock'))
            ->post(route('manager.system-ops.unlock.submit'), [
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('manager.system-ops.unlock'))
            ->assertSessionHasErrors('password');
    }

    public function test_deleting_account_logs_account_and_order_account_id_nulled(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'admin');

        $game = Game::factory()->create();
        $account = Account::create(array_merge([
            'mail' => 'ops_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $game->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
            'is_full' => false,
        ], Account::resolveInitialStocks(false, false)));

        $order = Order::factory()->create([
            'account_id' => $account->id,
            'seller_id' => $admin->id,
            'sold_item' => 'primary',
            'buyer_phone' => '01234567890',
        ]);

        $account->delete();

        $this->assertDatabaseHas('system_activity_logs', [
            'action' => 'account.deleted',
            'subject_id' => $account->id,
        ]);

        $this->assertTrue(
            SystemActivityLog::query()
                ->where('action', 'order.account_id_nulled')
                ->where('subject_id', $order->id)
                ->exists()
        );
    }

    public function test_order_eloquent_null_account_id_is_logged(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'admin');

        $game = Game::factory()->create();
        $account = Account::create(array_merge([
            'mail' => 'ops2_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $game->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '2222',
            'is_full' => false,
        ], Account::resolveInitialStocks(false, false)));

        $order = Order::factory()->create([
            'account_id' => $account->id,
            'seller_id' => $admin->id,
            'sold_item' => 'primary',
            'buyer_phone' => '01234567891',
        ]);

        $order->account_id = null;
        $order->save();

        $this->assertTrue(
            SystemActivityLog::query()
                ->where('action', 'order.account_id_nulled')
                ->where('subject_id', $order->id)
                ->get()
                ->contains(function ($log) {
                    return ($log->meta['cause'] ?? null) === 'eloquent_update';
                })
        );
    }

    public function test_scheduled_backup_command_skips_when_disabled(): void
    {
        $settings = SystemOpsSetting::current();
        $settings->update([
            'auto_backup_enabled' => false,
            'last_backup_at' => null,
        ]);

        $this->artisan('system-ops:run-scheduled-backup')
            ->expectsOutput('Auto backup disabled.')
            ->assertSuccessful();
    }

    public function test_scheduled_backup_command_runs_when_due(): void
    {
        $settings = SystemOpsSetting::current();
        $settings->update([
            'auto_backup_enabled' => true,
            'auto_backup_interval' => 'daily',
            'last_backup_at' => now()->subDays(2),
        ]);

        $this->mock(\App\Services\SystemBackupService::class, function ($mock) {
            $mock->shouldReceive('createDatabaseBackup')
                ->once()
                ->andReturn(['success' => true, 'message' => 'ok']);
        });

        $this->artisan('system-ops:run-scheduled-backup')
            ->expectsOutput('Scheduled database backup completed.')
            ->assertSuccessful();

        $this->assertTrue(
            SystemActivityLog::query()->where('action', 'backup.created')->exists()
        );
    }
}
