<?php

namespace App\Observers;

use App\Models\Account;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

class AccountObserver
{
    /**
     * Handle the Account "created" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function created(Account $account)
    {
        $this->invalidateAccountCaches('created');
    }

    /**
     * Handle the Account "updated" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function updated(Account $account)
    {
        $this->invalidateAccountCaches('updated');
    }

    /**
     * Handle the Account "deleted" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function deleted(Account $account)
    {
        $this->invalidateAccountCaches('deleted');
    }

    /**
     * Handle the Account "restored" event.
     *
     * @param  \App\Models\Account  $account
     * @return void
     */
    public function restored(Account $account)
    {
        $this->invalidateAccountCaches('restored');
    }

    /**
     * Invalidate account-related caches
     *
     * @param string $event
     * @return void
     */
    protected function invalidateAccountCaches(string $event)
    {
        try {
            CacheManager::invalidateAccounts();
            
            Log::debug('Account cache invalidated', [
                'event' => $event,
                'observer' => 'AccountObserver'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate account cache', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}

