<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual shop listing order: lower numbers list first; NULL games follow, newest first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->nullable()->after('is_featured');
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['display_order']);
            $table->dropColumn('display_order');
        });
    }
};
