<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('event_type', 100)->nullable();
            $table->json('payload');
            $table->string('signature', 255)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->index(['provider', 'event_type']);
            $table->index('related_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
