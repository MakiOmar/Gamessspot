<?php

namespace App\Services\NextLoad;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin HTTP client for the Next Load merchant API (every endpoint is a POST with a status envelope).
 * Responses can contain card pins, so bodies are never logged.
 */
class NextLoadClient
{
    private const TOKEN_CACHE_KEY = 'nextload:token';

    private const TOKEN_TTL_SECONDS = 43200;

    public const PRODUCTS_CACHE_KEY = 'nextload:products';

    private const PRODUCTS_TTL_SECONDS = 600;

    public static function enabled(): bool
    {
        return (bool) config('services.nextload.enabled') && self::configured();
    }

    public static function configured(): bool
    {
        return filled(config('services.nextload.email'))
            && filled(config('services.nextload.password'))
            && filled(config('services.nextload.security_code'));
    }

    public function balance(): string
    {
        $json = $this->call('Balance');

        return Money::normalize($json['data'][0]['balance'] ?? '0');
    }

    /**
     * @return list<array{product_id:int,product:string,price:string}>
     */
    public function products(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::PRODUCTS_CACHE_KEY);
        }

        return Cache::remember(self::PRODUCTS_CACHE_KEY, self::PRODUCTS_TTL_SECONDS, function () {
            $rows = $this->call('ProductsList')['data'] ?? [];

            return collect(is_array($rows) ? $rows : [])
                ->filter(fn ($row) => is_array($row) && isset($row['product_id']))
                ->map(fn (array $row) => [
                    'product_id' => (int) $row['product_id'],
                    'product' => (string) ($row['product'] ?? ''),
                    'price' => Money::normalize($row['price'] ?? '0'),
                ])
                ->values()
                ->all();
        });
    }

    /** @return array{product_id:int,product:string,price:string}|null */
    public function product(int $productId): ?array
    {
        foreach ($this->products() as $product) {
            if ($product['product_id'] === $productId) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Never call twice with different references for the same sale; see NextLoadPurchaseService.
     *
     * @return array<string, mixed> CreateOrder `data`
     */
    public function createOrder(string $reference, int $productId, int $quantity): array
    {
        $json = $this->call('CreateOrder', [
            'reference' => $reference,
            'productid' => (string) $productId,
            'quantity' => (string) $quantity,
        ]);

        return is_array($json['data'] ?? null) ? $json['data'] : [];
    }

    /**
     * @return array<string, mixed>|null null when the supplier does not know the order
     */
    public function fetchOrder(string|int $orderId): ?array
    {
        $rows = $this->call('FetchOrder', ['order_id' => (string) $orderId])['data'] ?? [];

        return is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;
    }

    /** @return list<array<string, mixed>> */
    public function orders(string $from, string $to): array
    {
        $rows = $this->call('Orders', ['from' => $from, 'to' => $to])['data'] ?? [];

        return is_array($rows) ? array_values($rows) : [];
    }

    /** @return list<array<string, mixed>> */
    public function transactions(string $from, string $to): array
    {
        $rows = $this->call('Transactions', ['from' => $from, 'to' => $to])['data'] ?? [];

        return is_array($rows) ? array_values($rows) : [];
    }

    public function forgetToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    /**
     * @return array<string, mixed> decoded Success envelope
     */
    private function call(string $endpoint, array $body = [], bool $retried = false): array
    {
        $this->ensureConfigured();

        $response = $this->send($endpoint, $body, $this->token());
        $json = $response->json();
        $message = is_array($json) ? (string) ($json['message'] ?? '') : '';

        if ($response->status() === 501 || strcasecmp($message, 'Invalid Login') === 0) {
            $this->forgetToken();
            if (! $retried) {
                return $this->call($endpoint, $body, true);
            }

            throw $this->fail($endpoint, $response, NextLoadException::CONFIG, 'Next Load rejected the API token.');
        }

        return $this->unwrap($endpoint, $response, $json);
    }

    private function token(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            try {
                return Crypt::decryptString($cached);
            } catch (Throwable) {
                $this->forgetToken();
            }
        }

        $response = $this->send('Token', [
            'email' => (string) config('services.nextload.email'),
            'password' => (string) config('services.nextload.password'),
            'securitycode' => (string) config('services.nextload.security_code'),
        ]);
        $json = $response->json();

        if ($response->status() === 401) {
            throw $this->fail('Token', $response, NextLoadException::CONFIG, 'Next Load rejected the credentials.');
        }

        $token = $this->unwrap('Token', $response, $json)['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw $this->fail('Token', $response, NextLoadException::CONFIG, 'Next Load returned no token.');
        }

        Cache::put(self::TOKEN_CACHE_KEY, Crypt::encryptString($token), self::TOKEN_TTL_SECONDS);

        return $token;
    }

    private function send(string $endpoint, array $body, ?string $token = null): Response
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout(max(5, (int) config('services.nextload.timeout', 30)));
        if ($token !== null) {
            $request = $request->withToken($token);
        }

        $url = config('services.nextload.base_url').$endpoint;

        try {
            return $body === [] ? $request->send('POST', $url) : $request->post($url, $body);
        } catch (ConnectionException $e) {
            Log::warning('Next Load request failed to connect', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);

            throw new NextLoadException(NextLoadException::TRANSPORT, 'Next Load did not respond. Try again.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function unwrap(string $endpoint, Response $response, mixed $json): array
    {
        if (is_array($json) && ($json['status'] ?? null) === 'Success') {
            return $json;
        }

        if (! is_array($json) || ! isset($json['status'])) {
            // No envelope: the request may or may not have been processed, so it is safe only to retry as-is.
            $reason = $response->serverError() || $response->status() === 0
                ? NextLoadException::TRANSPORT
                : NextLoadException::FAILED;

            throw $this->fail($endpoint, $response, $reason, 'Next Load returned an unexpected response.');
        }

        $message = trim((string) ($json['message'] ?? 'Request failed'));
        $reason = match (true) {
            str_contains(strtolower($message), 'reference is existing') => NextLoadException::DUPLICATE_REFERENCE,
            str_contains(strtolower($message), 'balance') => NextLoadException::INSUFFICIENT_BALANCE,
            $response->status() === 401 => NextLoadException::CONFIG,
            default => NextLoadException::FAILED,
        };

        throw $this->fail($endpoint, $response, $reason, 'Next Load: '.mb_substr($message, 0, 200));
    }

    private function ensureConfigured(): void
    {
        if (! self::configured()) {
            throw new NextLoadException(NextLoadException::CONFIG, 'Next Load credentials are not configured.');
        }
    }

    private function fail(string $endpoint, Response $response, string $reason, string $message): NextLoadException
    {
        Log::warning('Next Load request failed', [
            'endpoint' => $endpoint,
            'http_status' => $response->status(),
            'reason' => $reason,
            'message' => $message,
        ]);

        return new NextLoadException($reason, $message, $response->status());
    }
}
