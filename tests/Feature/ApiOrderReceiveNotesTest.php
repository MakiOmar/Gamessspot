<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Game;
use App\Models\StoresProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * POST api/orders/receive: POS call-center sales orders send `notes` for agent attribution.
 */
class ApiOrderReceiveNotesTest extends TestCase
{
    use DatabaseTransactions;

    private StoresProfile $store;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Sanctum::actingAs(User::factory()->create(['is_active' => true]));
        $this->store = StoresProfile::factory()->create();
    }

    public function test_game_sale_saves_notes_and_stays_idempotent(): void
    {
        $game = Game::factory()->create(['ps5_secondary_status' => true]);
        $account = Account::factory()->create(['game_id' => $game->id, 'ps5_secondary_stock' => 2]);
        $payload = [
            'store_profile_id' => $this->store->id,
            'game_id' => $game->id,
            'buyer_phone' => '0108'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer',
            'buyer_email' => 'buyer@example.com',
            'price' => 500,
            'type' => 'secondary',
            'platform' => '5',
            'storefront_order_id' => 'so-'.random_int(100000, 999999),
            'storefront_line_key' => 'ps5_secondary_stock|game:'.$game->id.'|line:1|n:1',
            'pos_transaction_id' => 4242,
            'notes' => 'POS sales order SO2026/0012 / Agent One',
        ];

        $this->postJson('/api/orders/receive', $payload)
            ->assertCreated()
            ->assertJsonPath('account_details.email', $account->mail);

        $this->assertDatabaseHas('orders', [
            'storefront_order_id' => $payload['storefront_order_id'],
            'notes' => 'POS sales order SO2026/0012 / Agent One',
            'pos_order_id' => '4242',
        ]);

        $this->postJson('/api/orders/receive', $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);
        $this->assertSame(1, (int) $account->fresh()->ps5_secondary_stock);
    }

    public function test_card_sale_saves_notes(): void
    {
        $category = CardCategory::create(['name' => 'PSN 50', 'price' => 2500]);
        Card::create(['code' => 'CODE-'.random_int(1000, 9999), 'cost' => 2000, 'card_category_id' => $category->id, 'status' => true]);

        $this->postJson('/api/orders/receive', [
            'card_category_id' => $category->id,
            'store_profile_id' => $this->store->id,
            'buyer_phone' => '0108'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer',
            'price' => 2500,
            'storefront_order_id' => 'so-'.random_int(100000, 999999),
            'pos_transaction_id' => 4243,
            'notes' => 'POS sales order SO2026/0013',
        ])->assertSuccessful();

        $this->assertDatabaseHas('orders', ['notes' => 'POS sales order SO2026/0013', 'sold_item' => 'card']);
    }

    public function test_offline_type_is_still_rejected(): void
    {
        $game = Game::factory()->create();

        $this->postJson('/api/orders/receive', [
            'store_profile_id' => $this->store->id,
            'game_id' => $game->id,
            'buyer_phone' => '0108'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer',
            'buyer_email' => 'buyer@example.com',
            'price' => 500,
            'type' => 'offline',
            'platform' => '5',
            'storefront_order_id' => 'so-1',
            'pos_transaction_id' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('type');
    }
}
