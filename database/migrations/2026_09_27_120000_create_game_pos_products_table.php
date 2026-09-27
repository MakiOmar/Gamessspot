<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_pos_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('platform', 16);
            $table->string('offer', 16);
            $table->unsignedInteger('pos_product_id');
            $table->unsignedInteger('pos_variation_id');
            $table->boolean('pos_active')->default(true);
            $table->timestamps();

            $table->unique(['game_id', 'platform', 'offer']);
            $table->index('pos_product_id');
        });

        Schema::table('card_categories', function (Blueprint $table) {
            $table->unsignedInteger('pos_product_id')->nullable()->after('price');
            $table->unsignedInteger('pos_variation_id')->nullable()->after('pos_product_id');
            $table->boolean('pos_active')->default(false)->after('pos_variation_id');
            $table->index('pos_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->dropIndex(['pos_product_id']);
            $table->dropColumn(['pos_product_id', 'pos_variation_id', 'pos_active']);
        });

        Schema::dropIfExists('game_pos_products');
    }
};
