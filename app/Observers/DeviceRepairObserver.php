<?php

namespace App\Observers;

use App\Models\DeviceRepair;
use App\Services\CacheManager;
use Illuminate\Support\Facades\Log;

class DeviceRepairObserver
{
    /**
     * Handle the DeviceRepair "created" event.
     *
     * @param  \App\Models\DeviceRepair  $deviceRepair
     * @return void
     */
    public function created(DeviceRepair $deviceRepair)
    {
        $this->invalidateDeviceRepairCaches('created');
    }

    /**
     * Handle the DeviceRepair "updated" event.
     *
     * @param  \App\Models\DeviceRepair  $deviceRepair
     * @return void
     */
    public function updated(DeviceRepair $deviceRepair)
    {
        $this->invalidateDeviceRepairCaches('updated');
    }

    /**
     * Handle the DeviceRepair "deleted" event.
     *
     * @param  \App\Models\DeviceRepair  $deviceRepair
     * @return void
     */
    public function deleted(DeviceRepair $deviceRepair)
    {
        $this->invalidateDeviceRepairCaches('deleted');
    }

    /**
     * Handle the DeviceRepair "restored" event.
     *
     * @param  \App\Models\DeviceRepair  $deviceRepair
     * @return void
     */
    public function restored(DeviceRepair $deviceRepair)
    {
        $this->invalidateDeviceRepairCaches('restored');
    }

    /**
     * Invalidate device repair-related caches
     *
     * @param string $event
     * @return void
     */
    protected function invalidateDeviceRepairCaches(string $event)
    {
        try {
            CacheManager::invalidateDeviceRepairs();
        } catch (\Exception $e) {
            Log::error('Failed to invalidate device repair cache', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }
}

