<?php

namespace App\Console\Commands;

use App\Services\NextLoad\NextLoadClient;
use App\Services\NextLoad\NextLoadException;
use App\Services\NextLoad\NextLoadSyncService;
use Illuminate\Console\Command;

class NextLoadSyncProductsCommand extends Command
{
    protected $signature = 'nextload:sync-products';

    protected $description = 'Refresh the Next Load price list and the supplier cost of linked card categories';

    public function handle(NextLoadSyncService $sync): int
    {
        if (! NextLoadClient::enabled()) {
            $this->line('Next Load is disabled.');

            return self::SUCCESS;
        }

        try {
            $result = $sync->syncProducts();
        } catch (NextLoadException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Updated %d categor%s.', $result['updated'], $result['updated'] === 1 ? 'y' : 'ies'));
        if ($result['missing'] !== []) {
            $this->warn('Linked products no longer listed for category ids: '.implode(', ', $result['missing']));
        }

        return self::SUCCESS;
    }
}
