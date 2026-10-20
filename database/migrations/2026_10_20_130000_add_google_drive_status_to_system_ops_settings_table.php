<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_ops_settings', function (Blueprint $table) {
            $table->timestamp('drive_last_at')->nullable()->after('last_backup_at');
            $table->string('drive_last_status', 20)->nullable()->after('drive_last_at');
            $table->string('drive_last_message', 1000)->nullable()->after('drive_last_status');
        });
    }

    public function down(): void
    {
        Schema::table('system_ops_settings', function (Blueprint $table) {
            $table->dropColumn(['drive_last_at', 'drive_last_status', 'drive_last_message']);
        });
    }
};
