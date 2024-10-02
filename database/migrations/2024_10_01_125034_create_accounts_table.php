<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create games table
 */
class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('mail');
            $table->string('region', 2); // ISO 3166-1 alpha-2 country code
            $table->integer('ps4_offline_stock')->default(0);
            $table->integer('ps4_primary_stock')->default(0);
            $table->integer('ps4_secondary_stock')->default(0);
            $table->integer('ps5_offline_stock')->default(0);
            $table->integer('ps5_primary_stock')->default(0);
            $table->integer('ps5_secondary_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}
