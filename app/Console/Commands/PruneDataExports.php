<?php

namespace App\Console\Commands;

use App\Models\DataExport;
use App\Services\Exports\DataExportService;
use Illuminate\Console\Command;

class PruneDataExports extends Command
{
    protected $signature = 'exports:prune';

    protected $description = 'Delete background exports (and their files) older than ' . DataExport::RETENTION_HOURS . ' hours';

    public function handle(DataExportService $exports): int
    {
        $deleted = $exports->pruneExpired();
        $this->info("Pruned {$deleted} export(s).");

        return self::SUCCESS;
    }
}
