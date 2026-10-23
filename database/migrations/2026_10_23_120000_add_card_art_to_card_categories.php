<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generated gift card artwork: the Next Load product name it is parsed from, plus manual overrides.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->string('supplier_product_name')->nullable()->after('next_load_product_id');
            $table->string('art_brand', 30)->nullable()->after('supplier_synced_at');
            $table->string('art_country', 2)->nullable()->after('art_brand');
            $table->string('art_value', 20)->nullable()->after('art_country');
        });
    }

    public function down(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->dropColumn(['supplier_product_name', 'art_brand', 'art_country', 'art_value']);
        });
    }
};
