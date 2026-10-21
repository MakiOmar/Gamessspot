<?php

namespace Tests\Feature;

use App\Jobs\NotifyPosOrderUndoneJob;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\PosApiToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Undoing an order POS allocated for a sales order / POS sell tells POS to unfetch that line.
 */
class PosOrderUndoNotifyTest extends TestCase
{
    use DatabaseTransactions;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->manager = User::factory()->create(['is_active' => true]);
        $this->manager->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
    }

    private function cardOrder(array $attributes = []): Order
    {
        $category = CardCategory::create(['name' => 'Undo Test '.uniqid(), 'price' => 100]);
        $card = Card::create(['code' => 'UNDO-'.uniqid(), 'cost' => 50, 'card_category_id' => $category->id, 'status' => false]);

        return Order::create(array_merge([
            'seller_id' => $this->manager->id,
            'store_profile_id' => StoresProfile::factory()->create()->id,
            'buyer_phone' => '0109'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer',
            'price' => 100,
            'sold_item' => 'card',
            'card_id' => $card->id,
        ], $attributes));
    }

    private function undo(Order $order)
    {
        return $this->actingAs($this->manager, 'admin')
            ->postJson(route('manager.orders.undo'), ['order_id' => $order->id, 'sold_item' => $order->sold_item]);
    }

    public function test_undoing_a_pos_allocated_order_notifies_pos(): void
    {
        foreach (['so-41', 'pos-42'] as $ref) {
            $order = $this->cardOrder([
                'storefront_order_id' => $ref,
                'storefront_line_key' => 'card|category:1|line:9|n:1',
                'pos_order_id' => '42',
            ]);

            $this->undo($order)->assertOk()->assertJsonPath('success', true);

            $this->assertNull(Order::find($order->id));
            Queue::assertPushed(NotifyPosOrderUndoneJob::class, fn (NotifyPosOrderUndoneJob $job) => $job->storefrontOrderId === $ref
                && $job->lineKey === 'card|category:1|line:9|n:1'
                && $job->accountsOrderId === $order->id);
        }
    }

    public function test_storefront_and_manual_orders_do_not_notify_pos(): void
    {
        $this->undo($this->cardOrder(['storefront_order_id' => 'sf-abc', 'storefront_line_key' => 'card|n:1']))->assertOk();
        $this->undo($this->cardOrder())->assertOk();

        Queue::assertNotPushed(NotifyPosOrderUndoneJob::class);
    }

    public function test_job_posts_to_pos_and_refreshes_an_expired_token(): void
    {
        Cache::put(PosApiToken::CACHE_KEY, 'stale-token', 60);
        $calls = 0;
        Http::fake(function (Request $request) use (&$calls) {
            if (str_ends_with($request->url(), '/api/login')) {
                return Http::response(['token' => 'fresh-token']);
            }
            $calls++;

            return $request->hasHeader('Authorization', 'Bearer fresh-token')
                ? Http::response(['success' => true, 'reset' => true])
                : Http::response(['message' => 'Unauthenticated.'], 401);
        });

        (new NotifyPosOrderUndoneJob('so-41', 'card|n:1', 77))->handle(app(PosApiToken::class));

        $this->assertSame(2, $calls);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/accounts/orders/undone/1')
            && $r['storefront_order_id'] === 'so-41'
            && $r['storefront_line_key'] === 'card|n:1'
            && $r['accounts_order_id'] === 77);
    }
}
