<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['invoice_reminder', 'payment_followup', 'quote_followup', 'report_generation', 'backup']);
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->time('time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run')->nullable();
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
