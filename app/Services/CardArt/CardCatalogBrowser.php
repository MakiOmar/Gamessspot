<?php

namespace App\Services\CardArt;

use Illuminate\Support\Collection;

/**
 * Groups sellable card categories by brand, then country, for the "brand -> country -> cards" browsing.
 * Each category must carry its `classification` (CardArtPresenter::classify).
 */
final class CardCatalogBrowser
{
    /**
     * @param  Collection<int, object>  $categories
     * @return list<array{brand: string, label: string, logo_url: ?string, color: string, count: int, countries: list<array{key: string, label: string, flag_url: ?string, count: int}>}>
     */
    public function groups(Collection $categories): array
    {
        return $categories
            ->groupBy(fn ($category) => $this->classification($category)['brand'])
            ->map(function (Collection $items, string $brand) {
                $first = $this->classification($items->first());

                return [
                    'brand' => $brand,
                    'label' => $first['brand_label'],
                    'logo_url' => $first['logo_url'],
                    'color' => $first['color'],
                    'count' => $items->count(),
                    'countries' => $this->countries($items),
                ];
            })
            ->sortBy(fn (array $group) => [$group['brand'] === CardArtCatalog::OTHER_BRAND ? 1 : 0, -$group['count'], $group['label']])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object>  $categories
     * @return Collection<int, object>
     */
    public function filter(Collection $categories, ?string $brand, ?string $country): Collection
    {
        return $categories
            ->filter(function ($category) use ($brand, $country) {
                $classification = $this->classification($category);

                return ($brand === null || $classification['brand'] === $brand)
                    && ($country === null || $classification['country'] === $country);
            })
            ->values();
    }

    /**
     * @param  Collection<int, object>  $items
     * @return list<array{key: string, label: string, flag_url: ?string, count: int}>
     */
    private function countries(Collection $items): array
    {
        $last = [CardArtCatalog::GLOBAL_COUNTRY => 1, CardArtCatalog::OTHER_COUNTRY => 2];

        return $items
            ->groupBy(fn ($category) => $this->classification($category)['country'])
            ->map(function (Collection $rows, string $key) {
                $first = $this->classification($rows->first());

                return ['key' => $key, 'label' => $first['country_label'], 'flag_url' => $first['flag_url'], 'count' => $rows->count()];
            })
            ->sortBy(fn (array $country) => [$last[$country['key']] ?? 0, -$country['count'], $country['label']])
            ->values()
            ->all();
    }

    /**
     * @return array{brand: string, brand_label: string, logo_url: ?string, color: string, country: string, country_label: string, flag_url: ?string}
     */
    private function classification(object $category): array
    {
        return $category->classification;
    }
}
