<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySellerIdNullableInReports extends Migration
{
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_id')->nullable(false)->change();
        });
    }
}
