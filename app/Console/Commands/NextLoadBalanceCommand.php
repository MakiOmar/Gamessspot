<?php

namespace App\Console\Commands;

use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadSyncService;
use Illuminate\Console\Command;

class NextLoadBalanceCommand extends Command
{
    protected $signature = 'nextload:balance';

    protected $description = 'Cache the Next Load balance and warn when it is below NEXTLOAD_LOW_BALANCE';

    public function handle(NextLoadSyncService $sync): int
    {
        if (! NextLoadClient::enabled()) {
            $this->line('Next Load is disabled.');

            return self::SUCCESS;
        }

        $result = $sync->checkBalance();
        if ($result['balance'] === null) {
            $this->error('Could not read the Next Load balance.');

            return self::FAILURE;
        }

        $this->info('Balance: '.$result['balance'].($result['low'] ? ' (below the low-balance threshold)' : ''));

        return self::SUCCESS;
    }
}
