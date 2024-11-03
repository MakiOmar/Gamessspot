<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAccountManagerRoleToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insert the new 'account manager' role
        DB::table('roles')->insert([
            ['name' => 'account manager'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the 'account manager' role if the migration is rolled back
        DB::table('roles')->where('name', 'account manager')->delete();
    }
}
