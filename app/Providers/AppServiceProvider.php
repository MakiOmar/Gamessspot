<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ( app()->environment('local') ) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            // Register ImageUploadService as a singleton
            $this->app->singleton(
                ImageUploadService::class,
                function ($app) {
                    return new ImageUploadService();
                }
            );
        }

        if ( $this->app->environment('production') ) {
            \URL::forceScheme('https');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Optimize: Prevent lazy loading to catch N+1 query problems
        // Only enable in truly local development (not staging/production)
        if ( app()->environment('local') && app()->runningInConsole() === false ) {
            // Temporarily disabled to avoid breaking staging environment
            // Model::preventLazyLoading(true);
            // Model::preventSilentlyDiscardingAttributes(true);
            // Model::preventAccessingMissingAttributes(true);
        }

        // Log slow queries in non-production environments
        if ( app()->environment('local') ) {
            DB::listen(
                function ($query) {
                    if ( $query->time > 1000 ) {
                        Log::warning(
                            'Slow Query Detected',
                            array(
                                'sql'      => $query->sql,
                                'bindings' => $query->bindings,
                                'time'     => $query->time . 'ms',
                            )
                        );
                    }
                }
            );
        }

        // Note: Connection timeout and pooling are handled in config/database.php
    }
}
