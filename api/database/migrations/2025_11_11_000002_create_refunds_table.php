<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            
            // Relación con empresa y pago original
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            
            // Datos del reembolso
            $table->string('refund_commerce_order')->index(); // Orden de reembolso del comercio
            $table->string('flow_refund_order')->nullable()->index(); // Número de orden reembolso Flow
            $table->decimal('amount', 12, 2); // Monto a reembolsar
            $table->string('receiver_email'); // Email del receptor
            
            // Referencias a transacción original
            $table->string('original_commerce_order')->nullable();
            $table->string('original_flow_transaction_id')->nullable();
            
            // Estado y resultado
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            
            // Tokens y referencias
            $table->string('flow_token')->nullable()->unique(); // Token de Flow para el reembolso
            $table->string('refund_transaction_id')->nullable(); // ID de transacción del reembolso
            
            // URLs
            $table->text('url_callback'); // URL de callback para notificaciones
            
            // Respuestas y detalles
            $table->json('flow_response')->nullable(); // Respuesta completa de Flow
            $table->json('error_details')->nullable(); // Detalles de error si falla
            $table->text('rejection_reason')->nullable(); // Razón de rechazo
            
            // Fechas
            $table->timestamp('processed_at')->nullable(); // Fecha de procesamiento
            $table->timestamps();
            
            // Índices
            $table->index(['company_id', 'status']);
            $table->index(['receiver_email', 'status']);
            $table->unique(['company_id', 'refund_commerce_order']); // No duplicar órdenes de reembolso
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};