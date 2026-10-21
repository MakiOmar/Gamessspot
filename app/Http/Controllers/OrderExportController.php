<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportOrdersRequest;
use App\Models\DataExport;
use App\Services\Exports\DataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sell log exports run on the queue: request one, poll its status, then download the file.
 */
class OrderExportController extends Controller
{
    public function __construct(private DataExportService $exports)
    {
    }

    public function store(ExportOrdersRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->exports->requestOrdersExport($request->user('admin'), $request->filters());
        $message = $result['reused']
            ? 'Your previous export is still being prepared. It will download when ready.'
            : 'Your export is being prepared. It will download when ready.';

        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json($this->payload($result['export']) + array('message' => $message), $result['reused'] ? 200 : 202);
    }

    public function show(DataExport $dataExport): JsonResponse
    {
        $this->authorize('view', $dataExport);

        return response()->json($this->payload($dataExport));
    }

    public function download(DataExport $dataExport): StreamedResponse
    {
        $this->authorize('download', $dataExport);

        $disk = Storage::disk(DataExport::DISK);
        abort_unless($disk->exists($dataExport->file_path), 404, 'This export has expired. Please export again.');

        return $disk->download($dataExport->file_path, $dataExport->file_name);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(DataExport $export): array
    {
        return array(
            'id'           => $export->id,
            'status'       => $export->status,
            'row_count'    => $export->row_count,
            'error'        => $export->error,
            'status_url'   => route('manager.orders.exports.show', $export),
            'download_url' => $export->isReady() ? route('manager.orders.exports.download', $export) : null,
        );
    }
}
