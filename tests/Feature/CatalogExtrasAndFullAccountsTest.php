<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogExtrasAndFullAccountsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_review_api_creates_pending_review_by_phone(): void
    {
        $game = Game::factory()->create();

        $response = $this->postJson('/api/reviews', [
            'phone' => '+20122' . random_int(1000000, 9999999),
            'stars' => 5,
            'comment' => 'Great game',
            'game_id' => $game->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Review::STATUS_PENDING);

        $this->assertDatabaseHas('reviews', [
            'reviewable_id' => $game->id,
            'stars' => 5,
            'status' => Review::STATUS_PENDING,
        ]);
    }

    public function test_approved_review_appears_on_game_payload(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create([
            'name' => 'Reviewer One',
            'phone' => '+20111' . random_int(1000000, 9999999),
        ]);

        Review::create([
            'user_id' => $user->id,
            'reviewable_type' => $game->getMorphClass(),
            'reviewable_id' => $game->id,
            'stars' => 4,
            'comment' => 'Solid',
            'status' => Review::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'user_id' => User::factory()->create()->id,
            'reviewable_type' => $game->getMorphClass(),
            'reviewable_id' => $game->id,
            'stars' => 2,
            'comment' => 'Hidden pending',
            'status' => Review::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/games/' . $game->id);

        $response->assertOk()
            ->assertJsonPath('data.reviews.count', 1)
            ->assertJsonPath('data.reviews.average', 4)
            ->assertJsonPath('data.reviews.items.0.reviewer_name', 'Reviewer One');
    }

    public function test_full_sell_keeps_ps4_primary_and_clears_feature(): void
    {
        $game = Game::factory()->create();
        $account = Account::create(array_merge([
            'mail' => 'full_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $game->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '1234',
            'is_full' => true,
        ], Account::resolveInitialStocks(false, false)));

        $this->assertTrue($account->canSellAsFull());
        $this->assertTrue($account->isPristineForFullFeature());

        $account->applyFullSale();
        $account->refresh();

        $this->assertFalse($account->is_full);
        $this->assertFalse($account->canSellAsFull());
        $this->assertSame(1, (int) $account->ps4_primary_stock);
        $this->assertSame(0, (int) $account->ps4_secondary_stock);
        $this->assertSame(0, (int) $account->ps4_offline_stock);
        $this->assertSame(0, (int) $account->ps5_primary_stock);
        $this->assertSame(0, (int) $account->ps5_secondary_stock);
        $this->assertSame(0, (int) $account->ps5_offline_stock);

        $account->restoreFullSaleBundle();
        $account->refresh();

        $this->assertTrue($account->isPristineForFullFeature());
        $this->assertTrue($account->is_full);
        $this->assertTrue($account->canSellAsFull());
    }

    public function test_ps5_only_full_sell_clears_ps5_stocks(): void
    {
        $game = Game::factory()->create();
        $account = Account::create(array_merge([
            'mail' => 'full5_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $game->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '1234',
            'is_full' => true,
        ], Account::resolveInitialStocks(false, true)));

        $this->assertTrue($account->isPs5OnlyAccount());
        $this->assertTrue($account->canSellAsFull());

        $account->applyFullSale();
        $account->refresh();

        $this->assertFalse($account->is_full);
        $this->assertSame(0, (int) $account->ps5_primary_stock);
        $this->assertSame(0, (int) $account->ps5_secondary_stock);
        $this->assertSame(0, (int) $account->ps5_offline_stock);
    }

    public function test_individual_sale_disables_full_feature(): void
    {
        $game = Game::factory()->create();
        $account = Account::create(array_merge([
            'mail' => 'indiv_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $game->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '1234',
            'is_full' => true,
        ], Account::resolveInitialStocks(false, false)));

        $account->decrement('ps5_primary_stock', 1);
        $account->refresh();
        $account->disableFullFeatureIfNeeded();
        $account->refresh();

        $this->assertFalse($account->is_full);
        $this->assertFalse($account->canSellAsFull());
    }

    public function test_cannot_enable_full_on_non_pristine_stocks(): void
    {
        $game = Game::factory()->create();
        $account = Account::create(array_merge([
            'mail' => 'nopristine_' . uniqid() . '@example.com',
            'password' => 'secret',
            'game_id' => $game->id,
            'region' => 'US',
            'cost' => 10,
            'birthdate' => '1990-01-01',
            'login_code' => '1234',
            'is_full' => false,
        ], Account::resolveInitialStocks(false, false, ['ps4_primary' => true])));

        $this->assertFalse($account->isPristineForFullFeature());
    }
}
