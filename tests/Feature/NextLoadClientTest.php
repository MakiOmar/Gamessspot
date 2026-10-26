<?php

namespace Tests\Feature;

use App\Services\NextLoad\Money;
use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NextLoadClientTest extends TestCase
{
    private const BASE = 'https://nl.test/APIS/V1/';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.nextload.enabled' => true,
            'services.nextload.base_url' => self::BASE,
            'services.nextload.email' => 'merchant@example.com',
            'services.nextload.password' => 'secret-pass',
            'services.nextload.security_code' => '1234',
        ]);
        Cache::forget('nextload:token');
        Cache::forget(NextLoadClient::PRODUCTS_CACHE_KEY);
    }

    public function test_token_is_cached_and_reused(): void
    {
        Http::fake([
            self::BASE.'Token' => Http::response(['status' => 'Success', 'token' => 'tok-1']),
            self::BASE.'Balance' => Http::response(['status' => 'Success', 'data' => [['balance' => '300.5']]]),
        ]);

        $client = new NextLoadClient();
        $this->assertSame('300.500', $client->balance());
        $this->assertSame('300.500', $client->balance());

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'Balance' && $r->hasHeader('Authorization', 'Bearer tok-1'));
    }

    public function test_invalid_login_refreshes_token_and_retries_once(): void
    {
        Http::fake([
            self::BASE.'Token' => Http::sequence()
                ->push(['status' => 'Success', 'token' => 'old'])
                ->push(['status' => 'Success', 'token' => 'new']),
            self::BASE.'Balance' => Http::sequence()
                ->push(['status' => 'Error', 'message' => 'Invalid Login'], 501)
                ->push(['status' => 'Success', 'data' => [['balance' => '1.000']]]),
        ]);

        $this->assertSame('1.000', (new NextLoadClient())->balance());
        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'Balance' && $r->hasHeader('Authorization', 'Bearer new'));
    }

    public function test_wrong_credentials_is_config_error(): void
    {
        Http::fake([self::BASE.'Token' => Http::response(['status' => 'Error'], 401)]);

        try {
            (new NextLoadClient())->balance();
            $this->fail('Expected exception');
        } catch (NextLoadException $e) {
            $this->assertSame(NextLoadException::CONFIG, $e->reason);
        }
    }

    public function test_duplicate_reference_and_transport_errors_are_classified(): void
    {
        Http::fake([
            self::BASE.'Token' => Http::response(['status' => 'Success', 'token' => 't']),
            self::BASE.'CreateOrder' => Http::sequence()
                ->push(['status' => 'Error', 'message' => 'reference is existing'], 500)
                ->push('Bad gateway', 502),
        ]);
        $client = new NextLoadClient();

        foreach ([NextLoadException::DUPLICATE_REFERENCE, NextLoadException::TRANSPORT] as $expected) {
            try {
                $client->createOrder('ref-1', 266, 1);
                $this->fail('Expected exception');
            } catch (NextLoadException $e) {
                $this->assertSame($expected, $e->reason);
            }
        }
    }

    public function test_fetch_order_empty_data_is_not_found_and_credentials_are_never_logged(): void
    {
        Log::spy();
        Http::fake([
            self::BASE.'Token' => Http::response(['status' => 'Success', 'token' => 't']),
            self::BASE.'FetchOrder' => Http::sequence()
                ->push(['status' => 'Success', 'data' => []])
                ->push(['status' => 'Error', 'message' => 'Order lookup failed'], 400),
        ]);
        $client = new NextLoadClient();

        $this->assertNull($client->fetchOrder(99));
        try {
            $client->fetchOrder(100);
        } catch (NextLoadException) {
        }

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return ! str_contains(json_encode($context), 'secret-pass');
        });
    }

    public function test_products_are_normalized_and_cached(): void
    {
        Http::fake([
            self::BASE.'Token' => Http::response(['status' => 'Success', 'token' => 't']),
            self::BASE.'ProductsList' => Http::response(['status' => 'Success', 'data' => [
                ['product_id' => 266, 'product' => 'Test', 'price' => '0.000'],
                ['product_id' => 352, 'product' => 'amazon KSA 50 SAR', 'price' => '13.38'],
            ]]),
        ]);
        $client = new NextLoadClient();

        $this->assertSame('13.380', $client->product(352)['price']);
        $this->assertNull($client->product(1));
        Http::assertSentCount(2);
    }

    public function test_products_keep_the_supplier_in_stock_flag(): void
    {
        Http::fake([
            self::BASE.'Token' => Http::response(['status' => 'Success', 'token' => 't']),
            self::BASE.'ProductsList' => Http::response(['status' => 'Success', 'data' => [
                ['product_id' => 513, 'product' => 'Amazon UK 100 £', 'price' => '137.000', 'in_stock' => false],
                ['product_id' => 514, 'product' => 'Amazon UK 50 £', 'price' => '68.500', 'in_stock' => true],
                ['product_id' => 515, 'product' => 'No flag', 'price' => '1.000'],
            ]]),
        ]);
        $client = new NextLoadClient();

        $this->assertFalse($client->product(513)['in_stock']);
        $this->assertTrue($client->product(514)['in_stock']);
        $this->assertTrue($client->product(515)['in_stock']);
    }

    public function test_in_stock_flag_parsing_only_treats_explicit_no_as_out_of_stock(): void
    {
        foreach ([false, 0, '0', 'false', 'no'] as $value) {
            $this->assertFalse(NextLoadClient::parseInStock($value), var_export($value, true));
        }
        foreach ([true, 1, '1', 'true', null, '', 'unknown'] as $value) {
            $this->assertTrue(NextLoadClient::parseInStock($value), var_export($value, true));
        }
    }

    public function test_money_helpers_avoid_floats(): void
    {
        $this->assertSame(0, Money::compare('13.380', '13.38'));
        $this->assertSame('669.000', Money::toLocal('13.380', '50'));
        $this->assertSame('0.002', Money::toLocal('0.001', '1.5'));
    }
}
