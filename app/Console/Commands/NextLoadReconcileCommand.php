<?php

namespace App\Console\Commands;

use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadException;
use App\Services\NextLoad\NextLoadSyncService;
use Illuminate\Console\Command;

class NextLoadReconcileCommand extends Command
{
    protected $signature = 'nextload:reconcile {--days=2 : How many days back to compare}';

    protected $description = 'Compare Next Load orders with local supplier orders and flag differences';

    public function handle(NextLoadSyncService $sync): int
    {
        if (! NextLoadClient::enabled()) {
            $this->line('Next Load is disabled.');

            return self::SUCCESS;
        }

        $from = now()->subDays(max(1, (int) $this->option('days')))->toDateString();
        $to = now()->toDateString();

        try {
            $result = $sync->reconcile($from, $to);
        } catch (NextLoadException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('%s to %s: %d supplier order(s), %d unknown, %d mismatched.', $from, $to, $result['supplier'], count($result['unknown']), count($result['mismatched'])));
        foreach ($result['unknown'] as $reference) {
            $this->warn('Unknown reference: '.$reference);
        }
        foreach ($result['mismatched'] as $reference) {
            $this->warn('Status mismatch: '.$reference);
        }

        return self::SUCCESS;
    }
}
