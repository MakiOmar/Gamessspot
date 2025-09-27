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
        Schema::create('device_repairs', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('phone_number');
            $table->string('country_code', 5)->default('+20');
            $table->string('device_model');
            $table->string('device_serial_number');
            $table->text('notes')->nullable();
            $table->enum('status', ['received', 'processing', 'ready', 'delivered'])->default('received');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('tracking_code')->unique();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
            
            $table->index(['phone_number', 'country_code']);
            $table->index('tracking_code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_repairs');
    }
};
