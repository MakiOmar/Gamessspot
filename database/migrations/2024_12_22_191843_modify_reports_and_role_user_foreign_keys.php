<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyReportsAndRoleUserForeignKeys extends Migration
{
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreign('seller_id')
                ->references('id')->on('users')
                ->onDelete('set null'); // Change to SET NULL
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null'); // Change to SET NULL
        });
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['seller_id']); // Drop the modified foreign key
            $table->foreign('seller_id')
                ->references('id')->on('users')
                ->onDelete('cascade'); // Restore to CASCADE
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Drop the modified foreign key
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade'); // Restore to CASCADE
        });
    }
}
