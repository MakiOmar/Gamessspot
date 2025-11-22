<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Role;
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
            // Get allowed roles dynamically from database - all staff roles can access dashboard
            $allowedRoleNames = ['admin', 'sales', 'account manager', 'accountatnt', 'accountant'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('manage-games', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['admin', 'sales', 'account manager'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('manage-gift-cards', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['admin', 'sales'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('view-sell-log', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['admin', 'sales', 'account manager', 'accountatnt', 'accountant'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('manage-accounts', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['admin', 'account manager'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('manage-options', function ($user) {
            $user->loadMissing('roles');
            if (!Role::roleExists('admin')) {
                return false;
            }
            return $user->hasRole('admin');
        });
        Gate::define('view-reports', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['admin', 'accountatnt', 'accountant'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('manage-categories', function ($user) {
            $user->loadMissing('roles');
            if (!Role::roleExists('admin')) {
                return false;
            }
            return $user->hasRole('admin');
        });


        Gate::define('edit-games', function ($user) {
            $user->loadMissing('roles');
            if (!Role::roleExists('admin')) {
                return false;
            }
            return $user->hasRole('admin');
        });

        Gate::define('manage-users', function ($user) {
            $user->loadMissing('roles');
            if (!Role::roleExists('admin')) {
                return false;
            }
            return $user->hasRole('admin');
        });

        Gate::define('manage-store-profiles', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['admin', 'accountatnt', 'accountant'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('manage-device-repairs', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['admin', 'sales', 'account manager', 'accountatnt', 'accountant'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('delete-device-repairs', function ($user) {
            $user->loadMissing('roles');
            if (!Role::roleExists('admin')) {
                return false;
            }
            return $user->hasRole('admin');
        });

        Gate::define('submit-device-request', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['customer', 'admin', 'sales'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });

        Gate::define('track-device-status', function ($user) {
            $user->loadMissing('roles');
            $allowedRoleNames = ['customer', 'admin', 'sales'];
            $allowedRoles = array_filter($allowedRoleNames, function($roleName) {
                return Role::roleExists($roleName);
            });
            return $user->hasRole($allowedRoles);
        });
    }
}
