<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Generated URLs must use https when APP_URL is https, even outside production (staging behind Traefik).
 */
class ForceHttpsSchemeTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceScheme(null);
        parent::tearDown();
    }

    public function test_https_app_url_forces_https_routes_outside_production(): void
    {
        config(['app.url' => 'https://accounts.example.test']);
        (new AppServiceProvider($this->app))->register();

        $this->assertStringStartsWith('https://', route('manager.traders.store'));
    }

    public function test_http_app_url_keeps_http_routes_locally(): void
    {
        URL::forceScheme(null);
        config(['app.url' => 'http://localhost']);
        (new AppServiceProvider($this->app))->register();

        $this->assertStringStartsWith('http://', route('manager.traders.store'));
    }
}
