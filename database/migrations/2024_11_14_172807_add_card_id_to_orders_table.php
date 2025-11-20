<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardIdToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'card_id')) {
                $table->unsignedBigInteger('card_id')->nullable()->after('sold_item');  // Add the card_id column
                $table->foreign('card_id')->references('id')->on('cards');  // Foreign key constraint
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['card_id']);  // Remove foreign key constraint
            $table->dropColumn('card_id');     // Remove the card_id column
        });
    }
}

