<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Models\Order;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\NextLoad\NextLoadCatalog;
use App\Services\NextLoad\NextLoadClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NextLoadCommandsTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE = 'https://nl.test/APIS/V1/';

    private CardCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config([
            'services.nextload.enabled' => true,
            'services.nextload.base_url' => self::BASE,
            'services.nextload.email' => 'merchant@example.com',
            'services.nextload.password' => 'pass',
            'services.nextload.security_code' => '1234',
            'services.nextload.low_balance' => '50',
        ]);
        foreach (['nextload:token', NextLoadClient::PRODUCTS_CACHE_KEY, NextLoadCatalog::BALANCE_CACHE_KEY, 'nextload:outage', 'nextload:low_balance_alerted'] as $key) {
            Cache::forget($key);
        }
        $this->category = CardCategory::create([
            'name' => 'NL Cmd '.uniqid(),
            'price' => 100,
            'supplier_mode' => CardCategory::MODE_SUPPLIER_ONLY,
            'next_load_product_id' => 266,
        ]);
    }

    public function test_poll_records_delayed_manager_sale_in_the_sell_log(): void
    {
        $seller = User::factory()->create();
        $store = StoresProfile::factory()->create();
        $order = $this->inProgressOrder(null, $seller->id, [
            'card_category_id' => $this->category->id,
            'store_profile_id' => $store->id,
            'buyer_phone' => '01012345678',
            'buyer_name' => 'Delayed Buyer',
            'price' => 100,
            'notes' => 'counter sale',
        ]);
        $this->fakeComplete('PIN-POLL-'.uniqid());

        $this->artisan('nextload:poll')->expectsOutputToContain('recorded 1 delayed sale')->assertSuccessful();

        $order->refresh();
        $this->assertSame(NextLoadOrder::STATUS_COMPLETE, $order->status);
        $this->assertNotNull($order->consumed_at);
        $card = Card::where('card_category_id', $this->category->id)->sole();
        $this->assertFalse($card->status);
        $this->assertSame(Card::SOURCE_NEXT_LOAD, $card->source);
        $this->assertDatabaseHas('orders', [
            'card_id' => $card->id,
            'seller_id' => $seller->id,
            'store_profile_id' => $store->id,
            'buyer_phone' => '01012345678',
            'buyer_name' => 'Delayed Buyer',
            'sold_item' => 'card',
            'notes' => 'counter sale',
        ]);
        $this->assertDatabaseHas('system_activity_logs', ['action' => 'next_load.pending_sale_recorded', 'subject_id' => $order->id]);
    }

    public function test_poll_releases_manager_order_without_sale_details_to_stock(): void
    {
        $order = $this->inProgressOrder(null);
        $this->fakeComplete('PIN-OLD-'.uniqid());

        $this->artisan('nextload:poll')->assertSuccessful();

        $this->assertNotNull($order->refresh()->consumed_at);
        $this->assertSame(1, Card::where('card_category_id', $this->category->id)->where('status', true)->count());
        $this->assertSame(0, Order::whereIn('card_id', Card::where('card_category_id', $this->category->id)->pluck('id'))->count());
    }

    public function test_poll_releases_to_stock_when_the_delayed_sale_cannot_be_recorded(): void
    {
        $order = $this->inProgressOrder(null, null, [
            'card_category_id' => $this->category->id,
            'store_profile_id' => 999999999,
            'buyer_phone' => '01012345678',
            'buyer_name' => 'Gone Store',
            'price' => 100,
        ]);
        $this->fakeComplete('PIN-FK-'.uniqid());

        $this->artisan('nextload:poll')->assertSuccessful();

        $this->assertNotNull($order->refresh()->consumed_at);
        $this->assertSame(1, Card::where('card_category_id', $this->category->id)->where('status', true)->count());
    }

    public function test_poll_keeps_storefront_codes_for_the_waiting_sale(): void
    {
        $order = $this->inProgressOrder('so-poll-'.uniqid());
        $this->fake(['FetchOrder' => ['status' => 'Success', 'data' => [[
            'status' => 'Complete', 'price' => '0.000', 'totalamount' => '0.000',
            'items' => [['product_sn' => 'SN', 'product_pin' => 'PIN-SF-'.uniqid(), 'price' => '0.000']],
        ]]]]);

        $this->artisan('nextload:poll')->assertSuccessful();

        $this->assertSame(NextLoadOrder::STATUS_COMPLETE, $order->refresh()->status);
        $this->assertNull($order->consumed_at);
        $this->assertSame(0, Card::where('card_category_id', $this->category->id)->count());
    }

    public function test_balance_command_caches_balance_and_flags_low_balance(): void
    {
        $this->fake(['Balance' => ['status' => 'Success', 'data' => [['balance' => '12.500']]]]);

        $this->artisan('nextload:balance')->expectsOutputToContain('below')->assertSuccessful();
        $this->assertSame('12.500', Cache::get(NextLoadCatalog::BALANCE_CACHE_KEY));
        $this->assertDatabaseHas('system_activity_logs', ['action' => 'next_load.low_balance']);
    }

    public function test_sync_products_updates_supplier_cost(): void
    {
        $this->fake(['ProductsList' => ['status' => 'Success', 'data' => [['product_id' => 266, 'product' => 'Test', 'price' => '7.250']]]]);

        $this->artisan('nextload:sync-products')->assertSuccessful();

        $this->assertSame('7.250', (string) $this->category->refresh()->supplier_cost);
        $this->assertNotNull($this->category->supplier_synced_at);
    }

    public function test_reconcile_flags_unknown_references(): void
    {
        $this->fake(['Orders' => ['status' => 'Success', 'data' => [['order_id' => 1, 'reference' => 'not-ours-'.uniqid(), 'status' => 'Complete']]]]);

        $this->artisan('nextload:reconcile')->expectsOutputToContain('1 unknown')->assertSuccessful();
    }

    public function test_commands_do_nothing_when_disabled(): void
    {
        config(['services.nextload.enabled' => false]);
        Http::fake();

        foreach (['nextload:poll', 'nextload:balance', 'nextload:sync-products', 'nextload:reconcile'] as $command) {
            $this->artisan($command)->assertSuccessful();
        }
        Http::assertNothingSent();
    }

    private function fakeComplete(string $pin): void
    {
        $this->fake(['FetchOrder' => ['status' => 'Success', 'data' => [[
            'status' => 'Complete', 'price' => '0.000', 'totalamount' => '0.000',
            'items' => [['product_sn' => 'SN', 'product_pin' => $pin, 'price' => '0.000']],
        ]]]]);
    }

    private function inProgressOrder(?string $storefrontOrderId, ?int $requestedBy = null, ?array $saleContext = null): NextLoadOrder
    {
        return NextLoadOrder::create([
            'requested_by' => $requestedBy,
            'sale_context' => $saleContext,
            'reference' => 'test-'.uniqid(),
            'next_load_order_id' => '42',
            'card_category_id' => $this->category->id,
            'product_id' => 266,
            'quantity' => 1,
            'status' => NextLoadOrder::STATUS_IN_PROGRESS,
            'purpose' => NextLoadOrder::PURPOSE_SALE,
            'storefront_order_id' => $storefrontOrderId,
            'storefront_line_key' => $storefrontOrderId ? 'card|line:1' : null,
            'next_poll_at' => now()->subMinute(),
        ]);
    }

    /** @param  array<string, array>  $responses endpoint => JSON body */
    private function fake(array $responses): void
    {
        $fakes = [self::BASE.'Token' => Http::response(['status' => 'Success', 'token' => 'tok'])];
        foreach ($responses as $endpoint => $body) {
            $fakes[self::BASE.$endpoint] = Http::response($body);
        }
        Http::fake($fakes);
    }
}
