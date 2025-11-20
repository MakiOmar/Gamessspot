<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role'); // Remove the single 'role' column
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('role')->default(1); // Optionally restore the role column if you roll back
        });
    }
};
