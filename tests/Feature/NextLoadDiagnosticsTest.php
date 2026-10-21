<?php

namespace Tests\Feature;

use App\Services\NextLoad\NextLoadClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Login-free Next Load diagnostics page: key-gated, read-only, secrets masked.
 */
class NextLoadDiagnosticsTest extends TestCase
{
    private const BASE = 'https://nl.test/APIS/V1/';

    private const KEY = 'diag-key-0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.nextload.enabled' => true,
            'services.nextload.base_url' => self::BASE,
            'services.nextload.email' => 'merchant@example.com',
            'services.nextload.password' => 'secret-pass',
            'services.nextload.security_code' => '987654',
            'services.nextload.diagnostics_key' => self::KEY,
        ]);
        Cache::forget('nextload:token');
        Cache::forget(NextLoadClient::PRODUCTS_CACHE_KEY);
    }

    public function test_page_is_hidden_without_a_configured_or_matching_key(): void
    {
        $this->get(route('nextload.diagnostics', ['key' => 'wrong']))->assertNotFound();
        $this->get(route('nextload.diagnostics'))->assertNotFound();

        config(['services.nextload.diagnostics_key' => null]);
        $this->get(route('nextload.diagnostics', ['key' => '']))->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_successful_run_shows_exchanges_with_secrets_masked(): void
    {
        Http::fake([
            'api.ipify.org*' => Http::response('82.29.178.160'),
            self::BASE.'Token' => Http::response(['status' => 'Success', 'token' => 'tok-secret-abc']),
            self::BASE.'Balance' => Http::response(['status' => 'Success', 'data' => [['balance' => '125.500']]]),
            self::BASE.'ProductsList' => Http::response(['status' => 'Success', 'data' => [
                ['product_id' => 266, 'product' => 'Test', 'price' => '0.000'],
                ['product_id' => 300, 'product' => 'PSN 10$', 'price' => '9.800'],
            ]]),
        ]);

        $this->get(route('nextload.diagnostics', ['key' => self::KEY]))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('All checks passed')
            ->assertSee('82.29.178.160')
            ->assertSee('Balance: 125.500')
            ->assertSee('2 products')
            ->assertSee('Test — price 0.000')
            ->assertSee(self::BASE.'Token')
            ->assertSee('Bearer ***')
            ->assertDontSee('secret-pass')
            ->assertDontSee('987654')
            ->assertDontSee('tok-secret-abc')
            ->assertDontSee('merchant@example.com');

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'CreateOrder'));
    }

    public function test_supplier_rejection_is_reported_as_a_failed_check(): void
    {
        Http::fake([
            'api.ipify.org*' => Http::response('82.29.178.160'),
            self::BASE.'*' => Http::response(['status' => 'Failed', 'message' => 'Not Allow Ip']),
        ]);

        $this->get(route('nextload.diagnostics', ['key' => self::KEY]))
            ->assertOk()
            ->assertSee('Some checks failed')
            ->assertSee('Next Load: Not Allow Ip')
            ->assertSee('&quot;message&quot;: &quot;Not Allow Ip&quot;', false);
    }
}
