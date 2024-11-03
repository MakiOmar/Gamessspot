<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAvailableToSpecialPricesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('special_prices', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('ps5_offline_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('special_prices', function (Blueprint $table) {
            $table->dropColumn('is_available');
        });
    }
}
