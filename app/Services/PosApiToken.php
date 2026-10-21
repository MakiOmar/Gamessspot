<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cached POS API login token, shared with OrderController::login() through the `api_token` key.
 */
class PosApiToken
{
    public const CACHE_KEY = 'api_token';

    public static function baseUrl(): string
    {
        return rtrim(SettingsService::getPosBaseUrl(), '/');
    }

    public function get(string $base): string
    {
        $token = Cache::get(self::CACHE_KEY);
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

        Cache::put(self::CACHE_KEY, $token, now()->addDays(3));

        return (string) $token;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
