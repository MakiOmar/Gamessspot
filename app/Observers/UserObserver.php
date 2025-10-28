<?php

namespace App\Observers;

use App\Models\User;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        $this->invalidateUserCaches('created');
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        $this->invalidateUserCaches('updated');
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        $this->invalidateUserCaches('deleted');
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        $this->invalidateUserCaches('restored');
    }

    /**
     * Invalidate user-related caches
     *
     * @param string $event
     * @return void
     */
    protected function invalidateUserCaches(string $event)
    {
        try {
            CacheManager::invalidateUsers();
            
            Log::debug('User cache invalidated', [
                'event' => $event,
                'observer' => 'UserObserver'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate user cache', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}

