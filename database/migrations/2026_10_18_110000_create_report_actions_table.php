<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of who changed a report (created, solved, archived, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->timestamps();

            $table->index(array('report_id', 'created_at'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_actions');
    }
};
