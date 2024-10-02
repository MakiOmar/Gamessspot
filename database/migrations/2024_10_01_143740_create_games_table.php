<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code');
            $table->decimal('full_price', 10, 2)->default(0);
            $table->decimal('ps4_primary_price', 10, 2)->default(0);
            $table->boolean('ps4_primary_status')->default(0);
            $table->decimal('ps4_secondary_price', 10, 2)->default(0);
            $table->boolean('ps4_secondary_status')->default(0);
            $table->decimal('ps4_offline_price', 10, 2)->default(0);
            $table->boolean('ps4_offline_status')->default(0);
            $table->decimal('ps5_primary_price', 10, 2)->default(0);
            $table->boolean('ps5_primary_status')->default(0);
            $table->decimal('ps5_offline_price', 10, 2)->default(0);
            $table->boolean('ps5_offline_status')->default(0);
            $table->string('ps4_image_url')->nullable();
            $table->string('ps5_image_url')->nullable();
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
        Schema::dropIfExists('games');
    }
}
