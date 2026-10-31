<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * GET /manager/gift-cards/sell-log: gift card sales (local and Next Load codes) only.
 */
class GiftCardSellLogTest extends TestCase
{
    use DatabaseTransactions;

    private CardCategory $category;

    private StoresProfile $store;

    private string $tag;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->withoutVite();
        $this->tag = uniqid();
        $this->store = StoresProfile::factory()->create();
        $this->category = CardCategory::create(['name' => 'Gift Log '.$this->tag, 'price' => 100]);
    }

    public function test_lists_local_and_next_load_sales_but_not_account_sales(): void
    {
        $seller = User::factory()->create();
        $this->cardSale('Local Buyer '.$this->tag, Card::SOURCE_MANUAL, $seller->id);
        $this->cardSale('Supplier Buyer '.$this->tag, Card::SOURCE_NEXT_LOAD, $seller->id);
        $this->accountSale('Account Buyer '.$this->tag);

        $this->actingAs($this->userWithRole('admin'), 'admin')
            ->get(route('manager.gift-cards.sell-log', ['card_category_id' => $this->category->id]))
            ->assertOk()
            ->assertSee('Local Buyer '.$this->tag)
            ->assertSee('Supplier Buyer '.$this->tag)
            ->assertSee('Next Load')
            ->assertDontSee('Account Buyer '.$this->tag)
            ->assertViewHas('totals', fn (array $totals) => $totals['count'] === 2 && $totals['revenue'] === 200.0);
    }

    public function test_source_filter(): void
    {
        $this->cardSale('Local Buyer '.$this->tag, Card::SOURCE_MANUAL);
        $this->cardSale('Supplier Buyer '.$this->tag, Card::SOURCE_NEXT_LOAD);

        $this->actingAs($this->userWithRole('admin'), 'admin')
            ->get(route('manager.gift-cards.sell-log', ['card_category_id' => $this->category->id, 'source' => Card::SOURCE_NEXT_LOAD]))
            ->assertOk()
            ->assertSee('Supplier Buyer '.$this->tag)
            ->assertDontSee('Local Buyer '.$this->tag);
    }

    public function test_date_filter_defaults_to_today(): void
    {
        $this->cardSale('Today Buyer '.$this->tag, Card::SOURCE_MANUAL);
        $old = $this->cardSale('Old Buyer '.$this->tag, Card::SOURCE_MANUAL);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin, 'admin')
            ->get(route('manager.gift-cards.sell-log'))
            ->assertOk()
            ->assertSee('Today Buyer '.$this->tag)
            ->assertDontSee('Old Buyer '.$this->tag);

        $this->get(route('manager.gift-cards.sell-log', ['start_date' => now()->subDays(3)->toDateString(), 'end_date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Old Buyer '.$this->tag);
    }

    public function test_search_matches_buyer_phone_and_exact_code(): void
    {
        $this->cardSale('Phone Buyer '.$this->tag, Card::SOURCE_MANUAL, null, 'CODE-'.$this->tag);
        $this->cardSale('Other Buyer '.$this->tag, Card::SOURCE_MANUAL);
        $this->actingAs($this->userWithRole('admin'), 'admin');

        $this->get(route('manager.gift-cards.sell-log', ['q' => 'CODE-'.$this->tag]))
            ->assertOk()
            ->assertSee('Phone Buyer '.$this->tag)
            ->assertDontSee('Other Buyer '.$this->tag);
    }

    public function test_sales_role_sees_shop_orders_and_website_label(): void
    {
        $order = $this->cardSale('Shop Buyer '.$this->tag, Card::SOURCE_NEXT_LOAD);
        $order->update(['storefront_order_id' => 'so-log-'.$this->tag, 'storefront_line_key' => 'line']);

        $this->actingAs($this->userWithRole('sales'), 'admin')
            ->get(route('manager.gift-cards.sell-log', ['card_category_id' => $this->category->id]))
            ->assertOk()
            ->assertSee('Shop Buyer '.$this->tag)
            ->assertSee('Website');
    }

    public function test_user_without_customer_search_sees_only_own_sales(): void
    {
        $user = $this->userWithCapabilities(['view-sell-log', 'manage-gift-cards']);
        $this->cardSale('Mine '.$this->tag, Card::SOURCE_MANUAL, $user->id);
        $this->cardSale('Someone Else '.$this->tag, Card::SOURCE_MANUAL, User::factory()->create()->id);

        $this->actingAs($user, 'admin')
            ->get(route('manager.gift-cards.sell-log'))
            ->assertOk()
            ->assertSee('Mine '.$this->tag)
            ->assertDontSee('Someone Else '.$this->tag);
    }

    public function test_requires_manage_gift_cards(): void
    {
        $this->actingAs($this->userWithRole('account manager'), 'admin')
            ->get(route('manager.gift-cards.sell-log'))
            ->assertForbidden();
    }

    public function test_codes_and_cost_are_hidden_from_accountants(): void
    {
        $this->cardSale('Accountant View '.$this->tag, Card::SOURCE_MANUAL, null, 'SECRET-'.$this->tag);
        $accountant = $this->userWithCapabilities(['view-sell-log', 'manage-gift-cards', 'search-customer-orders'], 'accountant');

        $this->actingAs($accountant, 'admin')
            ->get(route('manager.gift-cards.sell-log'))
            ->assertOk()
            ->assertSee('Accountant View '.$this->tag)
            ->assertDontSee('SECRET-'.$this->tag)
            ->assertViewHas('showCost', false);
    }

    public function test_main_sell_log_marks_next_load_rows(): void
    {
        $admin = $this->userWithRole('admin');
        $this->cardSale('Badge Buyer '.$this->tag, Card::SOURCE_NEXT_LOAD, $admin->id);

        $this->actingAs($admin, 'admin')
            ->get(route('manager.orders.search', ['search' => 'Badge Buyer '.$this->tag]))
            ->assertOk()
            ->assertSee('Badge Buyer '.$this->tag)
            ->assertSee('Next Load');
    }

    private function cardSale(string $buyerName, string $source, ?int $sellerId = null, ?string $code = null): Order
    {
        $card = Card::create([
            'code' => $code ?? 'GL-'.uniqid('', true),
            'cost' => 60,
            'card_category_id' => $this->category->id,
            'status' => false,
            'source' => $source,
        ]);

        return Order::create([
            'seller_id' => $sellerId,
            'store_profile_id' => $this->store->id,
            'buyer_phone' => '0102'.random_int(1000000, 9999999),
            'buyer_name' => $buyerName,
            'price' => 100,
            'notes' => '',
            'sold_item' => 'card',
            'card_id' => $card->id,
        ]);
    }

    private function accountSale(string $buyerName): Order
    {
        return Order::create([
            'store_profile_id' => $this->store->id,
            'buyer_phone' => '0103'.random_int(1000000, 9999999),
            'buyer_name' => $buyerName,
            'price' => 300,
            'notes' => '',
            'sold_item' => 'ps5_offline_stock',
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        return $this->userWithCapabilities(app(RolePermissionService::class)->defaultMatrix()[$roleName] ?? [], $roleName);
    }

    /** @param  list<string>  $capabilities */
    private function userWithCapabilities(array $capabilities, ?string $roleName = null): User
    {
        $role = Role::firstOrCreate(['name' => $roleName ?? 'gift log test '.$this->tag]);
        $role->capabilities = $capabilities;
        $role->save();
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }
}
