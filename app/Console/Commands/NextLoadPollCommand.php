<?php

namespace App\Console\Commands;

use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadSyncService;
use Illuminate\Console\Command;

class NextLoadPollCommand extends Command
{
    protected $signature = 'nextload:poll {--limit=50 : Max supplier orders to check per run}';

    protected $description = 'Resend timed-out Next Load orders, poll InProgress ones, record delayed manager sales and move orphaned codes to stock';

    public function handle(NextLoadSyncService $sync): int
    {
        if (! NextLoadClient::enabled()) {
            $this->line('Next Load is disabled.');

            return self::SUCCESS;
        }

        $stats = $sync->pollDue(max(1, (int) $this->option('limit')));
        $this->info(sprintf(
            'Checked %d, completed %d, failed %d, recorded %d delayed sale(s), released %d code(s) to stock, stuck %d.',
            $stats['checked'], $stats['completed'], $stats['failed'], $stats['recorded'], $stats['released'], $stats['stuck']
        ));

        return self::SUCCESS;
    }
}
