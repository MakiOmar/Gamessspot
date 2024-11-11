<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCardCategoriesTableReplaceDescriptionWithPrice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->dropColumn('description');  // Remove the description column
            $table->decimal('price', 8, 2)->nullable(); // Add the price column with decimal type
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('card_categories', function (Blueprint $table) {
            $table->text('description')->nullable();  // Re-add description column for rollback
            $table->dropColumn('price'); // Remove price column for rollback
        });
    }
}
