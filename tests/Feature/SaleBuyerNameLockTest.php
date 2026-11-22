<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Game;
use App\Models\Order;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\RolePermissionService;
use App\Support\PhoneUserResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Known buyer phones keep the stored customer name on account and gift-card sales.
 */
class SaleBuyerNameLockTest extends TestCase
{
    use DatabaseTransactions;

    private string $tag;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->withoutVite();
        $this->tag = uniqid();
    }

    public function test_sale_buyer_name_helper_prefers_stored_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Stored Name '.$this->tag,
            'phone' => '+2011'.random_int(10000000, 99999999),
        ]);

        $this->assertSame(
            $user->name,
            PhoneUserResolver::saleBuyerName($user->phone, 'Forged Name')
        );
        $this->assertSame(
            'Brand New Buyer',
            PhoneUserResolver::saleBuyerName('+2099'.random_int(10000000, 99999999), 'Brand New Buyer')
        );
    }

    public function test_account_store_ignores_forged_buyer_name_for_known_phone(): void
    {
        $customer = User::factory()->create([
            'name' => 'Real Customer '.$this->tag,
            'phone' => '+2012'.random_int(10000000, 99999999),
            'is_active' => 1,
        ]);
        $game = Game::factory()->create();
        $account = Account::factory()->create([
            'game_id' => $game->id,
            'ps5_secondary_stock' => 1,
        ]);
        $store = StoresProfile::factory()->create();
        $seller = $this->staffSeller();

        $this->actingAs($seller, 'admin')
            ->postJson(route('orders.store'), [
                'store_profile_id' => $store->id,
                'game_id' => $game->id,
                'buyer_phone' => $customer->phone,
                'buyer_name' => 'Forged Alias '.$this->tag,
                'price' => 500,
                'type' => 'secondary',
                'platform' => '5',
            ])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'account_id' => $account->id,
            'buyer_phone' => $customer->phone,
            'buyer_name' => $customer->name,
        ]);
        $this->assertDatabaseMissing('orders', [
            'account_id' => $account->id,
            'buyer_name' => 'Forged Alias '.$this->tag,
        ]);
    }

    public function test_sell_card_ignores_forged_buyer_name_for_known_phone(): void
    {
        $customer = User::factory()->create([
            'name' => 'Card Customer '.$this->tag,
            'phone' => '+2015'.random_int(10000000, 99999999),
            'is_active' => 1,
        ]);
        $store = StoresProfile::factory()->create();
        $category = CardCategory::create([
            'name' => 'Name Lock '.$this->tag,
            'price' => 100,
            'supplier_mode' => CardCategory::MODE_LOCAL_ONLY,
        ]);
        $card = Card::create([
            'code' => 'NL-'.$this->tag,
            'cost' => 40,
            'card_category_id' => $category->id,
            'status' => true,
            'source' => Card::SOURCE_MANUAL,
        ]);
        $seller = $this->staffSeller();

        $this->actingAs($seller, 'admin')
            ->postJson(route('manager.orders.sell.card'), [
                'card_category_id' => $category->id,
                'store_profile_id' => $store->id,
                'buyer_phone' => $customer->phone,
                'buyer_name' => 'Forged Card Buyer',
                'price' => 50,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('orders', [
            'card_id' => $card->id,
            'buyer_phone' => $customer->phone,
            'buyer_name' => $customer->name,
        ]);
    }

    private function staffSeller(): User
    {
        $caps = app(RolePermissionService::class)->defaultMatrix()['admin'];
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->capabilities = $caps;
        $role->save();

        $user = User::factory()->create([
            'email' => 'seller_'.$this->tag.'_'.uniqid().'@example.com',
            'is_active' => 1,
            'password' => Hash::make('password'),
        ]);
        $user->roles()->sync([$role->id]);

        return $user->fresh()->load('roles');
    }
}
