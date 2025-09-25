<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_provider')) {
                $table->string('payment_provider', 40)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('payments', 'provider_payment_id')) {
                $table->string('provider_payment_id', 120)->nullable()->after('payment_provider');
            }
            if (!Schema::hasColumn('payments', 'intent_status')) {
                $table->string('intent_status', 40)->nullable()->after('provider_payment_id'); // created|initiated|authorized|paid|failed|refunded
            }
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->after('intent_status');
            }
            if (!Schema::hasColumn('payments', 'raw_gateway_response')) {
                $table->json('raw_gateway_response')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach ([
                'raw_gateway_response',
                'paid_at',
                'intent_status',
                'provider_payment_id',
                'payment_provider'
            ] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
