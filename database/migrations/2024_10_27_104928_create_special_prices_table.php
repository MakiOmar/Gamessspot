<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialPricesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('special_prices')) {
            Schema::create('special_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_id');  // Foreign key to games
            $table->unsignedBigInteger('store_profile_id');  // Foreign key to stores_profile

            // Special price columns for each platform and status
            $table->decimal('ps4_primary_price', 10, 2)->default(0.00);
            $table->decimal('ps4_secondary_price', 10, 2)->default(0.00);
            $table->decimal('ps4_offline_price', 10, 2)->default(0.00);
            $table->decimal('ps5_primary_price', 10, 2)->default(0.00);
            $table->decimal('ps5_secondary_price', 10, 2)->default(0.00);
            $table->decimal('ps5_offline_price', 10, 2)->default(0.00);

            // Foreign key constraints
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('store_profile_id')->references('id')->on('stores_profile')->onDelete('cascade');

            // Timestamps
            $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('special_prices');
    }
}
