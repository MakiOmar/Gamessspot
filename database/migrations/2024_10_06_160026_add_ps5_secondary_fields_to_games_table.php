<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPs5SecondaryFieldsToGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->decimal('ps5_secondary_price', 8, 2)->default(0); // Adding price field
            $table->integer('ps5_secondary_status')->default(0);      // Adding status field
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('ps5_secondary_price');
            $table->dropColumn('ps5_secondary_status');
        });
    }
}
