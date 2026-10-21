<?php

namespace App\Services\NextLoad;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Read-only Next Load health check for the diagnostics page: runs the real client (token, balance,
 * price list) and records every HTTP exchange with credentials, tokens and card codes masked.
 * Never calls CreateOrder.
 */
class NextLoadDiagnostics
{
    public const TEST_PRODUCT_ID = 266;

    private const MASK = '***';

    private const SECRET_KEYS = array('password', 'securitycode', 'security_code', 'token', 'pin', 'serial', 'code', 'card_code');

    private const MAX_LIST_ITEMS = 15;

    /** @var list<array<string, mixed>> */
    private array $exchanges = array();

    /** Checks run sequentially, so one start time covers the request in flight. */
    private float $requestStartedAt = 0.0;

    public function __construct(private NextLoadClient $client)
    {
    }

    /**
     * @return array{config: array<string, mixed>, checks: list<array<string, mixed>>, exchanges: list<array<string, mixed>>}
     */
    public function run(): array
    {
        $this->listen();

        $checks = array();
        $checks[] = $this->check('Outbound IP (must be on the Next Load allowlist)', fn () => $this->egressIp());

        if (NextLoadClient::configured()) {
            $checks[] = $this->check('Login (fresh token) + balance', function () {
                $this->client->forgetToken();

                return 'Balance: ' . $this->client->balance();
            });
            $checks[] = $this->check('Price list', fn () => count($this->client->products(true)) . ' products');
            $checks[] = $this->check('Test product ' . self::TEST_PRODUCT_ID, function () {
                $product = $this->client->product(self::TEST_PRODUCT_ID);

                return $product === null
                    ? 'Not in the price list'
                    : $product['product'] . ' — price ' . $product['price'];
            });
        } else {
            $checks[] = array('name' => 'Credentials', 'ok' => false, 'result' => 'NEXTLOAD_EMAIL / PASSWORD / SECURITY_CODE are not set.', 'ms' => 0);
        }

        return array(
            'config'    => $this->configSummary(),
            'checks'    => $checks,
            'exchanges' => $this->exchanges,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function check(string $name, callable $probe): array
    {
        $start = microtime(true);
        try {
            $result = $probe();
            $ok = true;
        } catch (Throwable $e) {
            $result = class_basename($e) . ': ' . $e->getMessage();
            $ok = false;
        }

        return array('name' => $name, 'ok' => $ok, 'result' => $result, 'ms' => (int) round((microtime(true) - $start) * 1000));
    }

    private function egressIp(): string
    {
        return trim(Http::timeout(8)->get('https://api.ipify.org')->throw()->body());
    }

    /**
     * @return array<string, mixed>
     */
    private function configSummary(): array
    {
        $email = (string) config('services.nextload.email');

        return array(
            'enabled'          => (bool) config('services.nextload.enabled'),
            'base_url'         => config('services.nextload.base_url'),
            'email'            => $email === '' ? null : preg_replace('/^(.{2})[^@]*/', '$1' . self::MASK, $email),
            'password_set'     => filled(config('services.nextload.password')),
            'security_code_set' => filled(config('services.nextload.security_code')),
            'timeout'          => config('services.nextload.timeout'),
            'exchange_rate'    => config('services.nextload.exchange_rate'),
            'allow_loss'       => (bool) config('services.nextload.allow_loss'),
        );
    }

    private function listen(): void
    {
        Event::listen(RequestSending::class, function () {
            $this->requestStartedAt = microtime(true);
        });

        Event::listen(ResponseReceived::class, function (ResponseReceived $event) {
            $response = $event->response;
            $json = $response->json();
            $this->record($event->request, array(
                'status'  => $response->status(),
                'headers' => $this->flattenHeaders($response->headers()),
                'body'    => is_array($json) ? $this->redact($json) : mb_substr($response->body(), 0, 2000),
            ));
        });

        Event::listen(ConnectionFailed::class, function (ConnectionFailed $event) {
            $this->record($event->request, array('status' => null, 'headers' => array(), 'body' => 'Connection failed (no response).'));
        });
    }

    /**
     * @param array<string, mixed> $response
     */
    private function record(ClientRequest $request, array $response): void
    {
        $body = $request->data();

        $this->exchanges[] = array(
            'sent_at'  => now()->toDateTimeString(),
            'ms'       => (int) round((microtime(true) - $this->requestStartedAt) * 1000),
            'request'  => array(
                'method'  => $request->method(),
                'url'     => $request->url(),
                'headers' => $this->redactHeaders($this->flattenHeaders($request->headers())),
                'body'    => is_array($body) && $body !== array() ? $this->redact($body) : null,
            ),
            'response' => $response,
        );
    }

    /**
     * Mask secrets at any depth; long lists (price list) are cut to keep the page readable.
     */
    private function redact(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && in_array(strtolower($key), self::SECRET_KEYS, true) && ! is_array($value)) {
            return $value === null || $value === '' ? $value : self::MASK;
        }
        if ($key === 'email' && is_string($value)) {
            return preg_replace('/^(.{2})[^@]*/', '$1' . self::MASK, $value);
        }
        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $items = $isList ? array_slice($value, 0, self::MAX_LIST_ITEMS) : $value;
        $out = array();
        foreach ($items as $childKey => $child) {
            $out[$childKey] = $this->redact($child, is_string($childKey) ? $childKey : null);
        }
        if ($isList && count($value) > self::MAX_LIST_ITEMS) {
            $out[] = '… ' . (count($value) - self::MAX_LIST_ITEMS) . ' more';
        }

        return $out;
    }

    /**
     * @param array<string, array<int, string>|string> $headers
     * @return array<string, string>
     */
    private function flattenHeaders(array $headers): array
    {
        return array_map(fn ($value) => is_array($value) ? implode(', ', $value) : (string) $value, $headers);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function redactHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $headers[$name] = preg_replace('/^(\w+)\s+.+$/', '$1 ' . self::MASK, $value);
            }
        }

        return $headers;
    }
}
