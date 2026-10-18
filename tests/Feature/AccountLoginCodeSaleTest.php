<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\AccountLoginCodeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Dashboard sell form hands out one login code per primary/secondary/full sale; offline never gets one.
 */
class AccountLoginCodeSaleTest extends TestCase
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

    private function sell(Game $game, string $type, string $platform = '5')
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

    public function test_parse_accepts_spaces_commas_and_newlines(): void
    {
        $codes = (new AccountLoginCodeService())->parse("111 222,333 ,\n444,,  555");

        $this->assertSame(array('111', '222', '333', '444', '555'), $codes);
    }

    public function test_secondary_sale_delivers_first_code_and_removes_it(): void
    {
        $game = Game::factory()->create();
        $account = Account::factory()->create([
            'game_id'             => $game->id,
            'ps5_secondary_stock' => 2,
            'login_code'          => '111, 222 333',
        ]);

        $this->sell($game, 'secondary')
            ->assertOk()
            ->assertJsonPath('login_code', '111')
            ->assertJsonPath('delivers_login_code', true);

        $this->assertSame('222 333', $account->fresh()->login_code);
        $this->assertDatabaseHas('orders', ['account_id' => $account->id, 'login_code' => '111']);
    }

    public function test_sale_without_codes_left_succeeds_with_null_code(): void
    {
        $game = Game::factory()->create();
        Account::factory()->create([
            'game_id'             => $game->id,
            'ps5_secondary_stock' => 1,
            'login_code'          => '  ,  ',
        ]);

        $this->sell($game, 'secondary')
            ->assertOk()
            ->assertJsonPath('login_code', null)
            ->assertJsonPath('delivers_login_code', true);
    }

    public function test_offline_sale_does_not_consume_a_code(): void
    {
        $game = Game::factory()->create();
        $account = Account::factory()->create([
            'game_id'           => $game->id,
            'ps5_offline_stock' => 2,
            'login_code'        => '111 222',
        ]);

        $this->sell($game, 'offline')
            ->assertOk()
            ->assertJsonPath('login_code', null)
            ->assertJsonPath('delivers_login_code', false);

        $this->assertSame('111 222', $account->fresh()->login_code);
    }

    public function test_undo_puts_the_code_back(): void
    {
        $game = Game::factory()->create();
        $account = Account::factory()->create([
            'game_id'             => $game->id,
            'ps5_secondary_stock' => 2,
            'login_code'          => '111 222',
        ]);

        $this->sell($game, 'secondary')->assertOk();
        $order = Order::where('account_id', $account->id)->latest('id')->firstOrFail();

        $this->actingAs($this->seller, 'admin')
            ->postJson(route('manager.orders.undo'), ['order_id' => $order->id, 'sold_item' => $order->sold_item])
            ->assertOk();

        $this->assertSame('111 222', $account->fresh()->login_code);
    }
}
