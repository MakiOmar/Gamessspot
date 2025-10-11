<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('device_repairs', function (Blueprint $table) {
            // Add device_model_id foreign key
            $table->foreignId('device_model_id')->nullable()->after('id')->constrained('device_models')->onDelete('set null');
            
            // Make device_model column nullable for backward compatibility
            $table->string('device_model')->nullable()->change();
            
            $table->index('device_model_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_repairs', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['device_model_id']);
            $table->dropColumn('device_model_id');
            
            // Make device_model column required again
            $table->string('device_model')->nullable(false)->change();
        });
    }
};
