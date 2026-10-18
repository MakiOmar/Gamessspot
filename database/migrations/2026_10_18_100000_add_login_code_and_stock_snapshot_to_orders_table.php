<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Login code handed to the buyer, so undo can return it to the account.
            $table->string('login_code', 191)->nullable()->after('sold_item');
            // Offline stocks just before a full sale, so undo does not recreate slots already sold.
            $table->json('stock_snapshot')->nullable()->after('login_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(array('login_code', 'stock_snapshot'));
        });
    }
};
