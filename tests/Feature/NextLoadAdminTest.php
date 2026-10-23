<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\NextLoadOrder;
use App\Models\Role;
use App\Models\User;
use App\Services\NextLoad\NextLoadCatalog;
use App\Services\NextLoad\NextLoadClient;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Manager screens under /manager/next-load (manage-next-load).
 */
class NextLoadAdminTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE = 'https://nl.test/APIS/V1/';

    private User $admin;

    private array $createResponses = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->withoutVite();
        config([
            'services.nextload.enabled' => true,
            'services.nextload.base_url' => self::BASE,
            'services.nextload.email' => 'merchant@example.com',
            'services.nextload.password' => 'pass',
            'services.nextload.security_code' => '1234',
        ]);
        foreach (['nextload:token', NextLoadClient::PRODUCTS_CACHE_KEY, NextLoadCatalog::BALANCE_CACHE_KEY, 'nextload:outage'] as $key) {
            Cache::forget($key);
        }
        $this->admin = $this->userWithRole('admin');

        Http::fake(function ($request) {
            return match (substr($request->url(), strlen(self::BASE))) {
                'Token' => Http::response(['status' => 'Success', 'token' => 'tok']),
                'Balance' => Http::response(['status' => 'Success', 'data' => [['balance' => '250.000']]]),
                'ProductsList' => Http::response(['status' => 'Success', 'data' => [
                    ['product_id' => 266, 'product' => 'Test', 'price' => '0.000'],
                    ['product_id' => 352, 'product' => 'amazon KSA 50 SAR', 'price' => '13.380'],
                ]]),
                'CreateOrder' => Http::response(array_shift($this->createResponses) ?? ['status' => 'Error', 'message' => 'none'], 200),
                default => Http::response(['status' => 'Error', 'message' => 'unknown'], 404),
            };
        });
    }

    public function test_pages_require_manage_next_load(): void
    {
        $this->actingAs($this->userWithRole('sales'), 'admin')
            ->get(route('manager.next-load.index'))
            ->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $category = $this->linkedCategory();
        $order = $this->completeOrder($category);
        $this->actingAs($this->admin, 'admin');

        $this->get(route('manager.next-load.index'))->assertOk()->assertSee('250.000')->assertSee('Linked categories')->assertSee($category->name);
        $this->get(route('manager.next-load.products'))->assertOk()->assertSee('amazon KSA 50 SAR');
        $this->get(route('manager.next-load.links.create', ['product_id' => 352]))->assertOk()->assertSee('Linking buys nothing by itself.');
        $this->get(route('manager.next-load.links.edit', $category))->assertOk()->assertSee($category->name);
        $this->get(route('manager.next-load.orders.index'))->assertOk()->assertSee($order->reference);
        $this->get(route('manager.next-load.orders.show', $order))->assertOk()->assertDontSee('PIN-ADMIN');
    }

    public function test_link_existing_and_new_category(): void
    {
        $existing = CardCategory::create(['name' => 'Existing '.uniqid(), 'price' => 900]);
        $this->actingAs($this->admin, 'admin');

        $this->postJson(route('manager.next-load.links.store'), [
            'next_load_product_id' => 352,
            'supplier_mode' => 'local_first',
            'card_category_id' => $existing->id,
        ])->assertOk()->assertJsonPath('success', true);
        $existing->refresh();
        $this->assertSame(352, $existing->next_load_product_id);
        $this->assertSame('13.380', (string) $existing->supplier_cost);

        $name = 'From product '.uniqid();
        $this->postJson(route('manager.next-load.links.store'), [
            'next_load_product_id' => 266,
            'supplier_mode' => 'supplier_only',
            'name' => $name,
            'price' => 50,
        ])->assertOk();
        $this->assertDatabaseHas('card_categories', ['name' => $name, 'next_load_product_id' => 266, 'supplier_mode' => 'supplier_only']);

        $this->postJson(route('manager.next-load.links.store'), [
            'next_load_product_id' => 999,
            'supplier_mode' => 'supplier_only',
            'card_category_id' => $existing->id,
        ])->assertStatus(422);
    }

    public function test_update_and_unlink(): void
    {
        $category = $this->linkedCategory();
        $this->actingAs($this->admin, 'admin');

        $this->putJson(route('manager.next-load.links.update', $category), ['next_load_product_id' => 352, 'supplier_mode' => 'local_first', 'supplier_daily_limit' => '20'])
            ->assertOk();
        $category->refresh();
        $this->assertSame('local_first', $category->supplier_mode);
        $this->assertSame(20, $category->supplier_daily_limit);

        $this->putJson(route('manager.next-load.links.update', $category), ['next_load_product_id' => 352, 'supplier_mode' => 'local_first', 'supplier_daily_limit' => ''])
            ->assertOk();
        $this->assertNull($category->refresh()->supplier_daily_limit);
        $this->putJson(route('manager.next-load.links.update', $category), ['next_load_product_id' => 352, 'supplier_mode' => 'local_first', 'supplier_daily_limit' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('supplier_daily_limit');

        $this->deleteJson(route('manager.next-load.links.destroy', $category))->assertOk();
        $category->refresh();
        $this->assertNull($category->next_load_product_id);
        $this->assertSame('local_only', $category->supplier_mode);
    }

    public function test_restock_adds_available_cards(): void
    {
        $category = $this->linkedCategory();
        $this->createResponses[] = ['status' => 'Success', 'data' => [
            'order_id' => 77, 'status' => 'Complete', 'quantity' => 2, 'card_price' => '0.000', 'order_amount' => '0.000',
            'items' => [
                ['product_sn' => 'S1', 'product_pin' => 'PIN-R1-'.uniqid(), 'price' => '0.000'],
                ['product_sn' => 'S2', 'product_pin' => 'PIN-R2-'.uniqid(), 'price' => '0.000'],
            ],
        ]];

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('manager.next-load.links.restock', $category), ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(2, Card::where('card_category_id', $category->id)->where('status', true)->count());
        $this->assertSame(NextLoadOrder::PURPOSE_RESTOCK, NextLoadOrder::where('card_category_id', $category->id)->value('purpose'));

        $this->postJson(route('manager.next-load.links.restock', $category), ['quantity' => 500])->assertStatus(422);
    }

    public function test_release_and_audited_reveal(): void
    {
        $category = $this->linkedCategory();
        $order = $this->completeOrder($category);
        $item = $order->items()->first();
        $this->actingAs($this->admin, 'admin');

        $this->postJson(route('manager.next-load.orders.reveal', [$order, $item]))
            ->assertOk()
            ->assertJsonPath('pin', 'PIN-ADMIN-1');
        $this->assertDatabaseHas('system_activity_logs', ['action' => 'next_load.pin_revealed', 'subject_id' => $order->id]);

        $other = $this->completeOrder($category, 'PIN-ADMIN-2');
        $this->postJson(route('manager.next-load.orders.reveal', [$other, $item]))->assertNotFound();

        $this->postJson(route('manager.next-load.orders.release', $order))->assertOk();
        $this->assertSame(1, Card::where('next_load_order_item_id', $item->id)->where('status', true)->count());
        $this->postJson(route('manager.next-load.orders.release', $order))->assertStatus(422);
    }

    private function linkedCategory(): CardCategory
    {
        return CardCategory::create([
            'name' => 'Linked '.uniqid(),
            'price' => 100,
            'supplier_mode' => CardCategory::MODE_SUPPLIER_ONLY,
            'next_load_product_id' => 266,
        ]);
    }

    private function completeOrder(CardCategory $category, string $pin = 'PIN-ADMIN-1'): NextLoadOrder
    {
        $order = NextLoadOrder::create([
            'reference' => 'adm-'.uniqid(),
            'next_load_order_id' => (string) random_int(100, 999),
            'card_category_id' => $category->id,
            'product_id' => 266,
            'quantity' => 1,
            'delivered_quantity' => 1,
            'status' => NextLoadOrder::STATUS_COMPLETE,
            'purpose' => NextLoadOrder::PURPOSE_SALE,
            'storefront_order_id' => 'so-adm-'.uniqid(),
            'storefront_line_key' => 'line',
        ]);
        $order->items()->create(['serial' => 'SERIAL-'.uniqid(), 'pin' => $pin, 'pin_hash' => Card::hashCode($pin.uniqid()), 'price' => '0.000']);

        return $order;
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $role->capabilities = app(RolePermissionService::class)->defaultMatrix()[$roleName] ?? [];
        $role->save();
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }
}
