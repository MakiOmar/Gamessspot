<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
            $table->id();  // Primary key
            $table->unsignedBigInteger('seller_id');  // Reference to the seller
            $table->unsignedBigInteger('account_id');  // Reference to the account sold
            $table->string('buyer_phone', 15);  // Buyer's phone number
            $table->string('buyer_name', 100);  // Buyer's name
            $table->decimal('price', 10, 2);  // Price of the order
            $table->text('notes')->nullable();  // Optional notes
            $table->string('sold_item', 255);  // Description of the sold item
            $table->timestamps();  // Created at and updated at

            // Foreign key constraints without cascading deletes
            $table->foreign('seller_id')->references('id')->on('users');
            $table->foreign('account_id')->references('id')->on('accounts');
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
        Schema::dropIfExists('orders');
    }
}

