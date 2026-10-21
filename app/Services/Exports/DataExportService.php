<?php

namespace App\Services\Exports;

use App\Jobs\GenerateOrdersExportJob;
use App\Models\DataExport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DataExportService
{
    /**
     * Queue a sell log export, or return the caller's export that is still running so
     * repeated clicks do not stack heavy jobs on the worker.
     *
     * @param array<string, mixed> $filters
     * @return array{export: DataExport, reused: bool}
     */
    public function requestOrdersExport(User $user, array $filters): array
    {
        $running = DataExport::where('user_id', $user->id)
            ->where('type', DataExport::TYPE_ORDERS)
            ->inFlight()
            ->latest('id')
            ->first();
        if ($running !== null) {
            return array('export' => $running, 'reused' => true);
        }

        $export = DataExport::create(array(
            'user_id' => $user->id,
            'type'    => DataExport::TYPE_ORDERS,
            'filters' => $filters,
            'status'  => DataExport::STATUS_QUEUED,
        ));

        GenerateOrdersExportJob::dispatch($export->id);

        return array('export' => $export->fresh(), 'reused' => false);
    }

    /**
     * Delete exports (and their files) older than the retention window.
     */
    public function pruneExpired(): int
    {
        $disk = Storage::disk(DataExport::DISK);
        $deleted = 0;

        DataExport::where('created_at', '<', now()->subHours(DataExport::RETENTION_HOURS))
            ->chunkById(200, function ($exports) use ($disk, &$deleted) {
                foreach ($exports as $export) {
                    if ($export->file_path !== null) {
                        $disk->delete($export->file_path);
                    }
                    $export->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }
}
