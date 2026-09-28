<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trader_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 20)->nullable()->unique();
            $table->foreignId('trader_id')->constrained('traders')->restrictOnDelete();
            $table->date('purchase_date')->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('trader_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('trader_purchase_orders')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('cost_per_account', 12, 2);
            $table->decimal('total_cost', 14, 2);
            $table->timestamps();

            $table->unique(array('purchase_order_id', 'game_id'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trader_purchase_order_items');
        Schema::dropIfExists('trader_purchase_orders');
    }
};
