<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductTypeListingTest extends TestCase
{
    use DatabaseTransactions;

    private function actingManager(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $user->roles()->attach($admin->id);
        }

        return $user;
    }

    public function test_platform_api_defaults_to_games_only(): void
    {
        $game = Game::factory()->create(['title' => 'Unique Game Title XYZ']);
        $sub = Game::factory()->subscription()->create(['title' => 'PS Plus Unique Sub XYZ']);

        Account::factory()->create([
            'game_id' => $game->id,
            'mail' => 'acct_game_' . uniqid() . '@example.com',
            'ps4_primary_stock' => 1,
            'ps4_offline_stock' => 0,
            'ps4_secondary_stock' => 1,
            'password' => 'secret',
            'cost' => 1,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
        ]);
        Account::factory()->create([
            'game_id' => $sub->id,
            'mail' => 'acct_sub_' . uniqid() . '@example.com',
            'ps4_primary_stock' => 1,
            'ps4_offline_stock' => 0,
            'ps4_secondary_stock' => 1,
            'password' => 'secret',
            'cost' => 1,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
        ]);

        $default = $this->getJson('/api/games/platform/4');
        $default->assertOk();
        $itemTitles = collect($default->json('data'))->pluck('title');
        $this->assertTrue($itemTitles->contains('Unique Game Title XYZ'));
        $this->assertFalse($itemTitles->contains('PS Plus Unique Sub XYZ'));

        $subs = $this->getJson('/api/games/platform/4?product_type=subscription');
        $subs->assertOk();
        $subTitles = collect($subs->json('data'))->pluck('title');
        $this->assertTrue($subTitles->contains('PS Plus Unique Sub XYZ'));
        $this->assertFalse($subTitles->contains('Unique Game Title XYZ'));
    }

    public function test_manager_ps4_routes_separate_games_and_subscriptions(): void
    {
        $user = $this->actingManager();

        $game = Game::factory()->create(['title' => 'Mgr Game Only AAA']);
        $sub = Game::factory()->subscription()->create(['title' => 'Mgr Sub Only BBB']);

        Account::factory()->create([
            'game_id' => $game->id,
            'mail' => 'mgr_game_' . uniqid() . '@example.com',
            'ps4_primary_stock' => 2,
            'ps4_offline_stock' => 1,
            'ps4_secondary_stock' => 1,
            'password' => 'secret',
            'cost' => 1,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
        ]);
        Account::factory()->create([
            'game_id' => $sub->id,
            'mail' => 'mgr_sub_' . uniqid() . '@example.com',
            'ps4_primary_stock' => 2,
            'ps4_offline_stock' => 1,
            'ps4_secondary_stock' => 1,
            'password' => 'secret',
            'cost' => 1,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
        ]);

        $gamesPage = $this->actingAs($user, 'admin')->get(route('manager.games.ps4'));
        $gamesPage->assertOk();
        $gamesPage->assertSee('Mgr Game Only AAA');
        $gamesPage->assertDontSee('Mgr Sub Only BBB');

        $subsPage = $this->actingAs($user, 'admin')->get(route('manager.subscriptions.ps4'));
        $subsPage->assertOk();
        $subsPage->assertSee('Mgr Sub Only BBB');
        $subsPage->assertDontSee('Mgr Game Only AAA');
        $subsPage->assertSee('PS4 Subscriptions');
    }

    #[DataProvider('managerGamesRouteProvider')]
    public function test_manager_games_listing_lazy_loads_cover_images(string $routeName, int $platform): void
    {
        $user = $this->actingManager();

        $game = Game::factory()->create([
            'title' => "Lazy Cover PS{$platform} CCC",
            "ps{$platform}_image_url" => "assets/uploads/lazy-cover-ps{$platform}.webp",
        ]);

        Account::factory()->create([
            'game_id' => $game->id,
            'mail' => "lazy_ps{$platform}_" . uniqid() . '@example.com',
            "ps{$platform}_primary_stock" => 1,
            "ps{$platform}_offline_stock" => 1,
            "ps{$platform}_secondary_stock" => 1,
            'password' => 'secret',
            'cost' => 1,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
        ]);

        $response = $this->actingAs($user, 'admin')->get(route($routeName));

        $response->assertOk();
        $response->assertSee(
            '<img src="' . asset("assets/uploads/lazy-cover-ps{$platform}.webp") . '"',
            false
        );
        $response->assertSee('loading="lazy"', false);
        $response->assertDontSee('background-image: url(', false);
    }

    public static function managerGamesRouteProvider(): array
    {
        return [
            'ps4 games' => ['manager.games.ps4', 4],
            'ps5 games' => ['manager.games.ps5', 5],
        ];
    }

    public function test_game_by_id_includes_product_type(): void
    {
        $sub = Game::factory()->subscription()->create();

        $response = $this->getJson('/api/games/' . $sub->id);
        $response->assertOk()
            ->assertJsonPath('data.product_type', Game::TYPE_SUBSCRIPTION);
    }
}
