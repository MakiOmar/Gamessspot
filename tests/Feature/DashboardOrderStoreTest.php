<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Dashboard sell form (POST manager/orders/store): stock, transaction cleanup and safe error responses.
 */
class DashboardOrderStoreTest extends TestCase
{
    use DatabaseTransactions;

    private User $seller;

    private StoresProfile $store;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seller = User::factory()->create(['is_active' => true]);
        $this->seller->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
        $this->store = StoresProfile::factory()->create();
    }

    private function payload(Game $game, array $overrides = []): array
    {
        return array_merge([
            'store_profile_id' => $this->store->id,
            'game_id' => $game->id,
            'buyer_phone' => '0108'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer',
            'price' => 500,
            'type' => 'secondary',
            'platform' => '5',
        ], $overrides);
    }

    public function test_sale_takes_one_stock_and_records_order(): void
    {
        $game = Game::factory()->create();
        $account = Account::factory()->create(['game_id' => $game->id, 'ps5_secondary_stock' => 2]);

        $this->actingAs($this->seller, 'admin')
            ->postJson(route('orders.store'), $this->payload($game))
            ->assertOk()
            ->assertJsonPath('account_email', $account->mail);

        $this->assertSame(1, (int) $account->fresh()->ps5_secondary_stock);
        $this->assertDatabaseHas('orders', ['account_id' => $account->id, 'sold_item' => 'ps5_secondary_stock']);
    }

    public function test_no_matching_account_closes_the_transaction(): void
    {
        $game = Game::factory()->create();
        Account::factory()->create(['game_id' => $game->id, 'ps5_secondary_stock' => 0]);
        $level = DB::transactionLevel();

        $this->actingAs($this->seller, 'admin')
            ->postJson(route('orders.store'), $this->payload($game))
            ->assertStatus(422);

        $this->assertSame($level, DB::transactionLevel());
    }

    public function test_unexpected_failure_hides_exception_details(): void
    {
        $game = Game::factory()->create();
        $account = Account::factory()->create(['game_id' => $game->id, 'ps5_secondary_stock' => 1]);
        Order::creating(fn () => throw new \RuntimeException('internal secret detail'));

        $response = $this->actingAs($this->seller, 'admin')
            ->postJson(route('orders.store'), $this->payload($game))
            ->assertStatus(500)
            ->assertJsonMissingPath('error');

        $this->assertStringNotContainsString('internal secret detail', $response->getContent());
        $this->assertSame(1, (int) $account->fresh()->ps5_secondary_stock);
    }
}
