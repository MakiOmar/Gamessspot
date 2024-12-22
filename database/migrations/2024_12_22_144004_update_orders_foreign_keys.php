<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrdersForeignKeys extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {

            // Re-add foreign keys with onDelete('set null')
            $table->foreign('seller_id')
                ->references('id')->on('users')
                ->onDelete('set null');
            $table->foreign('account_id')
                ->references('id')->on('accounts')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Re-add the original foreign keys without onDelete('set null')
            $table->foreign('seller_id')
                ->references('id')->on('users');
            $table->foreign('account_id')
                ->references('id')->on('accounts');
        });
    }
}
