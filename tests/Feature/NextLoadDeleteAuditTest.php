<?php

namespace Tests\Feature;

use App\Models\NextLoadOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NextLoadDeleteAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('next_load_delete_audit')) {
            $this->artisan('migrate', [
                '--path' => 'database/migrations/2026_11_22_120000_next_load_delete_audit_and_mysql_user.php',
                '--force' => true,
            ]);
        }
    }

    public function test_hard_delete_writes_next_load_delete_audit_via_trigger(): void
    {
        if (! Schema::hasTable('next_load_delete_audit')) {
            $this->markTestSkipped('next_load_delete_audit table missing');
        }

        $id = DB::table('next_load_orders')->insertGetId([
            'reference' => 'audit-nl-'.uniqid(),
            'status' => NextLoadOrder::STATUS_COMPLETE,
            'purpose' => NextLoadOrder::PURPOSE_SALE,
            'product_name' => 'Audit Card',
            'quantity' => 1,
            'delivered_quantity' => 1,
            'card_price' => 10.5,
            'order_amount' => 10.5,
            'storefront_order_id' => 'web-audit-nl-'.uniqid(),
            'requested_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('next_load_orders')->where('id', $id)->delete();

        $this->assertDatabaseHas('next_load_delete_audit', [
            'next_load_order_id' => $id,
            'source' => 'mysql_trigger',
        ]);
    }
}
