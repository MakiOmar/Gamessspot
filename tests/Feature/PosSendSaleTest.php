<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Game;
use App\Models\GamePosProduct;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\PosSaleProductResolver;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sending sold orders to POS references the per-offer / per-category POS product for each order.
 */
class PosSendSaleTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('api_token', 'test-token', 60);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
    }

    private function linkOffer(Game $game, string $platform, string $offer, int $productId, bool $active = true): void
    {
        GamePosProduct::create([
            'game_id' => $game->id, 'platform' => $platform, 'offer' => $offer,
            'pos_product_id' => $productId, 'pos_variation_id' => $productId + 1000, 'pos_active' => $active,
        ]);
    }

    private function gameOrder(Game $game, string $soldItem, string $phone, string $mail): Order
    {
        $account = Account::factory()->create(['game_id' => $game->id, 'mail' => $mail]);

        return Order::factory()->create([
            'seller_id' => $this->admin->id,
            'account_id' => $account->id,
            'buyer_phone' => $phone,
            'price' => 800,
            'sold_item' => $soldItem,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function metaOf(array $line): array
    {
        return collect($line['meta_data'])->pluck('value', 'key')->all();
    }

    public function test_each_order_is_sent_as_its_own_offer_product_line(): void
    {
        $phone = '0109'.random_int(1000000, 9999999);
        User::factory()->create(['phone' => $phone]);
        $avatar = Game::factory()->create(['title' => 'Avatar']);
        $gow = Game::factory()->create(['title' => 'God of War']);
        $this->linkOffer($avatar, 'ps5', 'primary', 901);
        // Offer switched off after the sale still maps to its product.
        $this->linkOffer($gow, 'ps5', 'primary', 902, active: false);

        $category = CardCategory::create(['name' => 'PSN 50', 'price' => 2500]);
        $category->forceFill(['pos_product_id' => 950, 'pos_variation_id' => 1950, 'pos_active' => true])->saveQuietly();
        $card = Card::create(['code' => 'CODE-1', 'cost' => 2000, 'card_category_id' => $category->id, 'status' => false]);

        $orders = [
            $this->gameOrder($avatar, 'ps5_primary_stock', $phone, 'a@example.com'),
            $this->gameOrder($gow, 'ps5_primary_stock', $phone, 'g@example.com'),
            $this->gameOrder($avatar, 'ps4_offline_stock', $phone, 'o@example.com'),
            Order::factory()->create([
                'seller_id' => $this->admin->id, 'account_id' => null, 'card_id' => $card->id,
                'buyer_phone' => $phone, 'price' => 2500, 'sold_item' => 'card',
            ]),
        ];

        Http::fake(['*/api/accounts/orders/create/*' => Http::response(['created' => ['id' => 777]])]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('manager.orders.sendToPos'), ['order_ids' => array_map(fn ($o) => $o->id, $orders)])
            ->assertRedirect(route('manager.orders'))
            ->assertSessionHas('success');

        Http::assertSent(function (Request $request) use ($avatar, $gow, $category) {
            $lines = $request['line_items'];
            if (count($lines) !== 4) {
                return false;
            }
            [$avatarLine, $gowLine, $fallbackLine, $cardLine] = array_map(fn ($l) => $this->metaOf($l), $lines);

            return $avatarLine['_pos_product_id'] === 901 && $avatarLine['game_title'] === 'Avatar'
                && $avatarLine['_account'] === 'a@example.com' && $avatarLine['platform'] === 'ps5'
                && $lines[0]['sku'] === "ACCOUNTS-GAME-{$avatar->id}-PS5-PRIMARY"
                && $gowLine['_pos_product_id'] === 902 && $gowLine['game_title'] === 'God of War'
                && (string) $fallbackLine['_pos_product_id'] === (string) SettingsService::getPosIds()['offline']
                && $lines[2]['sku'] === (string) SettingsService::getPosSkus()['offline'] && $fallbackLine['type'] === 'offline'
                && $cardLine['_pos_product_id'] === 950 && $lines[3]['sku'] === 'ACCOUNTS-CARD-'.$category->id
                && collect($lines)->every(fn ($l) => $l['quantity'] === 1);
        });

        foreach ($orders as $order) {
            $this->assertSame(777, (int) $order->fresh()->pos_order_id);
        }
    }

    public function test_sold_item_parsing_covers_full_and_slot_offers(): void
    {
        $this->assertSame(['ps5', 'primary'], PosSaleProductResolver::parseSoldItem('ps5_primary_stock'));
        $this->assertSame(['ps4', 'full'], PosSaleProductResolver::parseSoldItem('ps4_full'));
        $this->assertSame(['ps4', 'offline'], PosSaleProductResolver::parseSoldItem('ps4_offline_stock'));
    }
}
