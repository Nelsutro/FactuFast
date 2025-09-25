<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_provider')) return; // asegurar migración previa
            $indexes = [
                ['col' => 'payment_provider'],
                ['col' => 'provider_payment_id'],
                ['col' => 'intent_status'],
            ];
            foreach ($indexes as $idx) {
                $col = $idx['col'];
                $indexName = 'payments_' . $col . '_idx';
                // Laravel no expone API directa para chequear índice por nombre; confiar en idempotencia deploy
                try { $table->index($col, $indexName); } catch (Throwable $e) { /* ignorar si existe */ }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['payment_provider','provider_payment_id','intent_status'] as $col) {
                $indexName = 'payments_' . $col . '_idx';
                try { $table->dropIndex($indexName); } catch (Throwable $e) { /* ignorar */ }
            }
        });
    }
};
