<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\NextLoad\NextLoadCatalog;
use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadSyncService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Generated gift card artwork (card_art) for categories without a poster.
 */
class CardArtTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE = 'https://nl.test/APIS/V1/';

    private string $productName = 'PSN US 10$';

    protected function setUp(): void
    {
        parent::setUp();
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

        Http::fake(function ($request) {
            return match (substr($request->url(), strlen(self::BASE))) {
                'Token' => Http::response(['status' => 'Success', 'token' => 'tok']),
                'ProductsList' => Http::response(['status' => 'Success', 'data' => [
                    ['product_id' => 352, 'product' => $this->productName, 'price' => '2.500'],
                ]]),
                default => Http::response(['status' => 'Error', 'message' => 'unknown'], 404),
            };
        });
    }

    public function test_api_returns_card_art_only_without_a_poster(): void
    {
        $plain = $this->categoryWithStock('Amazon UK 10 £');
        $poster = $this->categoryWithStock('Amazon UK 25 £', 'images/posters/demo.webp');

        $rows = collect($this->getJson('/api/card-ctegories/list')->assertOk()->json('data'))->keyBy('id');
        $art = $rows[$plain->id]['card_art'];
        $this->assertSame('amazon', $art['brand']);
        $this->assertSame('Amazon', $art['brand_label']);
        $this->assertSame('£10', $art['value']);
        $this->assertSame('gb', $art['country_code']);
        $this->assertStringEndsWith('/images/card-art/flags/gb.svg', $art['flag_url']);
        $this->assertStringEndsWith('/images/card-art/brands/amazon.svg', $art['logo_url']);
        $this->assertStringStartsWith('http', $art['flag_url']);
        $this->assertNull($rows[$poster->id]['card_art']);
        $this->assertArrayNotHasKey('art_brand', $rows[$plain->id]);
        $this->assertArrayNotHasKey('supplier_product_name', $rows[$plain->id]);

        $this->getJson('/api/card-categories/'.$plain->id)->assertOk()->assertJsonPath('data.card_art.value', '£10');
        $this->getJson('/api/card-categories/'.$poster->id)->assertOk()->assertJsonPath('data.card_art', null);
    }

    public function test_overrides_and_supplier_name_win_over_the_category_name(): void
    {
        $category = $this->categoryWithStock('Gift card A');
        $this->getJson('/api/card-categories/'.$category->id)->assertJsonPath('data.card_art', null);

        $category->forceFill(['supplier_product_name' => 'amazon KSA 100 SAR', 'art_country' => 'ae', 'art_value' => '100 AED'])->save();

        $this->getJson('/api/card-categories/'.$category->id)
            ->assertJsonPath('data.card_art.brand', 'amazon')
            ->assertJsonPath('data.card_art.country_code', 'ae')
            ->assertJsonPath('data.card_art.value', '100 AED');
    }

    public function test_linking_and_sync_store_the_supplier_product_name(): void
    {
        $category = CardCategory::create(['name' => 'Art link '.uniqid(), 'price' => 100]);
        $this->actingAs($this->userWithRole('admin'), 'admin');

        $this->postJson(route('manager.next-load.links.store'), [
            'next_load_product_id' => 352,
            'supplier_mode' => 'local_first',
            'card_category_id' => $category->id,
            'art_brand' => 'xbox',
            'art_value' => ' $10 ',
        ])->assertOk();
        $category->refresh();
        $this->assertSame('PSN US 10$', $category->supplier_product_name);
        $this->assertSame('xbox', $category->art_brand);
        $this->assertSame('$10', $category->art_value);
        $this->assertNull($category->art_country);

        $this->productName = 'PSN US 20$';
        Cache::forget(NextLoadClient::PRODUCTS_CACHE_KEY);
        app(NextLoadSyncService::class)->syncProducts();
        $this->assertSame('PSN US 20$', $category->refresh()->supplier_product_name);
    }

    public function test_override_validation(): void
    {
        $category = CardCategory::create(['name' => 'Art invalid '.uniqid(), 'price' => 100, 'next_load_product_id' => 352, 'supplier_mode' => 'local_first']);
        $this->actingAs($this->userWithRole('admin'), 'admin');

        $this->putJson(route('manager.next-load.links.update', $category), [
            'next_load_product_id' => 352,
            'supplier_mode' => 'local_first',
            'art_brand' => 'nokia',
            'art_country' => 'zz',
            'art_value' => str_repeat('9', 21),
        ])->assertStatus(422)->assertJsonValidationErrors(['art_brand', 'art_country', 'art_value']);
    }

    public function test_preview_endpoint_renders_the_component(): void
    {
        $this->actingAs($this->userWithRole('admin'), 'admin');

        $this->getJson(route('manager.next-load.links.art-preview', ['name' => 'amazon KSA 100 SAR']))
            ->assertOk()
            ->assertJsonPath('card_art.country_code', 'sa')
            ->assertJsonPath('card_art.value', '100 SAR');
        $html = $this->getJson(route('manager.next-load.links.art-preview', ['name' => 'amazon KSA 100 SAR']))->json('html');
        $this->assertStringContainsString('gift-card-art', $html);

        $this->getJson(route('manager.next-load.links.art-preview', ['name' => 'Test']))
            ->assertOk()
            ->assertJsonPath('html', null);

        $this->actingAs($this->userWithRole('sales'), 'admin')
            ->getJson(route('manager.next-load.links.art-preview', ['name' => 'Test']))
            ->assertForbidden();
    }

    public function test_sell_screen_renders_the_artwork(): void
    {
        $this->categoryWithStock('Steam 20 EUR');

        $this->actingAs($this->userWithRole('admin'), 'admin')
            ->get(route('manager.sell-cards', ['all' => 1]))
            ->assertOk()
            ->assertSee('gift-card-art__value', false)
            ->assertSee('€20');
    }

    public function test_api_returns_classification_even_with_a_poster(): void
    {
        $poster = $this->categoryWithStock('Amazon KSA 50 SAR', 'images/posters/demo.webp');

        $row = collect($this->getJson('/api/card-ctegories/list')->assertOk()->json('data'))->firstWhere('id', $poster->id);
        $this->assertSame('amazon', $row['classification']['brand']);
        $this->assertSame('sa', $row['classification']['country']);
        $this->assertSame('Saudi Arabia', $row['classification']['country_label']);

        $this->getJson('/api/card-categories/'.$poster->id)
            ->assertOk()
            ->assertJsonPath('data.classification.brand', 'amazon')
            ->assertJsonPath('data.classification.country', 'sa');
    }

    public function test_sell_screen_shows_brand_tiles_and_country_modal(): void
    {
        $this->categoryWithStock('Amazon KSA 100 SAR');
        $this->categoryWithStock('Amazon UK 10 £');
        $this->categoryWithStock('Steam 20 EUR');

        $this->actingAs($this->userWithRole('admin'), 'admin')
            ->get(route('manager.sell-cards'))
            ->assertOk()
            ->assertSee('Choose a brand')
            ->assertSee('data-target="#brandModal-amazon"', false)
            ->assertSee('id="brandModal-amazon"', false)
            ->assertSee('brand=amazon&amp;country=sa', false)
            // Steam is sold in one country: the tile links straight to its cards.
            ->assertSee('brand=steam&amp;country=eu', false)
            ->assertDontSee('id="brandModal-steam"', false)
            ->assertDontSee('Order Code');
    }

    public function test_sell_screen_filters_by_brand_and_country(): void
    {
        $sa = $this->categoryWithStock('Amazon KSA 100 SAR');
        $uk = $this->categoryWithStock('Amazon UK 10 £');
        $steam = $this->categoryWithStock('Steam 20 EUR');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin, 'admin')
            ->get(route('manager.sell-cards', ['brand' => 'amazon', 'country' => 'sa']))
            ->assertOk()
            ->assertSee($sa->name)
            ->assertDontSee($uk->name)
            ->assertDontSee($steam->name)
            ->assertSee('Saudi Arabia')
            ->assertSee('Change country');

        // A single-country brand resolves its country; unknown keys fall back to the tiles.
        $this->actingAs($admin, 'admin')
            ->get(route('manager.sell-cards', ['brand' => 'steam']))
            ->assertOk()
            ->assertSee($steam->name)
            ->assertDontSee($sa->name)
            ->assertDontSee('Change country');
        $this->actingAs($admin, 'admin')
            ->get(route('manager.sell-cards', ['brand' => 'nokia', 'country' => 'zz']))
            ->assertOk()
            ->assertSee('Choose a brand');
    }

    public function test_sell_search_and_all_cards_show_the_full_grid(): void
    {
        $sa = $this->categoryWithStock('Amazon KSA 100 SAR');
        $steam = $this->categoryWithStock('Steam 20 EUR');
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin, 'admin')
            ->get(route('manager.sell-cards.search', ['q' => $steam->name]))
            ->assertOk()
            ->assertSee($steam->name)
            ->assertDontSee($sa->name)
            ->assertDontSee('Choose a brand');

        $this->actingAs($admin, 'admin')
            ->get(route('manager.sell-cards', ['all' => 1]))
            ->assertOk()
            ->assertSee($sa->name)
            ->assertSee($steam->name)
            ->assertDontSee('Choose a brand');
    }

    public function test_categories_form_saves_and_returns_the_overrides(): void
    {
        $admin = $this->userWithRole('admin');
        $name = 'Art form '.uniqid();

        $this->actingAs($admin, 'admin')
            ->get(route('card-categories.index'))
            ->assertOk()
            ->assertSee('Brand and country')
            ->assertSee('<option value="sa">Saudi Arabia (SA)</option>', false);

        $this->actingAs($admin, 'admin')->postJson(route('card-categories.store'), [
            'name' => $name,
            'price' => 100,
            'art_brand' => 'amazon',
            'art_country' => 'sa',
            'art_value' => ' 100 SAR ',
        ])->assertOk()->assertJsonPath('success', true);
        $category = CardCategory::where('name', $name)->firstOrFail();
        $this->assertSame(['amazon', 'sa', '100 SAR'], [$category->art_brand, $category->art_country, $category->art_value]);

        $this->actingAs($admin, 'admin')->getJson(route('card-categories.edit', $category))
            ->assertOk()
            ->assertJsonPath('art_brand', 'amazon')
            ->assertJsonPath('art_country', 'sa')
            ->assertJsonPath('art_value', '100 SAR');

        // Back to "Automatic" clears the overrides.
        $this->actingAs($admin, 'admin')->putJson(route('card-categories.update', $category), [
            'name' => $name,
            'price' => 100,
            'art_brand' => '',
            'art_country' => '',
            'art_value' => '',
        ])->assertOk();
        $category->refresh();
        $this->assertNull($category->art_brand);
        $this->assertNull($category->art_country);
        $this->assertNull($category->art_value);
    }

    public function test_categories_form_rejects_unknown_overrides(): void
    {
        $this->actingAs($this->userWithRole('admin'), 'admin')->postJson(route('card-categories.store'), [
            'name' => 'Art bad '.uniqid(),
            'price' => 100,
            'art_brand' => 'nokia',
            'art_country' => 'zz',
            'art_value' => str_repeat('9', 21),
        ])->assertStatus(422)->assertJsonValidationErrors(['art_brand', 'art_country', 'art_value']);
    }

    private function categoryWithStock(string $name, ?string $poster = null): CardCategory
    {
        $category = CardCategory::create(['name' => 'T'.uniqid().' '.$name, 'price' => 100, 'poster_image' => $poster]);
        Card::create(['code' => 'ART-'.uniqid(), 'cost' => 50, 'card_category_id' => $category->id, 'status' => true]);

        return $category;
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
