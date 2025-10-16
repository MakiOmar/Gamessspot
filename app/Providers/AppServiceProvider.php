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
        // Optimize: Prevent lazy loading to catch N+1 query problems in development
        Model::preventLazyLoading( ! app()->isProduction() );

        // Optimize: Prevent silently discarding attributes
        Model::preventSilentlyDiscardingAttributes( ! app()->isProduction() );

        // Optimize: Prevent accessing missing attributes
        Model::preventAccessingMissingAttributes( ! app()->isProduction() );

        // Monitor database connections in non-production environments
        if ( ! app()->isProduction() ) {
            // Log slow queries (queries taking more than 1000ms)
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

        // Optimize: Set connection timeout to prevent hanging connections
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, 5);

        // Optimize: Register shutdown function to ensure connections are closed
        register_shutdown_function(
            function () {
                try {
                    DB::disconnect();
                } catch ( \Exception $e ) {
                    // Silently fail if connection already closed
                }
            }
        );
    }
}
