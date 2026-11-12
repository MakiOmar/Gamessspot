<?php

namespace Tests\Feature;

use App\Exports\OrdersExport;
use App\Models\DataExport;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Store profile sell log (`/manager/orders?id=`) can be narrowed to one seller in the list, search and export.
 */
class SellLogSellerFilterTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private StoresProfile $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_active' => true, 'name' => 'Admin Seller']);
        $this->admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
        $this->store = StoresProfile::factory()->create();
    }

    public function test_store_sell_log_lists_only_sellers_with_orders_in_that_store(): void
    {
        $alice = User::factory()->create(['name' => 'Alice Store Seller']);
        $bob = User::factory()->create(['name' => 'Bob Other Store']);
        $this->makeOrder($alice, $this->store);
        $this->makeOrder($bob, StoresProfile::factory()->create());

        $this->actingAs($this->admin, 'admin')
            ->get(route('manager.orders', ['id' => $this->store->id]))
            ->assertOk()
            ->assertSee('id="sellerFilter"', false)
            ->assertSee('Alice Store Seller')
            ->assertDontSee('Bob Other Store');
    }

    public function test_sell_log_without_store_profile_has_no_seller_filter(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('manager.orders'))
            ->assertOk()
            ->assertDontSee('id="sellerFilter"', false);
    }

    public function test_search_returns_only_the_selected_sellers_orders(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $aliceOrder = $this->makeOrder($alice, $this->store);
        $bobOrder = $this->makeOrder($bob, $this->store);

        $rows = $this->actingAs($this->admin, 'admin')
            ->getJson(route('manager.orders.search', [
                'store_profile_id' => $this->store->id,
                'seller_id'        => $alice->id,
                'start_date'       => now()->toDateString(),
                'end_date'         => now()->toDateString(),
            ]))
            ->assertOk()
            ->json('rows');

        $this->assertStringContainsString('orderRow-' . $aliceOrder->id, $rows);
        $this->assertStringNotContainsString('orderRow-' . $bobOrder->id, $rows);
    }

    public function test_export_carries_and_applies_the_seller_filter(): void
    {
        Queue::fake();
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $aliceOrder = $this->makeOrder($alice, $this->store);
        $this->makeOrder($bob, $this->store);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('manager.orders.export'), ['id' => $this->store->id, 'seller_id' => $alice->id])
            ->assertStatus(202);

        $filters = DataExport::findOrFail($response->json('id'))->filters;
        $this->assertEquals(['store_profile_id' => $this->store->id, 'seller_id' => $alice->id], $filters);
        $this->assertSame([$aliceOrder->id], (new OrdersExport($filters))->query()->pluck('orders.id')->all());
    }

    private function makeOrder(User $seller, StoresProfile $store): Order
    {
        return Order::create([
            'seller_id'        => $seller->id,
            'store_profile_id' => $store->id,
            'buyer_phone'      => '0107' . random_int(1000000, 9999999),
            'buyer_name'       => 'Buyer',
            'price'            => 100,
            'sold_item'        => 'card',
        ]);
    }
}
