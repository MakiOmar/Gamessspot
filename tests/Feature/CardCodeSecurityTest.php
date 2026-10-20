<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\StoresProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Unsold gift card codes must never leak through public APIs and are stored encrypted.
 */
class CardCodeSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private CardCategory $category;

    private string $code;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->category = CardCategory::create(['name' => 'PSN Sec '.uniqid(), 'price' => 2500]);
        $this->code = 'SECRET-'.uniqid();
        Card::create(['code' => $this->code, 'cost' => 1999, 'card_category_id' => $this->category->id, 'status' => true]);
    }

    public function test_public_category_list_hides_codes_and_cost(): void
    {
        $response = $this->getJson('/api/card-ctegories/list')->assertOk();

        $this->assertStringNotContainsString($this->code, $response->getContent());
        $row = collect($response->json('data'))->firstWhere('id', $this->category->id);
        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('cards', $row);
        $this->assertSame(1, (int) $row['available_count']);
    }

    public function test_public_category_show_hides_codes(): void
    {
        $response = $this->getJson('/api/card-categories/'.$this->category->id)
            ->assertOk()
            ->assertJsonPath('data.cards_count', 1);

        $this->assertStringNotContainsString($this->code, $response->getContent());
    }

    public function test_code_is_encrypted_at_rest_with_hash(): void
    {
        $raw = DB::table('cards')->where('card_category_id', $this->category->id)->first();

        $this->assertNotSame($this->code, $raw->code);
        $this->assertSame(Card::hashCode($this->code), $raw->code_hash);
        $this->assertTrue(Card::codeExists($this->code));
        $this->assertSame($this->code, Card::where('code_hash', Card::hashCode($this->code))->first()->code);
    }

    public function test_sale_response_still_returns_plain_code(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_active' => true]));
        $store = StoresProfile::factory()->create();

        $this->postJson('/api/orders/receive', [
            'card_category_id' => $this->category->id,
            'store_profile_id' => $store->id,
            'buyer_phone' => '0108'.random_int(1000000, 9999999),
            'buyer_name' => 'Buyer',
            'price' => 2500,
            'storefront_order_id' => 'so-'.random_int(100000, 999999),
            'pos_transaction_id' => 9911,
        ])->assertCreated()
            ->assertJsonPath('code', $this->code)
            ->assertJsonPath('card_details.code', $this->code);
    }
}
