<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('next_load_orders', function (Blueprint $table) {
            // Buyer, price and store of a manager sale, so the sale can be recorded when a delayed code arrives.
            $table->json('sale_context')->nullable()->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('next_load_orders', function (Blueprint $table) {
            $table->dropColumn('sale_context');
        });
    }
};
