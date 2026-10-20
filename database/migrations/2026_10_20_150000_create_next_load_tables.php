<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Next Load supplier: per-category sourcing, supplier orders (reference saved before sending) and their codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->string('supplier_mode', 20)->default('local_only')->after('price');
            $table->unsignedInteger('next_load_product_id')->nullable()->index()->after('supplier_mode');
            $table->decimal('supplier_cost', 12, 3)->nullable()->after('next_load_product_id');
            $table->timestamp('supplier_synced_at')->nullable()->after('supplier_cost');
        });

        Schema::create('next_load_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->string('next_load_order_id', 50)->nullable()->index();
            $table->foreignId('card_category_id')->nullable()->constrained('card_categories')->nullOnDelete();
            $table->unsignedInteger('product_id');
            $table->string('product_name')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedSmallInteger('delivered_quantity')->default(0);
            $table->string('status', 20)->default('pending_send')->index();
            $table->string('purpose', 10)->default('sale');
            $table->decimal('card_price', 12, 3)->nullable();
            $table->decimal('order_amount', 12, 3)->nullable();
            $table->string('storefront_order_id', 191)->nullable();
            $table->string('storefront_line_key', 191)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            $table->timestamp('next_poll_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['storefront_order_id', 'storefront_line_key'], 'nl_orders_storefront_line_index');
            $table->index(['status', 'next_poll_at']);
        });

        Schema::create('next_load_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('next_load_order_id')->constrained('next_load_orders')->cascadeOnDelete();
            $table->text('serial')->nullable();
            $table->text('pin');
            $table->string('pin_hash', 64)->unique();
            $table->decimal('price', 12, 3)->default(0);
            $table->timestamps();
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('status');
            $table->foreignId('next_load_order_item_id')->nullable()->after('source')
                ->constrained('next_load_order_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_load_order_item_id');
            $table->dropColumn('source');
        });
        Schema::dropIfExists('next_load_order_items');
        Schema::dropIfExists('next_load_orders');
        Schema::table('card_categories', function (Blueprint $table) {
            $table->dropIndex(['next_load_product_id']);
            $table->dropColumn(['supplier_mode', 'next_load_product_id', 'supplier_cost', 'supplier_synced_at']);
        });
    }
};
