<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\StoresProfile;
use App\Models\SystemActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderSoftDeleteAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasColumn('orders', 'deleted_at')) {
            $this->artisan('migrate', [
                '--path' => 'database/migrations/2026_11_22_012000_orders_soft_deletes_and_delete_audit.php',
                '--force' => true,
            ]);
        }
    }

    public function test_soft_delete_logs_order_deleted_and_keeps_row(): void
    {
        $store = StoresProfile::factory()->create();
        $seller = User::factory()->create();
        $order = Order::create([
            'seller_id' => $seller->id,
            'store_profile_id' => $store->id,
            'buyer_phone' => '010'.random_int(10000000, 99999999),
            'buyer_name' => 'Audit Buyer',
            'price' => 100,
            'sold_item' => 'card',
            'storefront_order_id' => 'web-audit-'.uniqid(),
            'pos_order_id' => '99991',
        ]);

        $order->delete();

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertTrue(
            SystemActivityLog::query()
                ->where('action', 'order.deleted')
                ->where('subject_id', $order->id)
                ->where('meta->storefront_order_id', $order->storefront_order_id)
                ->exists()
        );
    }

    public function test_hard_delete_writes_order_delete_audit_via_trigger(): void
    {
        if (! Schema::hasTable('order_delete_audit')) {
            $this->markTestSkipped('order_delete_audit table missing');
        }

        $store = StoresProfile::factory()->create();
        $order = Order::create([
            'store_profile_id' => $store->id,
            'buyer_phone' => '010'.random_int(10000000, 99999999),
            'buyer_name' => 'Hard Delete Buyer',
            'price' => 200,
            'sold_item' => 'card',
            'storefront_order_id' => 'web-hard-'.uniqid(),
        ]);

        DB::table('orders')->where('id', $order->id)->delete();

        $this->assertDatabaseHas('order_delete_audit', [
            'order_id' => $order->id,
            'storefront_order_id' => $order->storefront_order_id,
            'source' => 'mysql_trigger',
        ]);
    }
}
