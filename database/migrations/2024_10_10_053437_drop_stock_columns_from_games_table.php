<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropStockColumnsFromGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'ps4_offline_stock',
                'ps4_primary_stock',
                'ps4_secondary_stock',
                'ps5_offline_stock',
                'ps5_primary_stock',
                'ps5_secondary_stock',
            ]);
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
            $table->integer('ps4_offline_stock')->nullable();
            $table->integer('ps4_primary_stock')->nullable();
            $table->integer('ps4_secondary_stock')->nullable();
            $table->integer('ps5_offline_stock')->nullable();
            $table->integer('ps5_primary_stock')->nullable();
            $table->integer('ps5_secondary_stock')->nullable();
        });
    }
}
