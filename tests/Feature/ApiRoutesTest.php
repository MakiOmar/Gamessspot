<?php

namespace Tests\Feature;

use App\Models\User;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    // Note: Disabled RefreshDatabase due to migration foreign key constraints
    // Database tests should use a properly migrated test database
    // use RefreshDatabase;

    /**
     * Test API login route with valid credentials.
     */
    public function test_api_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create([
            'phone' => '+201234567890',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'phone' => '+201234567890',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    /**
     * Test API login route with invalid credentials.
     */
    public function test_api_login_with_invalid_credentials_returns_error(): void
    {
        $response = $this->postJson('/api/login', [
            'phone' => '+201234567890',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /**
     * Test API login route validation.
     */
    public function test_api_login_requires_phone_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'password']);
    }

    /**
     * Test authenticated API user route.
     */
    public function test_authenticated_user_can_access_api_user_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'phone'
            ]);
    }

    /**
     * Test unauthenticated user cannot access API user route.
     */
    public function test_unauthenticated_user_cannot_access_api_user_route(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * Test API games by platform route.
     */
    public function test_api_games_by_platform_returns_successful_response(): void
    {
        $response = $this->getJson('/api/games/platform/ps4');

        $response->assertStatus(200);
    }

    /**
     * Test API card categories list route.
     */
    public function test_api_card_categories_list_returns_successful_response(): void
    {
        $response = $this->getJson('/api/card-ctegories/list');

        $response->assertStatus(200);
    }
}

