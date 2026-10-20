<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\NextLoadOrder;
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

    public function test_poll_completes_manager_order_and_releases_codes_to_stock(): void
    {
        $order = $this->inProgressOrder(null);
        $this->fake(['FetchOrder' => ['status' => 'Success', 'data' => [[
            'status' => 'Complete', 'price' => '0.000', 'totalamount' => '0.000',
            'items' => [['product_sn' => 'SN', 'product_pin' => 'PIN-POLL-'.uniqid(), 'price' => '0.000']],
        ]]]]);

        $this->artisan('nextload:poll')->assertSuccessful();

        $order->refresh();
        $this->assertSame(NextLoadOrder::STATUS_COMPLETE, $order->status);
        $this->assertNotNull($order->consumed_at);
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

    private function inProgressOrder(?string $storefrontOrderId): NextLoadOrder
    {
        return NextLoadOrder::create([
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
