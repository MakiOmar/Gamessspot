<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Selling offline slots keeps the full-sell feature; full sales snapshot offline stock for undo.
 */
class FullSaleIgnoresOfflineTest extends TestCase
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

    private function makeFullAccount(Game $game, bool $ps5Only = false): Account
    {
        return Account::create(array_merge([
            'mail'       => 'full_' . uniqid() . '@example.com',
            'password'   => 'secret',
            'game_id'    => $game->id,
            'region'     => 'US',
            'cost'       => 10,
            'birthdate'  => '1990-01-01',
            'login_code' => '111 222 333',
            'is_full'    => true,
        ], Account::resolveInitialStocks(false, $ps5Only)));
    }

    private function sell(Game $game, string $type, string $platform)
    {
        return $this->actingAs($this->seller, 'admin')->postJson(route('orders.store'), [
            'store_profile_id' => $this->store->id,
            'game_id'          => $game->id,
            'buyer_phone'      => '0109' . random_int(1000000, 9999999),
            'buyer_name'       => 'Buyer',
            'price'            => 500,
            'type'             => $type,
            'platform'         => $platform,
        ]);
    }

    public function test_offline_sale_keeps_full_feature(): void
    {
        $game = Game::factory()->create();
        $account = $this->makeFullAccount($game);

        $this->sell($game, 'offline', '4')->assertOk();
        $this->sell($game, 'offline', '4')->assertOk();
        $this->sell($game, 'offline', '5')->assertOk();

        $account->refresh();
        $this->assertSame(0, (int) $account->ps4_offline_stock);
        $this->assertSame(0, (int) $account->ps5_offline_stock);
        $this->assertTrue((bool) $account->is_full);
        $this->assertTrue($account->canSellAsFull());
        $this->assertSame(1, Account::whereKey($account->id)->eligibleForFullSell()->count());
    }

    public function test_primary_sale_still_disables_full_feature(): void
    {
        $game = Game::factory()->create();
        $account = $this->makeFullAccount($game);

        $this->sell($game, 'primary', '5')->assertOk();

        $this->assertFalse((bool) $account->fresh()->is_full);
    }

    public function test_full_sale_after_offline_sold_and_undo_restores_snapshot(): void
    {
        $game = Game::factory()->create();
        $account = $this->makeFullAccount($game);

        $this->sell($game, 'offline', '4')->assertOk();
        $this->sell($game, 'full', '5')->assertOk()->assertJsonPath('login_code', '111');

        $order = Order::where('account_id', $account->id)->where('sold_item', 'like', '%_full')->firstOrFail();
        $this->assertSame(array('ps4_offline_stock' => 1, 'ps5_offline_stock' => 1), $order->stock_snapshot);

        $account->refresh();
        $this->assertFalse((bool) $account->is_full);
        $this->assertSame(0, (int) $account->ps4_offline_stock);

        $this->actingAs($this->seller, 'admin')
            ->postJson(route('manager.orders.undo'), ['order_id' => $order->id, 'sold_item' => $order->sold_item])
            ->assertOk();

        $account->refresh();
        $this->assertSame(1, (int) $account->ps4_offline_stock, 'Offline slot sold before the full sale must stay sold');
        $this->assertSame(1, (int) $account->ps5_offline_stock);
        $this->assertSame(1, (int) $account->ps5_primary_stock);
        $this->assertSame(1, (int) $account->ps5_secondary_stock);
        $this->assertTrue((bool) $account->is_full);
    }

    public function test_ps5_only_account_stays_full_after_offline_sold_out(): void
    {
        $game = Game::factory()->create();
        $account = $this->makeFullAccount($game, true);

        $account->update(['ps5_offline_stock' => 0]);
        $account->disableFullFeatureIfNeeded();

        $this->assertTrue((bool) $account->fresh()->is_full);
        $this->assertTrue($account->fresh()->canSellAsFull());
    }
}
