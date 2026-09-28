<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('trader_id')->nullable()->after('game_id')
                ->constrained('traders')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->after('trader_id')
                ->constrained('trader_purchase_orders')->restrictOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->after('purchase_order_id')
                ->constrained('trader_purchase_order_items')->restrictOnDelete();
            $table->date('purchase_date')->nullable()->after('purchase_order_item_id')->index();
            $table->decimal('original_cost', 12, 2)->nullable()->after('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(array('purchase_order_item_id'));
            $table->dropForeign(array('purchase_order_id'));
            $table->dropForeign(array('trader_id'));
            $table->dropIndex(array('purchase_date'));
            $table->dropColumn(array(
                'trader_id',
                'purchase_order_id',
                'purchase_order_item_id',
                'purchase_date',
                'original_cost',
            ));
        });
    }
};
