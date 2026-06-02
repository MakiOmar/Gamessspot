<?php

namespace App\Providers;

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
        foreach (array_keys(config('permissions.abilities', array())) as $ability) {
            if ($ability === 'view-game-accounts') {
                continue;
            }

            Gate::define($ability, function ($user) use ($ability) {
                $user->loadMissing('roles');

                return $user->roles->contains(
                    fn ($role) => $role->hasCapability($ability)
                );
            });
        }

        Gate::define('view-game-accounts', function ($user) {
            $user->loadMissing('roles');

            return $user->roles->contains(
                fn ($role) => $role->hasCapability('view-game-accounts')
                    || $role->hasCapability('manage-accounts')
            );
        });
    }
}
