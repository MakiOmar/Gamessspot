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

        // Ensure FK exists when game_id was created without a constraint (migration order)
        $fkExists = collect(\DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'accounts'
              AND COLUMN_NAME = 'game_id'
              AND REFERENCED_TABLE_NAME = 'games'
        "))->isNotEmpty();

        if (!$fkExists && Schema::hasColumn('accounts', 'game_id') && Schema::hasTable('games')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            });
        }
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
