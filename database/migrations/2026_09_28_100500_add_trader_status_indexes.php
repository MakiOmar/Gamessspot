<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite indexes for the per-trader active totals (withSum / withCount filtered by status).
     */
    public function up(): void
    {
        Schema::table('trader_purchase_orders', function (Blueprint $table) {
            $table->index(array('trader_id', 'status'), 'trader_purchase_orders_trader_status_index');
        });

        Schema::table('trader_payments', function (Blueprint $table) {
            $table->index(array('trader_id', 'status'), 'trader_payments_trader_status_index');
        });
    }

    public function down(): void
    {
        $this->dropCompositeIndex('trader_purchase_orders');
        $this->dropCompositeIndex('trader_payments');
    }

    /**
     * MySQL silently drops the FK's auto-created trader_id index once the composite index can back the
     * foreign key, so restore a single-column index first when none is left.
     */
    private function dropCompositeIndex(string $tableName): void
    {
        $hasSingleIndex = collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index) => $index['columns'] === array('trader_id'));

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $hasSingleIndex) {
            if (! $hasSingleIndex) {
                $table->index('trader_id', $tableName . '_trader_id_foreign');
            }
            $table->dropIndex($tableName . '_trader_status_index');
        });
    }
};
