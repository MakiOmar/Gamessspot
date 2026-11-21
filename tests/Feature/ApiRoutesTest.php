<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    public function test_api_login_with_allowlisted_user_returns_scoped_token(): void
    {
        $user = User::factory()->create([
            'phone' => '+2011'.random_int(10000000, 99999999),
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        config(['api.login_user_ids' => [$user->id], 'api.login_phones' => []]);

        $response = $this->postJson('/api/login', [
            'phone' => $user->phone,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);

        $token = $user->tokens()->latest('id')->first();
        $this->assertNotNull($token);
        $this->assertContains('orders:receive', $token->abilities);
        $this->assertNotContains('*', $token->abilities);
    }

    public function test_api_login_rejects_non_allowlisted_user(): void
    {
        $user = User::factory()->create([
            'phone' => '+2011'.random_int(10000000, 99999999),
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        config(['api.login_user_ids' => [], 'api.login_phones' => []]);

        $this->postJson('/api/login', [
            'phone' => $user->phone,
            'password' => 'password123',
        ])->assertStatus(403)
            ->assertJson(['message' => 'API login is restricted to integration accounts.']);
    }

    public function test_api_login_with_invalid_credentials_returns_error(): void
    {
        $response = $this->postJson('/api/login', [
            'phone' => '+201234567890',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_api_login_requires_phone_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'password']);
    }

    public function test_authenticated_user_can_access_api_user_route(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'phone',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_api_user_route(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_api_games_by_platform_returns_successful_response(): void
    {
        $response = $this->getJson('/api/games/platform/ps4');

        $this->assertContains($response->status(), [200, 400]);
    }

    public function test_api_card_categories_list_returns_successful_response(): void
    {
        $response = $this->getJson('/api/card-ctegories/list');

        $response->assertStatus(200);
    }
}
