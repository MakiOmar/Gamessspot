<?php

namespace App\Services;

use App\Models\CardCategory;
use App\Models\Game;
use App\Models\GamePosProduct;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
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

    /** POS rejects upsert batches larger than this. */
    public const BATCH_MAX = 32;

    /** 429 = POS rate limit, 409 = POS catalog lock busy; both clear up on their own. */
    private const RETRYABLE_STATUSES = [409, 429];

    /** Off by default so inline manager saves never wait; queued jobs use their own backoff. */
    private int $maxRetries = 0;

    /**
     * Wait out 409/429 responses up to POS_CATALOG_SYNC_MAX_RETRIES times (for long backfills).
     */
    public function withRetries(): static
    {
        $this->maxRetries = max(0, (int) config('services.pos_catalog.max_retries', 5));

        return $this;
    }

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

    /**
     * @return bool false when the game has nothing to push (no sold or previously linked offers)
     */
    public function pushGame(Game $game): bool
    {
        $offers = $this->offersForGame($game);
        if ($offers === []) {
            return false;
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

        return true;
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
        $item = $this->cardItem($category, $active);
        $results = $this->upsert('card', [$item], onlyExisting: ! $category->exists);

        if ($category->exists && isset($results[$item['sku']])) {
            $this->storeCardLink($category, $results[$item['sku']], $active);
        }
    }

    /**
     * Backfill many active card categories with one POS request per BATCH_MAX categories.
     *
     * @param  iterable<CardCategory>  $categories
     * @return int number of categories pushed
     */
    public function pushCardCategories(iterable $categories): int
    {
        $pushed = 0;

        foreach (collect($categories)->chunk(self::BATCH_MAX) as $chunk) {
            $items = $chunk->map(fn (CardCategory $category) => $this->cardItem($category, true))->values()->all();
            $results = $this->upsert('card', $items);

            foreach ($chunk as $category) {
                $result = $results[self::cardSku((int) $category->id)] ?? null;
                if ($result) {
                    $this->storeCardLink($category, $result, true);
                }
            }
            $pushed += $chunk->count();
        }

        return $pushed;
    }

    /**
     * @return array{sku:string,name:string,price:float,active:bool}
     */
    private function cardItem(CardCategory $category, bool $active): array
    {
        return [
            'sku' => self::cardSku((int) $category->id),
            'name' => mb_substr(trim((string) $category->name), 0, self::NAME_MAX),
            'price' => max(0, (float) $category->price),
            'active' => $active,
        ];
    }

    /**
     * @param  array{product_id:int,variation_id:int}  $result
     */
    private function storeCardLink(CardCategory $category, array $result, bool $active): void
    {
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

        $response = $this->send($payload);

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
     * POST with one token refresh on 401 and, after withRetries(), bounded waits on 409/429 (honours Retry-After).
     *
     * @param  array<string, mixed>  $payload
     */
    private function send(array $payload): Response
    {
        $tokenRefreshed = false;
        $retries = 0;

        while (true) {
            $response = $this->post($payload);

            if ($response->status() === 401 && ! $tokenRefreshed) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                $tokenRefreshed = true;

                continue;
            }

            if (! in_array($response->status(), self::RETRYABLE_STATUSES, true) || $retries >= $this->maxRetries) {
                return $response;
            }

            Sleep::for($this->retryDelaySeconds($response, $retries))->seconds();
            $retries++;
        }
    }

    private function retryDelaySeconds(Response $response, int $retries): int
    {
        $maxWait = max(1, (int) config('services.pos_catalog.max_retry_wait', 60));
        $retryAfter = $response->header('Retry-After');

        $seconds = is_numeric($retryAfter) ? (int) $retryAfter : 2 ** $retries;

        return min($maxWait, max(1, $seconds));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(array $payload): Response
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
