<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FeaturedGamesApiTest extends TestCase
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

    public function test_featured_endpoint_returns_only_featured_games_with_platform_payload_shape(): void
    {
        $featured = Game::factory()->featured()->create([
            'title' => 'Featured Catalog Game',
            'ps5_primary_status' => true,
            'ps5_secondary_status' => true,
        ]);
        $notFeatured = Game::factory()->create([
            'title' => 'Regular Catalog Game',
            'ps5_primary_status' => true,
        ]);

        Account::create(array_merge([
            'mail' => 'feat_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $featured->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
            'is_full' => false,
        ], Account::resolveInitialStocks(false, false)));

        Account::create(array_merge([
            'mail' => 'reg_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $notFeatured->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '2222',
            'is_full' => false,
        ], Account::resolveInitialStocks(false, false)));

        $response = $this->getJson('/api/games/featured/5');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($featured->id, $ids);
        $this->assertNotContains($notFeatured->id, $ids);

        $item = collect($response->json('data'))->firstWhere('id', $featured->id);
        $this->assertNotNull($item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('code', $item);
        $this->assertArrayHasKey('product_type', $item);
        $this->assertArrayHasKey('image_url', $item);
        $this->assertArrayHasKey('types', $item);
        $this->assertArrayHasKey('primary', $item['types']);
        $this->assertArrayHasKey('secondary', $item['types']);
        $this->assertArrayHasKey('full', $item['types']);
        $this->assertArrayHasKey('rating_average', $item);
        $this->assertArrayHasKey('rating_count', $item);
    }

    public function test_featured_endpoint_rejects_invalid_platform(): void
    {
        $this->getJson('/api/games/featured/3')
            ->assertStatus(400)
            ->assertJsonPath('error', 'Invalid platform. Use 4 for PS4 or 5 for PS5.');
    }

    public function test_combined_featured_endpoint_returns_both_platforms_with_count(): void
    {
        $featured = Game::factory()->featured()->create([
            'title' => 'Both Platforms Featured',
            'ps4_primary_status' => true,
            'ps5_primary_status' => true,
        ]);

        Account::create(array_merge([
            'mail' => 'both_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $featured->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '3333',
            'is_full' => false,
        ], Account::resolveInitialStocks(false, false)));

        $response = $this->getJson('/api/games/featured?count=5');

        $response->assertOk()
            ->assertJsonPath('count', 5)
            ->assertJsonStructure([
                'count',
                '4',
                '5',
            ]);

        $this->assertContains($featured->id, collect($response->json('4'))->pluck('id')->all());
        $this->assertContains($featured->id, collect($response->json('5'))->pluck('id')->all());

        $ps5Item = collect($response->json('5'))->firstWhere('id', $featured->id);
        $this->assertArrayHasKey('types', $ps5Item);
        $this->assertArrayHasKey('primary', $ps5Item['types']);
        $this->assertLessThanOrEqual(5, count($response->json('4')));
        $this->assertLessThanOrEqual(5, count($response->json('5')));
    }

    public function test_featured_platform_count_returns_limited_list(): void
    {
        $featured = Game::factory()->featured()->create([
            'ps5_primary_status' => true,
        ]);

        Account::create(array_merge([
            'mail' => 'cnt_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $featured->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '4444',
            'is_full' => false,
        ], Account::resolveInitialStocks(false, false)));

        $response = $this->getJson('/api/games/featured/5?count=3');

        $response->assertOk()
            ->assertJsonPath('count', 3)
            ->assertJsonStructure(['count', 'data']);

        $this->assertLessThanOrEqual(3, count($response->json('data')));
        $this->assertContains($featured->id, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_toggle_featured_flips_flag(): void
    {
        $game = Game::factory()->create(['is_featured' => false]);
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->patchJson('/manager/games/' . $game->id . '/featured')
            ->assertOk()
            ->assertJsonPath('is_featured', true);

        $this->assertTrue($game->fresh()->is_featured);

        $this->actingAs($admin, 'admin')
            ->patchJson('/manager/games/' . $game->id . '/featured')
            ->assertOk()
            ->assertJsonPath('is_featured', false);

        $this->assertFalse($game->fresh()->is_featured);
    }
}
