<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = array();

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('access-dashboard', function ($user) {
            return $user->hasRole(['admin', 'sales', 'account manager', 'accountant']);
        });

        Gate::define('manage-games', function ($user) {
            return $user->hasRole(['admin', 'sales', 'account manager']);
        });

        Gate::define('manage-gift-cards', function ($user) {
            return $user->hasRole(['admin', 'sales']);
        });

        Gate::define('view-sell-log', function ($user) {
            return $user->hasRole(['admin', 'sales', 'account manager', 'accountant']);
        });

        Gate::define('manage-accounts', function ($user) {
            return $user->hasRole(['admin', 'account manager']);
        });

        Gate::define('manage-options', function ($user) {
            return $user->hasRole('admin');
        });
        Gate::define('view-reports', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('manage-categories', function ($user) {
            return $user->hasRole('admin');
        });


        Gate::define('edit-games', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('manage-users', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('manage-store-profiles', function ($user) {
            return $user->hasRole(['admin', 'accountant']);
        });
    }
}
