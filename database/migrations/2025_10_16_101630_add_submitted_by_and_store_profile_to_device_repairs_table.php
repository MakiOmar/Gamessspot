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
            if (!Schema::hasColumn('device_repairs', 'submitted_by_user_id')) {
                // Add field to track which user (staff) submitted the repair
                $table->foreignId('submitted_by_user_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
                $table->index('submitted_by_user_id');
            }
            
            if (!Schema::hasColumn('device_repairs', 'store_profile_id')) {
                // Add field to track which store profile the repair belongs to
                $table->foreignId('store_profile_id')->nullable()->after('submitted_by_user_id')->constrained('stores_profile')->onDelete('set null');
                $table->index('store_profile_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_repairs', function (Blueprint $table) {
            $table->dropForeign(['submitted_by_user_id']);
            $table->dropForeign(['store_profile_id']);
            $table->dropColumn(['submitted_by_user_id', 'store_profile_id']);
        });
    }
};
