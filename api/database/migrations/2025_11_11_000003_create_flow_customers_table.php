<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_customers', function (Blueprint $table) {
            $table->id();
            
            // Relación con empresa
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Datos del cliente Flow
            $table->string('flow_customer_id')->index(); // ID del cliente en Flow
            $table->string('external_id')->index(); // ID externo (nuestro sistema)
            $table->string('name');
            $table->string('email');
            
            // Estado de registro de tarjeta
            $table->boolean('has_registered_card')->default(false);
            $table->string('card_last_digits', 4)->nullable();
            $table->string('card_brand')->nullable(); // visa, mastercard, etc.
            $table->timestamp('card_registered_at')->nullable();
            
            // Estado del cliente
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active');
            
            // Respuestas de Flow
            $table->json('flow_response')->nullable(); // Respuesta completa al crear cliente
            $table->json('registration_response')->nullable(); // Respuesta del registro de tarjeta
            
            $table->timestamps();
            
            // Índices únicos
            $table->unique(['company_id', 'external_id']); // Un cliente por external_id por empresa
            $table->unique(['company_id', 'flow_customer_id']); // Un flow_customer_id por empresa
            $table->index(['company_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_customers');
    }
};