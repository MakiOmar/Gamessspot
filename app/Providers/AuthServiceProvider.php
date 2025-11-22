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
            // Ensure roles are loaded
            $user->loadMissing('roles');
            // Note: 'accountatnt' is the actual role name in database (typo)
            return $user->hasRole(['admin', 'sales', 'account manager', 'accountatnt']);
        });

        Gate::define('manage-games', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole(['admin', 'sales', 'account manager']);
        });

        Gate::define('manage-gift-cards', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole(['admin', 'sales']);
        });

        Gate::define('view-sell-log', function ($user) {
            $user->loadMissing('roles');
            // Note: 'accountatnt' is the actual role name in database (typo)
            return $user->hasRole(['admin', 'sales', 'account manager', 'accountatnt']);
        });

        Gate::define('manage-accounts', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole(['admin', 'account manager']);
        });

        Gate::define('manage-options', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole('admin');
        });
        Gate::define('view-reports', function ($user) {
            $user->loadMissing('roles');
            // Note: 'accountatnt' is the actual role name in database (typo)
            return $user->hasRole(['admin', 'accountatnt']);
        });

        Gate::define('manage-categories', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole('admin');
        });


        Gate::define('edit-games', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole('admin');
        });

        Gate::define('manage-users', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole('admin');
        });

        Gate::define('manage-store-profiles', function ($user) {
            $user->loadMissing('roles');
            // Note: 'accountatnt' is the actual role name in database (typo)
            return $user->hasRole(['admin', 'accountatnt']);
        });

        Gate::define('manage-device-repairs', function ($user) {
            $user->loadMissing('roles');
            // Note: 'accountatnt' is the actual role name in database (typo)
            return $user->hasRole(['admin', 'sales', 'account manager', 'accountatnt']);
        });

        Gate::define('delete-device-repairs', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole('admin');
        });

        Gate::define('submit-device-request', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole(['customer', 'admin', 'sales']);
        });

        Gate::define('track-device-status', function ($user) {
            $user->loadMissing('roles');
            return $user->hasRole(['customer', 'admin', 'sales']);
        });
    }
}
