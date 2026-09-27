<?php

namespace App\Services;

use App\Models\CardCategory;
use App\Models\Game;
use App\Models\GamePosProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Mirrors each sellable game offer (Avatar Primary PS5, …) and card category as a POS product,
 * then stores the returned product/variation ids so sales and the shop can reference them.
 */
class PosCatalogSync
{
    public const PLATFORMS = ['ps4', 'ps5'];

    public const OFFERS = ['primary', 'secondary', 'offline', 'full'];

    private const TOKEN_CACHE_KEY = 'api_token';

    private const NAME_MAX = 191;

    public static function enabled(): bool
    {
        return (bool) config('services.pos_catalog.enabled', true);
    }

    public static function gameSku(int $gameId, string $platform, string $offer): string
    {
        return 'ACCOUNTS-GAME-'.$gameId.'-'.strtoupper($platform).'-'.strtoupper($offer);
    }

    public static function cardSku(int $categoryId): string
    {
        return 'ACCOUNTS-CARD-'.$categoryId;
    }

    /**
     * Offers to push: every offer the game sells, plus previously linked offers so POS can hide them.
     *
     * @return list<array{platform:string,offer:string,sku:string,name:string,price:float,active:bool}>
     */
    public function offersForGame(Game $game): array
    {
        $linked = $game->posProducts()->get()->keyBy(fn (GamePosProduct $row) => $row->platform.'|'.$row->offer);
        $items = [];

        foreach (self::PLATFORMS as $platform) {
            foreach (self::OFFERS as $offer) {
                $price = $offer === 'full'
                    ? (float) ($game->full_price ?? 0)
                    : (float) ($game->{$platform.'_'.$offer.'_price'} ?? 0);
                $active = $offer === 'full'
                    ? $price > 0
                    : (bool) $game->{$platform.'_'.$offer.'_status'};

                if (! $active && ! $linked->has($platform.'|'.$offer)) {
                    continue;
                }

                $items[] = [
                    'platform' => $platform,
                    'offer' => $offer,
                    'sku' => self::gameSku((int) $game->id, $platform, $offer),
                    'name' => self::offerName((string) $game->title, $platform, $offer),
                    'price' => max(0, $price),
                    'active' => $active,
                ];
            }
        }

        return $items;
    }

    public static function offerName(string $title, string $platform, string $offer): string
    {
        $suffix = ' '.ucfirst($offer).' '.strtoupper($platform);
        $title = trim($title);

        return mb_substr($title, 0, self::NAME_MAX - mb_strlen($suffix)).$suffix;
    }

    public function pushGame(Game $game): void
    {
        $offers = $this->offersForGame($game);
        if ($offers === []) {
            return;
        }

        $results = $this->upsert('game', $offers, (string) $game->code);
        foreach ($offers as $offer) {
            $result = $results[$offer['sku']] ?? null;
            if (! $result) {
                continue;
            }
            GamePosProduct::updateOrCreate(
                ['game_id' => $game->id, 'platform' => $offer['platform'], 'offer' => $offer['offer']],
                [
                    'pos_product_id' => $result['product_id'],
                    'pos_variation_id' => $result['variation_id'],
                    'pos_active' => $offer['active'],
                ]
            );
        }
    }

    /**
     * Hide every offer of a deleted game in POS; link rows are already gone with the game.
     */
    public function deactivateGame(int $gameId, string $title, ?string $code = null): void
    {
        $items = [];
        foreach (self::PLATFORMS as $platform) {
            foreach (self::OFFERS as $offer) {
                $items[] = [
                    'sku' => self::gameSku($gameId, $platform, $offer),
                    'name' => self::offerName($title, $platform, $offer),
                    'price' => 0,
                    'active' => false,
                ];
            }
        }

        $this->upsert('game', $items, $code, onlyExisting: true);
    }

    public function pushCardCategory(CardCategory $category, bool $active = true): void
    {
        $sku = self::cardSku((int) $category->id);
        $results = $this->upsert('card', [[
            'sku' => $sku,
            'name' => mb_substr(trim((string) $category->name), 0, self::NAME_MAX),
            'price' => max(0, (float) $category->price),
            'active' => $active,
        ]], onlyExisting: ! $category->exists);

        $result = $results[$sku] ?? null;
        if (! $result || ! $category->exists) {
            return;
        }

        // Avoid re-firing the observer (and another sync) for link-only columns.
        CardCategory::withoutEvents(function () use ($category, $result, $active) {
            $category->forceFill([
                'pos_product_id' => $result['product_id'],
                'pos_variation_id' => $result['variation_id'],
                'pos_active' => $active,
            ])->saveQuietly();
        });
    }

    /**
     * @param  list<array{sku:string,name:string,price:float|int,active:bool}>  $items
     * @return array<string, array{product_id:int,variation_id:int}>
     */
    private function upsert(string $kind, array $items, ?string $code = null, bool $onlyExisting = false): array
    {
        $payload = [
            'kind' => $kind,
            'code' => $code !== null && $code !== '' ? mb_substr($code, 0, 64) : null,
            'items' => array_map(fn (array $item) => [
                'sku' => $item['sku'],
                'name' => $item['name'],
                'price' => round((float) $item['price'], 2),
                'active' => (bool) $item['active'],
            ], $items),
            'only_existing' => $onlyExisting,
        ];

        $response = $this->post($payload);
        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->post($payload);
        }

        if (! $response->successful() || ! $response->json('success')) {
            throw new RuntimeException('POS catalog upsert failed with status '.$response->status().'.');
        }

        $out = [];
        foreach ((array) $response->json('items', []) as $row) {
            if (! empty($row['sku']) && ! empty($row['product_id']) && ! empty($row['variation_id'])) {
                $out[(string) $row['sku']] = [
                    'product_id' => (int) $row['product_id'],
                    'variation_id' => (int) $row['variation_id'],
                ];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(array $payload): \Illuminate\Http\Client\Response
    {
        $base = rtrim(SettingsService::getPosBaseUrl(), '/');
        $businessId = (int) config('services.pos_catalog.business_id', 1);

        return Http::acceptJson()
            ->timeout((int) config('services.pos_catalog.timeout', 20))
            ->withToken($this->token($base))
            ->post($base.'/api/accounts/catalog/upsert/'.$businessId, $payload);
    }

    /**
     * Same cached POS login token OrderController::login() uses.
     */
    private function token(string $base): string
    {
        $token = Cache::get(self::TOKEN_CACHE_KEY);
        if ($token) {
            return (string) $token;
        }

        $response = Http::acceptJson()
            ->timeout((int) config('services.pos_catalog.timeout', 20))
            ->post($base.'/api/login', [
                'username' => SettingsService::getPosUsername(),
                'password' => SettingsService::getPosPassword(),
            ]);

        $token = $response->successful() ? $response->json('token') : null;
        if (! $token) {
            throw new RuntimeException('POS login failed with status '.$response->status().'.');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addDays(3));

        return (string) $token;
    }
}
