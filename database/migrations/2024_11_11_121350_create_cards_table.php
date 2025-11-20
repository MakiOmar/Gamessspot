<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('cards')) {
            Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('cost', 8, 2);
            $table->foreignId('card_category_id')->constrained('card_categories')->onDelete('cascade');
            $table->timestamps();
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
        Schema::dropIfExists('cards');
    }
}
