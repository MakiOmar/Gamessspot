<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stores_profile', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name'); // Store name
            $table->string('phone_number'); // Store phone number
            $table->timestamps(); // Created_at and Updated_at columns
        });
    }

    public function down()
    {
        Schema::dropIfExists('stores_profile');
    }
};
