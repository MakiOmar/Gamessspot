<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'storefront_order_id')) {
                $table->string('storefront_order_id', 191)->nullable()->after('woocommerce_order_id');
                $table->index('storefront_order_id');
            }
            if (! Schema::hasColumn('orders', 'storefront_line_key')) {
                $table->string('storefront_line_key', 191)->nullable()->after('storefront_order_id');
                $table->index(['storefront_order_id', 'storefront_line_key'], 'orders_storefront_line_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'storefront_line_key')) {
                $table->dropIndex('orders_storefront_line_idx');
                $table->dropColumn('storefront_line_key');
            }
            if (Schema::hasColumn('orders', 'storefront_order_id')) {
                $table->dropIndex(['storefront_order_id']);
                $table->dropColumn('storefront_order_id');
            }
        });
    }
};
