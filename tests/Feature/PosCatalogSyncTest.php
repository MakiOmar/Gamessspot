<?php

namespace Tests\Feature;

use App\Jobs\SyncPosCatalogJob;
use App\Models\CardCategory;
use App\Models\Game;
use App\Models\GamePosProduct;
use App\Services\PosCatalogSync;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class PosCatalogSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('api_token', 'test-token', 60);
    }

    /**
     * Fake POS: echo every SKU back with a deterministic product/variation id.
     */
    private function fakePos(): void
    {
        Http::fake(function (Request $request) {
            if (! str_contains($request->url(), '/api/accounts/catalog/upsert/')) {
                return Http::response([], 404);
            }
            $items = [];
            foreach ($request['items'] as $i => $item) {
                $items[] = [
                    'sku' => $item['sku'],
                    'product_id' => 5000 + $i,
                    'variation_id' => 7000 + $i,
                    'active' => $item['active'],
                ];
            }

            return Http::response(['success' => true, 'items' => $items]);
        });
    }

    private function makeGame(array $overrides = []): Game
    {
        return Game::factory()->create(array_merge([
            'title' => 'Avatar',
            'full_price' => 0,
            'ps4_primary_status' => false,
            'ps4_secondary_status' => false,
            'ps4_offline_status' => false,
            'ps5_primary_status' => true,
            'ps5_primary_price' => 800,
            'ps5_secondary_status' => true,
            'ps5_secondary_price' => 500,
            'ps5_offline_status' => false,
        ], $overrides));
    }

    public function test_only_sold_offers_are_pushed_with_invoice_names(): void
    {
        $this->fakePos();
        $game = $this->makeGame();

        app(PosCatalogSync::class)->pushGame($game);

        Http::assertSent(function (Request $request) use ($game) {
            $skus = array_column($request['items'], 'sku');
            $names = array_column($request['items'], 'name');

            return $request->hasHeader('Authorization', 'Bearer test-token')
                && $skus === [
                    "ACCOUNTS-GAME-{$game->id}-PS5-PRIMARY",
                    "ACCOUNTS-GAME-{$game->id}-PS5-SECONDARY",
                ]
                && $names === ['Avatar Primary PS5', 'Avatar Secondary PS5'];
        });

        $this->assertSame(2, GamePosProduct::where('game_id', $game->id)->count());
        $this->assertDatabaseHas('game_pos_products', [
            'game_id' => $game->id, 'platform' => 'ps5', 'offer' => 'primary', 'pos_product_id' => 5000, 'pos_active' => 1,
        ]);
    }

    public function test_turned_off_offer_is_pushed_inactive_and_hidden_from_shop(): void
    {
        $this->fakePos();
        $game = $this->makeGame();
        $sync = app(PosCatalogSync::class);
        $sync->pushGame($game);

        $game->update(['ps5_secondary_status' => false]);
        $sync->pushGame($game->fresh());

        $this->assertDatabaseHas('game_pos_products', [
            'game_id' => $game->id, 'platform' => 'ps5', 'offer' => 'secondary', 'pos_active' => 0,
        ]);
        $offers = GamePosProduct::shopOffersFor([$game->id])[$game->id];
        $this->assertArrayHasKey('primary', $offers['5']);
        $this->assertArrayNotHasKey('secondary', $offers['5']);
    }

    public function test_full_offer_follows_full_price(): void
    {
        $this->fakePos();
        $game = $this->makeGame(['full_price' => 1500]);

        $offers = collect(app(PosCatalogSync::class)->offersForGame($game))->where('offer', 'full');

        $this->assertSame(['ps4', 'ps5'], $offers->pluck('platform')->values()->all());
        $this->assertSame('Avatar Full PS4', $offers->first()['name']);
    }

    public function test_card_category_stores_link_without_resyncing(): void
    {
        $this->fakePos();
        $category = CardCategory::create(['name' => 'PSN 50 USD', 'price' => 2500]);

        app(PosCatalogSync::class)->pushCardCategory($category);

        $category->refresh();
        $this->assertSame(5000, $category->pos_product_id);
        $this->assertSame(7000, $category->activePosVariationId());
        Http::assertSentCount(1);
    }

    public function test_game_save_queues_sync_after_commit_and_featured_toggle_does_not(): void
    {
        config(['services.pos_catalog.enabled' => true]);
        Queue::fake();

        $game = $this->makeGame();
        Queue::assertPushed(SyncPosCatalogJob::class, fn ($job) => $job->kind === 'game' && $job->id === $game->id);

        Queue::fake();
        $game->update(['is_featured' => true]);
        Queue::assertNotPushed(SyncPosCatalogJob::class);

        $game->update(['ps5_primary_price' => 900]);
        Queue::assertPushed(SyncPosCatalogJob::class);
    }

    public function test_pos_failure_does_not_break_inline_save(): void
    {
        config(['services.pos_catalog.enabled' => true]);
        Http::fake(['*' => Http::response(['message' => 'down'], 500)]);

        $game = $this->makeGame();

        $this->assertModelExists($game);
        $this->assertSame(0, GamePosProduct::where('game_id', $game->id)->count());
    }

    public function test_backfill_waits_out_rate_limit_using_retry_after(): void
    {
        Sleep::fake();
        $game = $this->makeGame();
        $okItems = [
            ['sku' => "ACCOUNTS-GAME-{$game->id}-PS5-PRIMARY", 'product_id' => 10, 'variation_id' => 20],
            ['sku' => "ACCOUNTS-GAME-{$game->id}-PS5-SECONDARY", 'product_id' => 11, 'variation_id' => 21],
        ];
        Http::fakeSequence('*/api/accounts/catalog/upsert/*')
            ->push(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '7'])
            ->push(['message' => 'Too Many Attempts.'], 429)
            ->push(['success' => true, 'items' => $okItems]);

        app(PosCatalogSync::class)->withRetries()->pushGame($game);

        Http::assertSentCount(3);
        Sleep::assertSequence([Sleep::for(7)->seconds(), Sleep::for(2)->seconds()]);
        $this->assertSame(2, GamePosProduct::where('game_id', $game->id)->count());
    }

    public function test_inline_push_does_not_wait_on_rate_limit(): void
    {
        Sleep::fake();
        Http::fake(['*' => Http::response(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '30'])]);

        try {
            app(PosCatalogSync::class)->pushGame($this->makeGame());
            $this->fail('Expected the 429 to surface.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('429', $e->getMessage());
        }

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_backfill_gives_up_after_max_retries(): void
    {
        config(['services.pos_catalog.max_retries' => 2]);
        Sleep::fake();
        Http::fake(['*' => Http::response(['message' => 'Too Many Attempts.'], 429)]);
        $game = $this->makeGame();

        $this->artisan('pos:sync-catalog', ['--games' => true, '--id' => [$game->id]])
            ->expectsOutputToContain('Synced 0 game(s), 0 with no offers to push, 1 failed.')
            ->assertFailed();

        Http::assertSentCount(3);
        Sleep::assertSleptTimes(2);
    }

    public function test_backfill_batches_card_categories_and_skips_games_without_offers(): void
    {
        $this->fakePos();
        $idle = $this->makeGame(['ps5_primary_status' => false, 'ps5_secondary_status' => false]);
        $first = CardCategory::create(['name' => 'PSN 10 USD', 'price' => 500]);
        $second = CardCategory::create(['name' => 'PSN 20 USD', 'price' => 1000]);

        $this->artisan('pos:sync-catalog', ['--games' => true, '--id' => [$idle->id]])
            ->expectsOutputToContain('Synced 0 game(s), 1 with no offers to push, 0 failed.')
            ->assertSuccessful();
        Http::assertNothingSent();

        $this->artisan('pos:sync-catalog', ['--cards' => true, '--id' => [$first->id, $second->id]])
            ->expectsOutputToContain('Synced 2 card category(s), 0 failed.')
            ->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['kind'] === 'card' && count($request['items']) === 2);
        $this->assertSame(7001, $second->fresh()->pos_variation_id);
    }

    public function test_game_api_exposes_active_offer_links_by_shop_platform(): void
    {
        $game = $this->makeGame();
        GamePosProduct::create([
            'game_id' => $game->id, 'platform' => 'ps5', 'offer' => 'primary',
            'pos_product_id' => 11, 'pos_variation_id' => 22, 'pos_active' => true,
        ]);

        $this->getJson('/api/games/'.$game->id)
            ->assertOk()
            ->assertJsonPath('data.pos_offers.5.primary.variation_id', 22);
    }
}
