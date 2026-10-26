<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\CacheManager;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Dashboard "Unsold Mails Assets" (no order) and "In-Stock Mails Assets" (any slot left) cost cards.
 */
class DashboardUnsoldAccountsCostTest extends TestCase
{
    use DatabaseTransactions;

    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        $this->game = Game::factory()->create();
    }

    public function test_scopes_split_never_sold_and_in_stock_accounts(): void
    {
        $untouched = $this->account(100, ['ps4_offline_stock' => 2, 'ps5_primary_stock' => 1]);
        $partlySold = $this->account(200, ['ps5_primary_stock' => 1]);
        $soldOut = $this->account(400, []);
        Order::factory()->create(['account_id' => $partlySold->id]);
        Order::factory()->create(['account_id' => $soldOut->id]);
        $ids = [$untouched->id, $partlySold->id, $soldOut->id];

        $this->assertEqualsWithDelta(100.0, (float) Account::whereKey($ids)->neverSold()->sum('cost'), 0.001);
        $this->assertEqualsWithDelta(300.0, (float) Account::whereKey($ids)->withStockLeft()->sum('cost'), 0.001);
    }

    public function test_cached_totals_follow_a_sale_and_its_undo(): void
    {
        $account = $this->account(150, ['ps4_offline_stock' => 1]);
        $before = (float) CacheManager::getNeverSoldAccountCost();

        $order = Order::factory()->create(['account_id' => $account->id]);
        $this->assertEqualsWithDelta($before - 150, (float) CacheManager::getNeverSoldAccountCost(), 0.001);

        $order->delete();
        $this->assertEqualsWithDelta($before, (float) CacheManager::getNeverSoldAccountCost(), 0.001);
    }

    public function test_dashboard_shows_both_cards(): void
    {
        $this->account(75, ['ps4_offline_stock' => 1]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('manager.dashboard'))
            ->assertOk()
            ->assertViewHas('neverSoldAccountsCost', fn ($value) => abs((float) $value - (float) Account::neverSold()->sum('cost')) < 0.001)
            ->assertViewHas('inStockAccountsCost', fn ($value) => abs((float) $value - (float) Account::withStockLeft()->sum('cost')) < 0.001)
            ->assertSee('Unsold Mails Assets')
            ->assertSee('In-Stock Mails Assets');
    }

    private function account(float $cost, array $stock): Account
    {
        return Account::factory()->create(array_merge(
            array_fill_keys(Account::STOCK_FIELDS, 0),
            [
                'game_id' => $this->game->id,
                'mail' => 'unsold_'.uniqid().'@example.com',
                'is_full' => false,
                'password' => 'secret',
                'cost' => $cost,
                'birthdate' => '1990-01-01',
                'login_code' => '1111',
            ],
            $stock
        ));
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->capabilities = app(RolePermissionService::class)->defaultMatrix()['admin'] ?? [];
        $role->save();
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }
}
