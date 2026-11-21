<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-delete orders for Eloquent undos, plus a BEFORE DELETE trigger so raw SQL
 * wipes (like 2026-11-21 01:35 UTC) still leave an append-only audit row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        Schema::create('order_delete_audit', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->unsignedBigInteger('store_profile_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('card_id')->nullable();
            $table->string('buyer_phone', 32)->nullable();
            $table->string('buyer_name', 191)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('sold_item', 64)->nullable();
            $table->string('pos_order_id', 64)->nullable();
            $table->string('storefront_order_id', 191)->nullable();
            $table->string('storefront_line_key', 191)->nullable();
            $table->string('source', 32)->default('mysql_trigger');
            $table->timestamp('deleted_at')->useCurrent();
            $table->index('order_id');
            $table->index('storefront_order_id');
            $table->index('deleted_at');
        });

        // Hard DELETE only (raw SQL / forceDelete). Soft deletes are UPDATEs.
        // On managed MySQL without SUPER, set log_bin_trust_function_creators=1
        // (or create the trigger as root) — see deploy notes.
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
    storefront_order_id, storefront_line_key, source, deleted_at
  ) VALUES (
    OLD.id, OLD.seller_id, OLD.store_profile_id, OLD.account_id, OLD.card_id,
    OLD.buyer_phone, OLD.buyer_name, OLD.price, OLD.sold_item, OLD.pos_order_id,
    OLD.storefront_order_id, OLD.storefront_line_key, 'mysql_trigger', UTC_TIMESTAMP()
  );
END
SQL);
        } catch (\Throwable $e) {
            // Soft-delete + audit table still land; trigger may need root install.
            logger()->warning('orders_before_delete_audit trigger not installed: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS orders_before_delete_audit');
        } catch (\Throwable $e) {
            // ignore
        }
        Schema::dropIfExists('order_delete_audit');
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
