<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_ops_settings')) {
            Schema::create('system_ops_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('auto_backup_enabled')->default(true);
                $table->string('auto_backup_interval', 20)->default('daily');
                $table->timestamp('last_backup_at')->nullable();
                $table->timestamps();
            });
        }

        if (DB::table('system_ops_settings')->count() === 0) {
            DB::table('system_ops_settings')->insert(array(
                'auto_backup_enabled' => true,
                'auto_backup_interval' => 'daily',
                'last_backup_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_ops_settings');
    }
};
