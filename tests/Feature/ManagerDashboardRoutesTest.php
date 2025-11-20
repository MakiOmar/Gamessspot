<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerDashboardRoutesTest extends TestCase
{
    // Note: Disabled RefreshDatabase due to migration foreign key constraints
    // Database tests should use a properly migrated test database
    // use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole->id);
    }

    /**
     * Test dashboard is accessible to authenticated admin.
     */
    public function test_dashboard_is_accessible_to_authenticated_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager');

        $response->assertStatus(200);
    }

    /**
     * Test health check route is accessible to authenticated admin.
     */
    public function test_health_check_route_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager/health-check');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'checks'
            ]);
    }

    /**
     * Test games route is accessible.
     */
    public function test_games_route_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager/games');

        $response->assertStatus(200);
    }

    /**
     * Test PS4 games route is accessible.
     */
    public function test_ps4_games_route_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager/games/ps4');

        $response->assertStatus(200);
    }

    /**
     * Test PS5 games route is accessible.
     */
    public function test_ps5_games_route_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager/games/ps5');

        $response->assertStatus(200);
    }

    /**
     * Test accounts route is accessible.
     */
    public function test_accounts_route_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager/accounts');

        $response->assertStatus(200);
    }

    /**
     * Test orders route is accessible.
     */
    public function test_orders_route_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager/orders');

        $response->assertStatus(200);
    }

    /**
     * Test users route is accessible.
     */
    public function test_users_route_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/manager/users');

        $response->assertStatus(200);
    }
}

