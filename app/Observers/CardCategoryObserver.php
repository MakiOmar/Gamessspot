<?php

namespace App\Observers;

use App\Jobs\SyncPosCatalogJob;
use App\Models\CardCategory;
use App\Services\PosCatalogSync;
use Illuminate\Support\Facades\Log;

class CardCategoryObserver
{
    public function created(CardCategory $category): void
    {
        $this->queue(SyncPosCatalogJob::card($category));
    }

    public function updated(CardCategory $category): void
    {
        if ($category->wasChanged(['name', 'price'])) {
            $this->queue(SyncPosCatalogJob::card($category));
        }
    }

    public function deleted(CardCategory $category): void
    {
        $this->queue(SyncPosCatalogJob::card($category, true));
    }

    /**
     * A queue or POS outage must never fail the manager save.
     */
    private function queue(SyncPosCatalogJob $job): void
    {
        if (! PosCatalogSync::enabled()) {
            return;
        }
        try {
            dispatch($job);
        } catch (\Throwable $e) {
            Log::warning('POS catalog sync could not be queued', ['kind' => $job->kind, 'id' => $job->id, 'message' => $e->getMessage()]);
        }
    }
}
