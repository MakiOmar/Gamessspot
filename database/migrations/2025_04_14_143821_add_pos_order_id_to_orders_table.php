<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POS transaction id an order was sent to (manual "Send to POS" or POS/storefront allocation).
 * Null = not sent yet; drives the green POS badge and the unsent-orders filter.
 *
 * Keeps the original migration name: existing databases already recorded it, so they skip
 * this file; the column guard covers databases imported from a dump without the record.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'pos_order_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('pos_order_id')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'pos_order_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pos_order_id');
        });
    }
};
