<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGameIdToAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'game_id')) {
                // Add game_id as a foreign key
                $table->foreignId('game_id')->constrained()->onDelete('cascade')->after('ps5_secondary_stock');
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
        Schema::table('accounts', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['game_id']);
            // Drop the game_id column
            $table->dropColumn('game_id');
        });
    }
}
