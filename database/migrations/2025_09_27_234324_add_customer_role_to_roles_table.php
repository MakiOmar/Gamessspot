<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert the customer role if it doesn't exist
        DB::table('roles')->updateOrInsert(
            ['name' => 'customer'],
            [
                'name' => 'customer',
                'capabilities' => json_encode(['view_device_tracking', 'submit_device_request']),
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the customer role if the migration is rolled back
        DB::table('roles')->where('name', 'customer')->delete();
    }
};
