<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\NextLoad\NextLoadCatalog;
use App\Services\NextLoad\NextLoadClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * POST /api/orders/receive for gift cards backed by Next Load (Http::fake, no real supplier calls).
 */
class NextLoadSaleTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE = 'https://nl.test/APIS/V1/';

    private StoresProfile $store;

    private CardCategory $category;

    /** @var list<array{status:int, body:array}> queued CreateOrder responses */
    private array $createResponses = [];

    /** @var list<array{status:int, body:array}> queued FetchOrder responses */
    private array $fetchResponses = [];

    /** @var array|callable Orders endpoint payload */
    private mixed $ordersList = [];

    private string $productPrice = '0.000';

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
            'services.nextload.exchange_rate' => '1',
            'services.nextload.allow_loss' => false,
        ]);
        foreach (['nextload:token', NextLoadClient::PRODUCTS_CACHE_KEY, NextLoadCatalog::BALANCE_CACHE_KEY, 'nextload:outage'] as $key) {
            Cache::forget($key);
        }

        Sanctum::actingAs(User::factory()->create(['is_active' => true]));
        $this->store = StoresProfile::factory()->create();
        $this->category = CardCategory::create([
            'name' => 'NL Test '.uniqid(),
            'price' => 100,
            'supplier_mode' => CardCategory::MODE_SUPPLIER_ONLY,
            'next_load_product_id' => 266,
        ]);
        $this->fakeSupplier();
    }

    public function test_supplier_only_sale_buys_a_code_and_stores_it_encrypted(): void
    {
        $this->createResponses[] = $this->complete('PIN-AAA-1');

        $this->sell($this->storefrontPayload())
            ->assertCreated()
            ->assertJsonPath('code', 'PIN-AAA-1');

        $card = Card::where('card_category_id', $this->category->id)->firstOrFail();
        $this->assertSame(Card::SOURCE_NEXT_LOAD, $card->source);
        $this->assertFalse($card->status);
        $this->assertNotSame('PIN-AAA-1', DB::table('cards')->where('id', $card->id)->value('code'));
        $this->assertNotSame('PIN-AAA-1', DB::table('next_load_order_items')->where('id', $card->next_load_order_item_id)->value('pin'));

        $supplierOrder = NextLoadOrder::where('card_category_id', $this->category->id)->firstOrFail();
        $this->assertSame(NextLoadOrder::STATUS_COMPLETE, $supplierOrder->status);
        $this->assertNotNull($supplierOrder->consumed_at);
    }

    public function test_local_first_sells_local_code_before_buying(): void
    {
        $this->category->update(['supplier_mode' => CardCategory::MODE_LOCAL_FIRST]);
        Card::create(['code' => 'LOCAL-'.uniqid(), 'cost' => 50, 'card_category_id' => $this->category->id, 'status' => true]);
        $this->createResponses[] = $this->complete('PIN-BBB-1');

        $this->sell($this->storefrontPayload('1'))->assertCreated();
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), 'CreateOrder'));

        $this->sell($this->storefrontPayload('2'))->assertCreated()->assertJsonPath('code', 'PIN-BBB-1');
    }

    public function test_local_only_never_calls_the_supplier(): void
    {
        $this->category->update(['supplier_mode' => CardCategory::MODE_LOCAL_ONLY]);

        $this->sell($this->storefrontPayload())->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_in_progress_returns_pending_and_retry_reuses_the_same_supplier_order(): void
    {
        $payload = $this->storefrontPayload();
        $this->createResponses[] = ['status' => 200, 'body' => ['status' => 'Success', 'data' => [
            'order_id' => 501, 'reference' => 'x', 'status' => 'InProgress', 'quantity' => 0, 'card_price' => '0.000', 'items' => [],
        ]]];

        $this->sell($payload)->assertStatus(202)->assertJsonPath('pending', true);

        $this->fetchResponses[] = ['status' => 200, 'body' => ['status' => 'Success', 'data' => [
            ['reference' => 'x', 'status' => 'Complete', 'price' => '0.000', 'totalamount' => '0.000', 'items' => [['product_sn' => 'SN1', 'product_pin' => 'PIN-CCC-1', 'price' => '0.000']]],
        ]]];

        $this->sell($payload)->assertCreated()->assertJsonPath('code', 'PIN-CCC-1');
        $this->sell($payload)->assertOk()->assertJsonPath('duplicate', true)->assertJsonPath('code', 'PIN-CCC-1');

        Http::assertSentCount(4); // Token, ProductsList, one CreateOrder, one FetchOrder
        $this->assertSame(1, NextLoadOrder::where('storefront_order_id', $payload['storefront_order_id'])->count());
    }

    public function test_duplicate_reference_is_recovered_without_buying_again(): void
    {
        $this->createResponses[] = ['status' => 500, 'body' => ['status' => 'Error', 'message' => 'reference is existing']];
        $this->fetchResponses[] = ['status' => 200, 'body' => ['status' => 'Success', 'data' => [
            ['status' => 'Complete', 'price' => '0.000', 'totalamount' => '0.000', 'items' => [['product_sn' => 'SN2', 'product_pin' => 'PIN-DDD-1', 'price' => '0.000']]],
        ]]];
        $payload = $this->storefrontPayload();
        $this->ordersList = fn () => [['order_id' => 777, 'reference' => NextLoadOrder::where('storefront_order_id', $payload['storefront_order_id'])->value('reference')]];

        $this->sell($payload)->assertCreated()->assertJsonPath('code', 'PIN-DDD-1');
        $this->assertSame('777', NextLoadOrder::where('storefront_order_id', $payload['storefront_order_id'])->value('next_load_order_id'));
    }

    public function test_failed_order_is_not_retried_with_the_same_reference(): void
    {
        $payload = $this->storefrontPayload();
        $this->createResponses[] = ['status' => 200, 'body' => ['status' => 'Success', 'data' => ['order_id' => 9, 'status' => 'Failed', 'items' => []]]];
        $this->createResponses[] = $this->complete('PIN-EEE-1');

        $this->sell($payload)->assertStatus(422);
        $this->sell($payload)->assertCreated()->assertJsonPath('code', 'PIN-EEE-1');

        $references = NextLoadOrder::where('storefront_order_id', $payload['storefront_order_id'])->orderBy('id')->pluck('reference');
        $this->assertCount(2, $references);
        $this->assertStringEndsWith('-1', $references[0]);
        $this->assertStringEndsWith('-2', $references[1]);
    }

    public function test_refetch_after_undo_buys_a_new_code_with_a_new_reference(): void
    {
        $payload = $this->storefrontPayload();
        $this->createResponses[] = $this->complete('PIN-UND-1');
        $this->createResponses[] = $this->complete('PIN-UND-2');

        $this->sell($payload)->assertCreated()->assertJsonPath('code', 'PIN-UND-1');
        $order = Order::where('storefront_order_id', $payload['storefront_order_id'])->firstOrFail();
        $manager = User::factory()->create(['is_active' => true]);
        $manager->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
        $this->actingAs($manager, 'admin')
            ->postJson(route('manager.orders.undo'), ['order_id' => $order->id, 'sold_item' => $order->sold_item])
            ->assertOk()->assertJsonPath('success', true);

        $this->sell($payload)->assertCreated()->assertJsonPath('code', 'PIN-UND-2');

        $references = NextLoadOrder::where('storefront_order_id', $payload['storefront_order_id'])->orderBy('id')->pluck('reference');
        $this->assertCount(2, $references);
        $this->assertStringEndsWith('-2', $references[1]);
    }

    public function test_timeout_keeps_reference_and_retry_resends_it(): void
    {
        $payload = $this->storefrontPayload();
        $this->createResponses[] = ['status' => 502, 'body' => []];
        $this->createResponses[] = $this->complete('PIN-FFF-1');

        $this->sell($payload)->assertStatus(202);
        $this->sell($payload)->assertCreated()->assertJsonPath('code', 'PIN-FFF-1');

        $sent = collect(Http::recorded())->filter(fn ($pair) => str_ends_with($pair[0]->url(), 'CreateOrder'))->map(fn ($pair) => $pair[0]['reference'])->unique();
        $this->assertCount(1, $sent);
    }

    public function test_loss_guard_blocks_purchases_above_sale_price(): void
    {
        $this->productPrice = '150.000';

        $this->sell($this->storefrontPayload())->assertStatus(422);
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), 'CreateOrder'));
    }

    public function test_listing_and_stock_check_include_supplier_categories(): void
    {
        $this->productPrice = '10.000';
        Cache::put(NextLoadCatalog::BALANCE_CACHE_KEY, '35.000', 60);

        $row = collect($this->getJson('/api/card-ctegories/list')->assertOk()->json('data'))->firstWhere('id', $this->category->id);
        $this->assertNotNull($row);
        $this->assertSame(3, $row['available_count']);
        $this->assertArrayNotHasKey('supplier_mode', $row);

        $this->postJson('/api/orders/check_card_stock', ['category_id' => $this->category->id])
            ->assertOk()
            ->assertJsonPath('stock', 3)
            ->assertJsonPath('supplier_available', true);
    }

    private function sell(array $payload)
    {
        return $this->postJson('/api/orders/receive', $payload);
    }

    private function storefrontPayload(string $line = '1'): array
    {
        $this->storefrontOrderId ??= 'so-nl-'.uniqid();

        return [
            'card_category_id' => $this->category->id,
            'store_profile_id' => $this->store->id,
            'buyer_phone' => '0108'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer',
            'price' => 100,
            'storefront_order_id' => $this->storefrontOrderId,
            'storefront_line_key' => 'card|category:'.$this->category->id.'|line:'.$line,
            'pos_transaction_id' => 5151,
        ];
    }

    private ?string $storefrontOrderId = null;

    private function complete(string $pin): array
    {
        return ['status' => 200, 'body' => ['status' => 'Success', 'data' => [
            'order_id' => random_int(1000, 9999), 'status' => 'Complete', 'quantity' => 1, 'card_price' => '0.000', 'order_amount' => '0.000',
            'product' => 'Test', 'items' => [['product_sn' => 'SN-'.$pin, 'product_pin' => $pin, 'price' => '0.000', 'product' => 'Test']],
        ]]];
    }

    private function fakeSupplier(): void
    {
        Http::fake(function (Request $request) {
            $endpoint = substr($request->url(), strlen(self::BASE));

            return match ($endpoint) {
                'Token' => Http::response(['status' => 'Success', 'token' => 'tok']),
                'Balance' => Http::response(['status' => 'Success', 'data' => [['balance' => '1000.000']]]),
                'ProductsList' => Http::response(['status' => 'Success', 'data' => [['product_id' => 266, 'product' => 'Test', 'price' => $this->productPrice]]]),
                'CreateOrder' => $this->next($this->createResponses),
                'FetchOrder' => $this->next($this->fetchResponses),
                'Orders' => Http::response(['status' => 'Success', 'data' => is_callable($this->ordersList) ? ($this->ordersList)() : $this->ordersList]),
                default => Http::response(['status' => 'Error', 'message' => 'unknown'], 404),
            };
        });
    }

    private function next(array &$queue)
    {
        $response = array_shift($queue) ?? ['status' => 500, 'body' => ['status' => 'Error', 'message' => 'no fake queued']];

        return Http::response($response['body'], $response['status']);
    }
}
