<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeSellerAndAccountNullableInOrders extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Modify the columns to be nullable
            if (Schema::hasColumn('orders', 'seller_id')) {
                $table->unsignedBigInteger('seller_id')->nullable()->change();
            }
            if (Schema::hasColumn('orders', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert the columns to not nullable
            $table->unsignedBigInteger('seller_id')->nullable(false)->change();
            $table->unsignedBigInteger('account_id')->nullable(false)->change();
        });
    }
}
