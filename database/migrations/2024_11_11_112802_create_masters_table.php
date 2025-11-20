<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('masters')) {
            Schema::create('masters', function (Blueprint $table) {
            $table->id();
            $table->string('mail')->unique();
            $table->string('password');
            $table->string('region');
            $table->decimal('value', 15, 2)->nullable();
            $table->decimal('rate', 8, 4)->nullable();
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
        Schema::dropIfExists('masters');
    }
}
