<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trader_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 20)->nullable()->unique();
            $table->foreignId('trader_id')->constrained('traders')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('payment_date')->index();
            $table->string('method', 30);
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trader_payments');
    }
};
