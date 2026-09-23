<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_activity_logs')) {
            return;
        }

        Schema::create('system_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 64)->index();
            $table->string('subject_type', 64)->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('subject_label')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_activity_logs');
    }
};
