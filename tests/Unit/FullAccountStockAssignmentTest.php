<?php

namespace Tests\Unit;

use App\Models\Account;
use Tests\TestCase;

class FullAccountStockAssignmentTest extends TestCase
{
    public function test_full_flag_does_not_change_initial_stocks(): void
    {
        $withFlag = Account::resolveInitialStocks(true, false);
        $withoutFlag = Account::resolveInitialStocks(false, false);

        $this->assertSame($withoutFlag, $withFlag);
        $this->assertSame([
            'ps4_primary_stock' => 1,
            'ps4_secondary_stock' => 1,
            'ps4_offline_stock' => 2,
            'ps5_primary_stock' => 1,
            'ps5_secondary_stock' => 1,
            'ps5_offline_stock' => 1,
        ], $withFlag);
    }

    public function test_full_flag_with_ps5_only_uses_normal_ps5_only_stocks(): void
    {
        $stocks = Account::resolveInitialStocks(true, true);

        $this->assertSame([
            'ps4_primary_stock' => 0,
            'ps4_secondary_stock' => 0,
            'ps4_offline_stock' => 0,
            'ps5_primary_stock' => 1,
            'ps5_secondary_stock' => 1,
            'ps5_offline_stock' => 2,
        ], $stocks);
    }

    public function test_non_full_ps5_only_keeps_legacy_offline_default(): void
    {
        $stocks = Account::resolveInitialStocks(false, true);

        $this->assertSame(0, $stocks['ps4_primary_stock']);
        $this->assertSame(0, $stocks['ps4_offline_stock']);
        $this->assertSame(2, $stocks['ps5_offline_stock']);
    }

    public function test_stocks_are_pristine_helpers(): void
    {
        $this->assertTrue(Account::stocksArePristine(Account::resolveInitialStocks(false, false)));
        $this->assertTrue(Account::stocksArePristine(Account::resolveInitialStocks(false, true)));
        $this->assertFalse(Account::stocksArePristine(array_merge(
            Account::resolveInitialStocks(false, false),
            ['ps4_primary_stock' => 0]
        )));
    }
}
