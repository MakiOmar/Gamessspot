<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('order_id'); // Foreign key referencing orders table
            $table->unsignedBigInteger('seller_id'); // Foreign key referencing users table
            $table->enum('status', ['has_problem', 'needs_return']); // Report status
            $table->text('note'); // Note or description of the report
            $table->timestamps(); // Created at and updated at

            // Define the relationship to orders and users
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade'); // Cascade delete if the user is deleted
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
        Schema::dropIfExists('reports');
    }
};
