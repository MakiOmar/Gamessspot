<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit for hard DELETEs on next_load_orders, and record MySQL
 * USER()/CURRENT_USER() on order + next_load delete triggers so raw SQL wipes
 * leave a connection identity even when general_log is off.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_delete_audit')) {
            Schema::table('order_delete_audit', function (Blueprint $table) {
                if (! Schema::hasColumn('order_delete_audit', 'mysql_user')) {
                    $table->string('mysql_user', 128)->nullable()->after('source');
                }
                if (! Schema::hasColumn('order_delete_audit', 'mysql_current_user')) {
                    $table->string('mysql_current_user', 128)->nullable()->after('mysql_user');
                }
            });
        }

        if (! Schema::hasTable('next_load_delete_audit')) {
            Schema::create('next_load_delete_audit', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('next_load_order_id');
                $table->string('reference', 191)->nullable();
                $table->string('status', 32)->nullable();
                $table->string('product_name', 191)->nullable();
                $table->string('storefront_order_id', 191)->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->decimal('card_price', 12, 3)->nullable();
                $table->decimal('order_amount', 12, 3)->nullable();
                $table->json('sale_context')->nullable();
                $table->string('source', 32)->default('mysql_trigger');
                $table->string('mysql_user', 128)->nullable();
                $table->string('mysql_current_user', 128)->nullable();
                $table->timestamp('deleted_at')->useCurrent();
                $table->index('next_load_order_id');
                $table->index('storefront_order_id');
                $table->index('deleted_at');
            });
        }

        try {
            DB::unprepared('DROP TRIGGER IF EXISTS orders_before_delete_audit');
            DB::unprepared(<<<'SQL'
CREATE TRIGGER orders_before_delete_audit
BEFORE DELETE ON orders
FOR EACH ROW
BEGIN
  INSERT INTO order_delete_audit (
    order_id, seller_id, store_profile_id, account_id, card_id,
    buyer_phone, buyer_name, price, sold_item, pos_order_id,
    storefront_order_id, storefront_line_key, source,
    mysql_user, mysql_current_user, deleted_at
  ) VALUES (
    OLD.id, OLD.seller_id, OLD.store_profile_id, OLD.account_id, OLD.card_id,
    OLD.buyer_phone, OLD.buyer_name, OLD.price, OLD.sold_item, OLD.pos_order_id,
    OLD.storefront_order_id, OLD.storefront_line_key, 'mysql_trigger',
    USER(), CURRENT_USER(), UTC_TIMESTAMP()
  );
END
SQL);

            DB::unprepared('DROP TRIGGER IF EXISTS next_load_orders_before_delete_audit');
            DB::unprepared(<<<'SQL'
CREATE TRIGGER next_load_orders_before_delete_audit
BEFORE DELETE ON next_load_orders
FOR EACH ROW
BEGIN
  INSERT INTO next_load_delete_audit (
    next_load_order_id, reference, status, product_name, storefront_order_id,
    requested_by, card_price, order_amount, sale_context, source,
    mysql_user, mysql_current_user, deleted_at
  ) VALUES (
    OLD.id, OLD.reference, OLD.status, OLD.product_name, OLD.storefront_order_id,
    OLD.requested_by, OLD.card_price, OLD.order_amount, OLD.sale_context, 'mysql_trigger',
    USER(), CURRENT_USER(), UTC_TIMESTAMP()
  );
END
SQL);
        } catch (\Throwable $e) {
            logger()->warning('delete audit triggers not installed: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS next_load_orders_before_delete_audit');
            DB::unprepared('DROP TRIGGER IF EXISTS orders_before_delete_audit');
            // Restore prior orders trigger without mysql_user columns if rolling back fully.
            DB::unprepared(<<<'SQL'
CREATE TRIGGER orders_before_delete_audit
BEFORE DELETE ON orders
FOR EACH ROW
BEGIN
  INSERT INTO order_delete_audit (
    order_id, seller_id, store_profile_id, account_id, card_id,
    buyer_phone, buyer_name, price, sold_item, pos_order_id,
    storefront_order_id, storefront_line_key, source, deleted_at
  ) VALUES (
    OLD.id, OLD.seller_id, OLD.store_profile_id, OLD.account_id, OLD.card_id,
    OLD.buyer_phone, OLD.buyer_name, OLD.price, OLD.sold_item, OLD.pos_order_id,
    OLD.storefront_order_id, OLD.storefront_line_key, 'mysql_trigger', UTC_TIMESTAMP()
  );
END
SQL);
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::dropIfExists('next_load_delete_audit');

        if (Schema::hasTable('order_delete_audit')) {
            Schema::table('order_delete_audit', function (Blueprint $table) {
                if (Schema::hasColumn('order_delete_audit', 'mysql_current_user')) {
                    $table->dropColumn('mysql_current_user');
                }
                if (Schema::hasColumn('order_delete_audit', 'mysql_user')) {
                    $table->dropColumn('mysql_user');
                }
            });
        }
    }
};
