<?php

namespace Tests\Unit;

use App\Models\Account;
use Tests\TestCase;

class FullAccountStockAssignmentTest extends TestCase
{
    public function test_full_account_dual_platform_sets_all_triples_to_one(): void
    {
        $stocks = Account::resolveInitialStocks(true, false);

        $this->assertSame([
            'ps4_primary_stock' => 1,
            'ps4_secondary_stock' => 1,
            'ps4_offline_stock' => 1,
            'ps5_primary_stock' => 1,
            'ps5_secondary_stock' => 1,
            'ps5_offline_stock' => 1,
        ], $stocks);
    }

    public function test_full_account_ps5_only_zeros_ps4_and_sets_ps5_triples_to_one(): void
    {
        $stocks = Account::resolveInitialStocks(true, true);

        $this->assertSame([
            'ps4_primary_stock' => 0,
            'ps4_secondary_stock' => 0,
            'ps4_offline_stock' => 0,
            'ps5_primary_stock' => 1,
            'ps5_secondary_stock' => 1,
            'ps5_offline_stock' => 1,
        ], $stocks);
    }

    public function test_non_full_ps5_only_keeps_legacy_offline_default(): void
    {
        $stocks = Account::resolveInitialStocks(false, true);

        $this->assertSame(0, $stocks['ps4_primary_stock']);
        $this->assertSame(0, $stocks['ps4_offline_stock']);
        $this->assertSame(2, $stocks['ps5_offline_stock']);
    }
}
