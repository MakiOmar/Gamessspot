<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Used by RedirectIfAuthenticated (guest middleware) after login.
     * Route loading now lives in bootstrap/app.php (Laravel 11+).
     *
     * @var string
     */
    public const HOME = '/manager';

    /**
     * Register any application services.
     */
    public function boot(): void
    {
        //
    }
}
