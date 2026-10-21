<?php

namespace App\Jobs;

use App\Exports\OrdersExport;
use App\Models\DataExport;
use App\Services\Exports\XlsxExportWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds a sell log XLSX in the background and records where it was stored on {@see DataExport}.
 */
class GenerateOrdersExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public int $dataExportId)
    {
        $this->afterCommit();
    }

    public function handle(XlsxExportWriter $writer): void
    {
        $export = DataExport::find($this->dataExportId);
        if ($export === null || $export->status !== DataExport::STATUS_QUEUED) {
            return;
        }

        $export->update(array('status' => DataExport::STATUS_PROCESSING));

        try {
            $this->generate($export, $writer);
        } catch (\Throwable $e) {
            if ($this->job !== null && $this->job->getConnectionName() !== 'sync') {
                throw $e;
            }
            // Inline (sync) dispatch runs inside the manager request: record the failure instead of a 500.
            $this->failed($e);
        }
    }

    private function generate(DataExport $export, XlsxExportWriter $writer): void
    {
        $disk = Storage::disk(DataExport::DISK);
        $directory = 'exports/' . $export->user_id;
        $disk->makeDirectory($directory);

        $fileName = 'orders-' . now()->format('Y-m-d-His') . '.xlsx';
        $path = $directory . '/' . $export->id . '-' . Str::random(16) . '.xlsx';

        $rows = $writer->write(new OrdersExport($export->filters ?? array()), $disk->path($path));

        $export->update(array(
            'status'       => DataExport::STATUS_COMPLETED,
            'file_path'    => $path,
            'file_name'    => $fileName,
            'row_count'    => $rows,
            'completed_at' => now(),
        ));
    }

    public function failed(\Throwable $e): void
    {
        $export = DataExport::find($this->dataExportId);
        if ($export === null || $export->status === DataExport::STATUS_COMPLETED) {
            return;
        }

        // Large exports can outlive the queue's retry_after (90 s); the redelivered copy is failed with
        // MaxAttemptsExceeded while the first run is still writing, so leave that export alone.
        if ($e instanceof MaxAttemptsExceededException && $export->status === DataExport::STATUS_PROCESSING) {
            Log::info('Orders export redelivered while still running', array('data_export_id' => $this->dataExportId));

            return;
        }

        $export->update(array(
            'status' => DataExport::STATUS_FAILED,
            'error'  => 'The export could not be generated.',
        ));

        Log::error('Orders export failed', array(
            'data_export_id' => $this->dataExportId,
            'message'        => $e->getMessage(),
            'file'           => $e->getFile() . ':' . $e->getLine(),
        ));
    }
}
