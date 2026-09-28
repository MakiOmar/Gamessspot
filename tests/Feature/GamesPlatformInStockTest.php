<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GamesPlatformInStockTest extends TestCase
{
    use DatabaseTransactions;

    private function account(Game $game, array $stock): void
    {
        Account::factory()->create(array_merge([
            'game_id' => $game->id,
            'mail' => 'instock_' . uniqid() . '@example.com',
            'is_full' => false,
            'ps4_offline_stock' => 0,
            'ps4_primary_stock' => 0,
            'ps4_secondary_stock' => 0,
            'ps5_offline_stock' => 0,
            'ps5_primary_stock' => 0,
            'ps5_secondary_stock' => 0,
            'password' => 'secret',
            'cost' => 1,
            'birthdate' => '1990-01-01',
            'login_code' => '1111',
        ], $stock));
    }

    /**
     * Titles across every page, so shared test data cannot push ours off page 1.
     */
    private function titles(string $url): Collection
    {
        $titles = collect();
        $page = 1;
        do {
            $response = $this->getJson($url . (str_contains($url, '?') ? '&' : '?') . 'page=' . $page);
            $response->assertOk();
            $titles = $titles->merge(collect($response->json('data'))->pluck('title'));
            $lastPage = (int) $response->json('last_page');
            $page++;
        } while ($page <= $lastPage);

        return $titles;
    }

    public function test_in_stock_only_keeps_only_games_with_a_sellable_offer(): void
    {
        $sellable = Game::factory()->create(['title' => 'InStock Sellable QQ1']);
        $offlineOnly = Game::factory()->create(['title' => 'InStock OfflineOnly QQ2']);
        $disabled = Game::factory()->create([
            'title' => 'InStock Disabled QQ3',
            'ps5_primary_status' => false,
            'ps5_secondary_status' => false,
        ]);

        $this->account($sellable, ['ps5_secondary_stock' => 2]);
        $this->account($offlineOnly, ['ps5_offline_stock' => 3]);
        $this->account($disabled, ['ps5_primary_stock' => 1, 'ps5_secondary_stock' => 1]);

        $all = $this->titles('/api/games/platform/5');
        $this->assertTrue($all->contains('InStock Sellable QQ1'));
        $this->assertTrue($all->contains('InStock OfflineOnly QQ2'));
        $this->assertTrue($all->contains('InStock Disabled QQ3'));

        $inStock = $this->titles('/api/games/platform/5?in_stock_only=1');
        $this->assertTrue($inStock->contains('InStock Sellable QQ1'));
        $this->assertFalse($inStock->contains('InStock OfflineOnly QQ2'));
        $this->assertFalse($inStock->contains('InStock Disabled QQ3'));
    }

    public function test_in_stock_only_applies_ps4_primary_offline_rule_per_platform(): void
    {
        $blocked = Game::factory()->create(['title' => 'InStock PS4 Blocked QQ4']);
        $ok = Game::factory()->create(['title' => 'InStock PS4 Ok QQ5']);

        // Primary stock only on an account that still has offline stock is not sellable on PS4.
        $this->account($blocked, ['ps4_primary_stock' => 1, 'ps4_offline_stock' => 1]);
        $this->account($ok, ['ps4_primary_stock' => 1]);

        $ps4 = $this->titles('/api/games/platform/4?in_stock_only=1');
        $this->assertFalse($ps4->contains('InStock PS4 Blocked QQ4'));
        $this->assertTrue($ps4->contains('InStock PS4 Ok QQ5'));

        $ps5 = $this->titles('/api/games/platform/5?in_stock_only=1');
        $this->assertFalse($ps5->contains('InStock PS4 Ok QQ5'));
    }
}
