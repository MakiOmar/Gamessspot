<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'store_profile_id')) {
                $table->unsignedBigInteger('store_profile_id')->nullable()->after('seller_id');
                $table->foreign('store_profile_id')->references('id')->on('stores_profile')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the foreign key and the column when rolling back
            $table->dropForeign(['store_profile_id']);
            $table->dropColumn('store_profile_id');
        });
    }
};
