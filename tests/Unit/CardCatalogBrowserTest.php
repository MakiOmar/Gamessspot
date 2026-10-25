<?php

namespace Tests\Unit;

use App\Models\CardCategory;
use App\Services\CardArt\CardArtPresenter;
use App\Services\CardArt\CardCatalogBrowser;
use Tests\TestCase;

/**
 * Brand/country classification and grouping used by the gift card browsing pages.
 */
class CardCatalogBrowserTest extends TestCase
{
    public function test_classify_reads_brand_and_country_from_the_name(): void
    {
        $classification = $this->classify('Amazon KSA 100 SAR');

        $this->assertSame('amazon', $classification['brand']);
        $this->assertSame('Amazon', $classification['brand_label']);
        $this->assertSame('sa', $classification['country']);
        $this->assertSame('Saudi Arabia', $classification['country_label']);
        $this->assertStringEndsWith('/images/card-art/flags/sa.svg', $classification['flag_url']);
        $this->assertStringEndsWith('/images/card-art/brands/amazon.svg', $classification['logo_url']);
    }

    public function test_classify_is_present_with_a_poster_and_without_a_face_value(): void
    {
        $classification = $this->classify('PSN US gift card', ['poster_image' => 'images/posters/demo.webp']);

        $this->assertSame('playstation', $classification['brand']);
        $this->assertSame('us', $classification['country']);
    }

    public function test_classify_groups_unknown_brands_global_and_missing_countries(): void
    {
        $global = $this->classify('Razer Gold Global 25 USD');
        $this->assertSame('other', $global['brand']);
        $this->assertSame('Other', $global['brand_label']);
        $this->assertNull($global['logo_url']);
        $this->assertSame('global', $global['country']);
        $this->assertSame('Global', $global['country_label']);
        $this->assertStringEndsWith('/images/card-art/flags/globe.svg', $global['flag_url']);

        $none = $this->classify('Test');
        $this->assertSame('other', $none['country']);
        $this->assertSame('Other', $none['country_label']);
        $this->assertNull($none['flag_url']);
    }

    public function test_classify_honours_overrides_and_the_supplier_name(): void
    {
        $classification = $this->classify('Gift card A', [
            'supplier_product_name' => 'amazon KSA 100 SAR',
            'art_brand' => 'xbox',
            'art_country' => 'ae',
        ]);

        $this->assertSame('xbox', $classification['brand']);
        $this->assertSame('ae', $classification['country']);
    }

    public function test_groups_sort_brands_and_countries_and_filter_narrows(): void
    {
        $categories = collect([
            'Amazon KSA 100 SAR', 'Amazon KSA 50 SAR', 'Amazon UK 10 £', 'Amazon Global 10 USD', 'Amazon gift',
            'PSN US $10', 'PSN US $20', 'PSN US $50', 'Razer Gold 10 USD',
        ])->map(fn (string $name) => $this->category($name));

        $groups = app(CardCatalogBrowser::class)->groups($categories);

        $this->assertSame(['amazon', 'playstation', 'other'], array_column($groups, 'brand'));
        $this->assertSame([5, 3, 1], array_column($groups, 'count'));
        $this->assertSame(['sa', 'gb', 'global', 'other'], array_column($groups[0]['countries'], 'key'));
        $this->assertSame([2, 1, 1, 1], array_column($groups[0]['countries'], 'count'));
        $this->assertSame('Saudi Arabia', $groups[0]['countries'][0]['label']);

        $browser = app(CardCatalogBrowser::class);
        $this->assertCount(2, $browser->filter($categories, 'amazon', 'sa'));
        $this->assertCount(5, $browser->filter($categories, 'amazon', null));
        $this->assertCount(0, $browser->filter($categories, 'steam', null));
    }

    /** @param  array<string, mixed>  $attributes */
    private function classify(string $name, array $attributes = []): array
    {
        return app(CardArtPresenter::class)->classify($this->model($name, $attributes));
    }

    private function category(string $name): CardCategory
    {
        $category = $this->model($name);
        $category->setAttribute('classification', app(CardArtPresenter::class)->classify($category));

        return $category;
    }

    /** @param  array<string, mixed>  $attributes */
    private function model(string $name, array $attributes = []): CardCategory
    {
        return (new CardCategory)->forceFill(['name' => $name, ...$attributes]);
    }
}
