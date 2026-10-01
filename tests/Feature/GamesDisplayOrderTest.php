<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Shop listings (platform list + featured) sort by games.display_order ascending, then unordered games newest first.
 */
class GamesDisplayOrderTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
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

    private function stockedGame(string $title, array $attributes = []): Game
    {
        $game = Game::factory()->create(array_merge(['title' => $title], $attributes));
        Account::factory()->create([
            'game_id' => $game->id,
            'mail' => 'order_' . uniqid() . '@example.com',
            'ps4_offline_stock' => 0,
            'ps4_primary_stock' => 0,
            'ps4_secondary_stock' => 1,
            'ps5_offline_stock' => 0,
            'ps5_primary_stock' => 0,
            'ps5_secondary_stock' => 0,
        ]);

        return $game;
    }

    /** @return array{0: Game, 1: Game, 2: Game, 3: Game} [unorderedOld, second, first, unorderedNew] */
    private function seedOrderedGames(bool $featured): array
    {
        $attrs = ['is_featured' => $featured];

        return [
            $this->stockedGame('Ordering Old DORD1', $attrs),
            $this->stockedGame('Ordering Second DORD2', $attrs + ['display_order' => 20]),
            $this->stockedGame('Ordering First DORD3', $attrs + ['display_order' => 5]),
            $this->stockedGame('Ordering New DORD4', $attrs),
        ];
    }

    /** @param list<int> $ids */
    private function assertRelativeOrder(array $expected, array $ids): void
    {
        $positions = array_map(fn (Game $g) => array_search($g->id, $ids, true), $expected);
        $this->assertNotContains(false, $positions, 'Every seeded game must be in the response.');
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
    }

    public function test_platform_list_puts_ordered_games_first_then_newest(): void
    {
        [$old, $second, $first, $new] = $this->seedOrderedGames(false);

        $ids = collect($this->getJson('/api/games/platform/4?q=DORD')->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$first->id, $second->id, $new->id, $old->id], $ids);
    }

    public function test_featured_list_respects_display_order(): void
    {
        [$old, $second, $first, $new] = $this->seedOrderedGames(true);

        $ids = collect($this->getJson('/api/games/featured/4?count=50')->assertOk()->json('data'))->pluck('id')->all();

        $this->assertRelativeOrder([$first, $second, $new, $old], $ids);
    }

    private function updatePayload(Game $game, array $overrides): array
    {
        return array_merge([
            'title' => $game->title,
            'code' => $game->code,
            'product_type' => $game->product_type ?? 'game',
            'full_price' => 100,
            'ps4_primary_status' => 1,
            'ps4_secondary_status' => 1,
            'ps4_offline_status' => 1,
            'ps5_primary_status' => 1,
            'ps5_secondary_status' => 1,
            'ps5_offline_status' => 1,
        ], $overrides);
    }

    public function test_manager_update_saves_and_clears_display_order(): void
    {
        $game = $this->stockedGame('Ordering Update DORD5');
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->putJson('/manager/games/' . $game->id, $this->updatePayload($game, ['display_order' => '3']))
            ->assertSuccessful();
        $this->assertSame(3, $game->fresh()->display_order);

        $this->actingAs($admin, 'admin')
            ->putJson('/manager/games/' . $game->id, $this->updatePayload($game, ['display_order' => '']))
            ->assertSuccessful();
        $this->assertNull($game->fresh()->display_order);
    }

    public function test_manager_update_rejects_negative_display_order(): void
    {
        $game = $this->stockedGame('Ordering Invalid DORD6');

        $this->actingAs($this->createAdminUser(), 'admin')
            ->putJson('/manager/games/' . $game->id, $this->updatePayload($game, ['display_order' => -1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('display_order');
    }
}
