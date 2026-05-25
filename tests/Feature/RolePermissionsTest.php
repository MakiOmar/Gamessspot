<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }

    public function test_account_manager_can_manage_accounts_and_view_reports(): void
    {
        $user = $this->createUserWithRole('account manager');

        $this->assertTrue(Gate::forUser($user)->allows('manage-accounts'));
        $this->assertTrue(Gate::forUser($user)->allows('view-reports'));
        $this->assertTrue(Gate::forUser($user)->allows('manage-sell-log'));
    }

    public function test_account_manager_can_access_accounts_export_route(): void
    {
        $user = $this->createUserWithRole('account manager');

        $response = $this->actingAs($user, 'admin')->get('/manager/accounts/export');

        $response->assertStatus(200);
    }

    public function test_account_manager_can_access_report_routes(): void
    {
        $user = $this->createUserWithRole('account manager');

        $this->actingAs($user, 'admin')
            ->get('/manager/orders/has-problem')
            ->assertStatus(200);
    }

    public function test_call_center_can_access_sell_log_but_not_manage_it(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->assertTrue(Gate::forUser($user)->allows('view-sell-log'));
        $this->assertFalse(Gate::forUser($user)->allows('manage-sell-log'));
        $this->assertFalse(Gate::forUser($user)->allows('manage-accounts'));
        $this->assertFalse(Gate::forUser($user)->allows('view-reports'));
    }

    public function test_call_center_can_access_orders_page(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->actingAs($user, 'admin')
            ->get('/manager/orders')
            ->assertStatus(200);
    }

    public function test_call_center_cannot_export_orders(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->actingAs($user, 'admin')
            ->get('/manager/orders/export')
            ->assertStatus(403);
    }

    public function test_call_center_cannot_access_accounts(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->actingAs($user, 'admin')
            ->get('/manager/accounts')
            ->assertStatus(403);
    }

    public function test_call_center_cannot_access_report_lists(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->actingAs($user, 'admin')
            ->get('/manager/orders/has-problem')
            ->assertStatus(403);
    }
}
