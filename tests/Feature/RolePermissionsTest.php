<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    protected RolePermissionService $rolePermissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rolePermissionService = app(RolePermissionService::class);
    }

    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(array('name' => $roleName));
        $this->applyDefaultCapabilities($role);

        $user = User::factory()->create();
        $user->roles()->sync(array($role->id));

        return $user->fresh()->load('roles');
    }

    protected function applyDefaultCapabilities(Role $role): void
    {
        $matrix = $this->rolePermissionService->defaultMatrix();

        if (isset($matrix[$role->name])) {
            $role->capabilities = $matrix[$role->name];
            $role->save();
        }
    }

    public function test_account_manager_can_manage_accounts_and_view_reports(): void
    {
        $user = $this->createUserWithRole('account manager');

        $this->assertTrue(Gate::forUser($user)->allows('manage-accounts'));
        $this->assertTrue(Gate::forUser($user)->allows('view-reports'));
        $this->assertTrue(Gate::forUser($user)->allows('manage-sell-log'));
        $this->assertTrue(Gate::forUser($user)->allows('undo-orders'));
    }

    public function test_account_manager_can_access_accounts_export_route(): void
    {
        $user = $this->createUserWithRole('account manager');

        $response = $this->actingAs($user, 'admin')->get(route('manager.accounts.export'));

        $response->assertStatus(200);
    }

    public function test_account_manager_can_access_report_routes(): void
    {
        $user = $this->createUserWithRole('account manager');

        $this->actingAs($user, 'admin')
            ->get(route('manager.orders.has_problem'))
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
            ->get(route('manager.orders'))
            ->assertStatus(200);
    }

    public function test_call_center_cannot_export_orders(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->actingAs($user, 'admin')
            ->get(route('manager.orders.export'))
            ->assertStatus(403);
    }

    public function test_call_center_cannot_access_accounts(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->actingAs($user, 'admin')
            ->get(route('manager.accounts'))
            ->assertStatus(403);
    }

    public function test_call_center_cannot_access_report_lists(): void
    {
        $user = $this->createUserWithRole('call center');

        $this->actingAs($user, 'admin')
            ->get(route('manager.orders.has_problem'))
            ->assertStatus(403);
    }

    public function test_admin_can_access_roles_permissions_page(): void
    {
        $user = $this->createUserWithRole('admin');

        $this->actingAs($user, 'admin')
            ->get(route('manager.roles-permissions.index'))
            ->assertStatus(200)
            ->assertSee('Roles &amp; Permissions', false);
    }

    public function test_sales_cannot_access_roles_permissions_page(): void
    {
        $user = $this->createUserWithRole('sales');

        $this->actingAs($user, 'admin')
            ->get(route('manager.roles-permissions.index'))
            ->assertStatus(403);
    }

    public function test_show_returns_sales_permissions_checked_correctly(): void
    {
        $admin = $this->createUserWithRole('admin');
        $sales = Role::where('name', 'sales')->first();
        $this->assertNotNull($sales);

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('manager.roles-permissions.show', $sales));

        $response->assertStatus(200);
        $permissions = collect($response->json('permissions'));

        $this->assertTrue(
            $permissions->firstWhere('key', 'manage-games')['checked'] ?? false
        );
        $this->assertFalse(
            $permissions->firstWhere('key', 'manage-options')['checked'] ?? true
        );
    }

    public function test_update_capabilities_changes_gate_result(): void
    {
        $admin = $this->createUserWithRole('admin');
        $salesRole = Role::where('name', 'sales')->first();
        $salesUser = $this->createUserWithRole('sales');

        $this->assertTrue(Gate::forUser($salesUser)->allows('manage-games'));

        $capabilities = $salesRole->capabilities;
        $capabilities = array_values(array_diff($capabilities, array('manage-games')));

        $this->actingAs($admin, 'admin')
            ->putJson(route('manager.roles-permissions.update', $salesRole), array(
                'permissions' => $capabilities,
            ))
            ->assertStatus(200);

        $salesUser = $salesUser->fresh()->load('roles');

        $this->assertFalse(Gate::forUser($salesUser)->allows('manage-games'));

        $capabilities[] = 'manage-games';
        $this->actingAs($admin, 'admin')
            ->putJson(route('manager.roles-permissions.update', $salesRole), array(
                'permissions' => $capabilities,
            ))
            ->assertStatus(200);
    }

    public function test_store_duplicate_role_copies_capabilities(): void
    {
        $admin = $this->createUserWithRole('admin');
        $sales = Role::where('name', 'sales')->first();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('manager.roles-permissions.store'), array(
                'name' => 'sales copy test',
                'duplicate_from_role_id' => $sales->id,
            ));

        $response->assertStatus(201);

        $newRole = Role::where('name', 'sales copy test')->first();
        $this->assertNotNull($newRole);
        $this->assertEquals($sales->capabilities, $newRole->capabilities);

        $newRole->delete();
    }

    public function test_cannot_delete_admin_role(): void
    {
        $admin = $this->createUserWithRole('admin');
        $adminRole = Role::where('name', 'admin')->first();

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('manager.roles-permissions.destroy', $adminRole))
            ->assertStatus(422);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $admin = $this->createUserWithRole('admin');
        $salesRole = Role::where('name', 'sales')->first();
        $this->createUserWithRole('sales');

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('manager.roles-permissions.destroy', $salesRole))
            ->assertStatus(422);
    }

    public function test_roles_permissions_routes_are_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('manager.roles-permissions.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('manager.roles-permissions.show'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('manager.roles-permissions.update'));
    }

    public function test_sales_can_search_customer_orders_permission(): void
    {
        $user = $this->createUserWithRole('sales');

        $this->assertTrue(Gate::forUser($user)->allows('search-customer-orders'));
    }

    public function test_account_manager_cannot_search_customer_orders_by_default(): void
    {
        $user = $this->createUserWithRole('account manager');

        $this->assertFalse(Gate::forUser($user)->allows('search-customer-orders'));
    }

    public function test_accountant_can_search_customer_orders_permission(): void
    {
        $user = $this->createUserWithRole('accountant');

        $this->assertTrue(Gate::forUser($user)->allows('search-customer-orders'));
    }

    public function test_account_manager_cannot_access_navbar_quick_search(): void
    {
        $user = $this->createUserWithRole('account manager');

        $this->actingAs($user, 'admin')
            ->get(route('manager.orders.qsearch', array('search' => '+201234567890')))
            ->assertStatus(403);
    }

    public function test_sales_can_access_navbar_quick_search(): void
    {
        $user = $this->createUserWithRole('sales');

        $this->actingAs($user, 'admin')
            ->get(route('manager.orders.qsearch', array('search' => '+201234567890')))
            ->assertStatus(200);
    }
}
