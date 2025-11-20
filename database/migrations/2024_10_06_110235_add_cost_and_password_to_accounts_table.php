<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostAndPasswordToAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'cost')) {
                $table->decimal('cost', 8, 2)->default(0.00); // Add the cost column (with default value)
            }
            if (!Schema::hasColumn('accounts', 'password')) {
                $table->string('password'); // Add the password column
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
            $table->dropColumn('cost');
            $table->dropColumn('password');
        });
    }
}
