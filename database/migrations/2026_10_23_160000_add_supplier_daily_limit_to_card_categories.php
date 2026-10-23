<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            // Max codes Next Load may buy per day for sales of this category (null = no limit).
            $table->unsignedSmallInteger('supplier_daily_limit')->nullable()->after('supplier_cost');
        });

        // Not led by card_category_id, so MySQL keeps the foreign key's own index.
        Schema::table('next_load_orders', function (Blueprint $table) {
            $table->index(['purpose', 'card_category_id', 'created_at'], 'nl_orders_category_day_index');
        });
    }

    public function down(): void
    {
        Schema::table('next_load_orders', function (Blueprint $table) {
            $table->dropIndex('nl_orders_category_day_index');
        });

        Schema::table('card_categories', function (Blueprint $table) {
            $table->dropColumn('supplier_daily_limit');
        });
    }
};
