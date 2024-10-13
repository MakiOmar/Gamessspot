<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_profile_id')
              ->nullable()
              ->after('role') // Adds the column after the 'role' column
              ->constrained('stores_profile');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['store_profile_id']);
            $table->dropColumn('store_profile_id');
        });
    }
};
