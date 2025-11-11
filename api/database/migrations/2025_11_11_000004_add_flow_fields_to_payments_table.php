<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Campos para Flow.cl
            if (!Schema::hasColumn('payments', 'flow_customer_id')) {
                $table->foreignId('flow_customer_id')->nullable()->constrained('flow_customers')->onDelete('set null')->after('invoice_id');
            }
            
            if (!Schema::hasColumn('payments', 'subject')) {
                $table->string('subject')->nullable()->after('amount');
            }
            
            if (!Schema::hasColumn('payments', 'email')) {
                $table->string('email')->nullable()->after('subject');
            }
            
            if (!Schema::hasColumn('payments', 'optional')) {
                $table->text('optional')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('payments', 'url_return')) {
                $table->text('url_return')->nullable()->after('optional');
            }
            
            if (!Schema::hasColumn('payments', 'url_confirmation')) {
                $table->text('url_confirmation')->nullable()->after('url_return');
            }
            
            if (!Schema::hasColumn('payments', 'timeout')) {
                $table->integer('timeout')->nullable()->after('url_confirmation');
            }
            
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['direct', 'customer', 'invoice'])->nullable()->after('payment_method');
            }
            
            if (!Schema::hasColumn('payments', 'flow_order')) {
                $table->string('flow_order')->nullable()->index()->after('transaction_id');
            }
            
            if (!Schema::hasColumn('payments', 'token')) {
                $table->string('token')->nullable()->unique()->after('flow_order');
            }
            
            if (!Schema::hasColumn('payments', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('paid_at');
            }
            
            if (!Schema::hasColumn('payments', 'flow_response')) {
                $table->json('flow_response')->nullable()->after('raw_gateway_response');
            }
            
            if (!Schema::hasColumn('payments', 'reference')) {
                $table->string('reference')->nullable()->after('transaction_id');
            }

            // Modificar campo status para incluir estados de Flow
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending')->change();
            
            // Hacer campos opcionales si no lo son
            $table->foreignId('client_id')->nullable()->change();
            $table->foreignId('invoice_id')->nullable()->change();
        });
        
        // Agregar índices adicionales
        Schema::table('payments', function (Blueprint $table) {
            if (!collect(Schema::getIndexes('payments'))->contains('name', 'payments_flow_order_index')) {
                $table->index(['company_id', 'flow_order'], 'payments_company_flow_order_index');
            }
            if (!collect(Schema::getIndexes('payments'))->contains('name', 'payments_payment_type_index')) {
                $table->index(['company_id', 'payment_type'], 'payments_company_payment_type_index');
            }
            if (!collect(Schema::getIndexes('payments'))->contains('name', 'payments_email_index')) {
                $table->index(['email'], 'payments_email_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Eliminar índices
            $indexes = ['payments_company_flow_order_index', 'payments_company_payment_type_index', 'payments_email_index'];
            foreach ($indexes as $index) {
                if (collect(Schema::getIndexes('payments'))->contains('name', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Eliminar columnas agregadas (en orden inverso)
            $columns = [
                'flow_response', 'confirmed_at', 'token', 'flow_order', 'reference',
                'payment_type', 'timeout', 'url_confirmation', 'url_return', 
                'optional', 'email', 'subject', 'flow_customer_id'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Revertir cambios en campos existentes
            $table->enum('status', ['completed', 'pending', 'failed'])->default('pending')->change();
            $table->foreignId('client_id')->nullable(false)->change();
            $table->foreignId('invoice_id')->nullable(false)->change();
        });
    }
};