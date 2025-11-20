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
            $columnsToDrop = [];
            if (Schema::hasColumn('games', 'ps4_offline_stock')) {
                $columnsToDrop[] = 'ps4_offline_stock';
            }
            if (Schema::hasColumn('games', 'ps4_primary_stock')) {
                $columnsToDrop[] = 'ps4_primary_stock';
            }
            if (Schema::hasColumn('games', 'ps4_secondary_stock')) {
                $columnsToDrop[] = 'ps4_secondary_stock';
            }
            if (Schema::hasColumn('games', 'ps5_offline_stock')) {
                $columnsToDrop[] = 'ps5_offline_stock';
            }
            if (Schema::hasColumn('games', 'ps5_primary_stock')) {
                $columnsToDrop[] = 'ps5_primary_stock';
            }
            if (Schema::hasColumn('games', 'ps5_secondary_stock')) {
                $columnsToDrop[] = 'ps5_secondary_stock';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
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
