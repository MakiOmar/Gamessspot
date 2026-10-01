<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Shop search (`q`) on GET /api/games/platform/{platform} filters before pagination; lists are newest first.
 */
class GamesPlatformSearchTest extends TestCase
{
    use DatabaseTransactions;

    private function stockedGame(string $title, array $attributes = []): Game
    {
        $game = Game::factory()->create(array_merge(['title' => $title], $attributes));
        Account::factory()->create([
            'game_id' => $game->id,
            'mail' => 'search_' . uniqid() . '@example.com',
            'ps4_offline_stock' => 0,
            'ps4_primary_stock' => 0,
            'ps4_secondary_stock' => 1,
            'ps5_offline_stock' => 0,
            'ps5_primary_stock' => 0,
            'ps5_secondary_stock' => 0,
        ]);

        return $game;
    }

    public function test_search_matches_title_or_code_and_paginates_the_matches(): void
    {
        $byTitle = $this->stockedGame('Zebrafinder Quest SRCH1');
        $byCode = $this->stockedGame('Unrelated Title SRCH2', ['code' => 'ZEBRAFINDER-77']);
        $this->stockedGame('Other Game SRCH3');

        $response = $this->getJson('/api/games/platform/4?q=zebrafinder')->assertOk();

        $this->assertSame(2, $response->json('total'));
        $this->assertEqualsCanonicalizing(
            [$byTitle->id, $byCode->id],
            collect($response->json('data'))->pluck('id')->all()
        );
        $this->assertStringContainsString('q=zebrafinder', (string) $response->json('next_page_url') . $response->json('first_page_url'));
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        $this->stockedGame('Percent Literal SRCH4');

        $this->getJson('/api/games/platform/4?q=' . urlencode('%'))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_list_is_newest_first(): void
    {
        $older = $this->stockedGame('Order Older SRCH5');
        $newer = $this->stockedGame('Order Newer SRCH6');

        $ids = collect($this->getJson('/api/games/platform/4?q=SRCH')->assertOk()->json('data'))->pluck('id')->all();

        $this->assertLessThan(array_search($older->id, $ids, true), array_search($newer->id, $ids, true));
    }
}
