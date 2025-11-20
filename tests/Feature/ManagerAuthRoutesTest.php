<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManagerAuthRoutesTest extends TestCase
{
    // Note: Disabled RefreshDatabase due to migration foreign key constraints
    // Database tests should use a properly migrated test database
    // use RefreshDatabase;

    /**
     * Test manager login page is accessible.
     */
    public function test_manager_login_page_is_accessible(): void
    {
        $response = $this->get('/manager/login');

        $response->assertStatus(200);
    }

    /**
     * Test manager login with valid credentials.
     */
    public function test_manager_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $user->roles()->attach($adminRole->id);

        $response = $this->post('/manager/login', [
            'phone' => $user->phone,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/manager');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test manager login with invalid credentials.
     */
    public function test_manager_login_with_invalid_credentials_returns_error(): void
    {
        $response = $this->post('/manager/login', [
            'phone' => '+201234567890',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /**
     * Test manager logout.
     */
    public function test_manager_logout_logs_user_out(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $user->roles()->attach($adminRole->id);

        $this->actingAs($user);

        $response = $this->post('/manager/logout');

        $response->assertRedirect('/manager/login');
        $this->assertGuest();
    }

    /**
     * Test protected manager routes require authentication.
     */
    public function test_protected_manager_routes_require_authentication(): void
    {
        $response = $this->get('/manager');

        $response->assertRedirect('/manager/login');
    }
}

