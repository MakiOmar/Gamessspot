<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('card_categories', 'description')) {
                $table->longText('description')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_categories', function (Blueprint $table) {
            if (Schema::hasColumn('card_categories', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
