<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Webpay credentials / config
            if (!Schema::hasColumn('companies', 'webpay_environment')) {
                $table->string('webpay_environment', 20)->default('integration')->after('portal_enabled'); // integration | production
            }
            if (!Schema::hasColumn('companies', 'webpay_commerce_code')) {
                $table->string('webpay_commerce_code', 50)->nullable()->after('webpay_environment');
            }
            if (!Schema::hasColumn('companies', 'webpay_api_key')) {
                $table->string('webpay_api_key', 255)->nullable()->after('webpay_commerce_code');
            }

            // MercadoPago credentials
            if (!Schema::hasColumn('companies', 'mp_public_key')) {
                $table->string('mp_public_key', 120)->nullable()->after('webpay_api_key');
            }
            if (!Schema::hasColumn('companies', 'mp_access_token')) {
                $table->string('mp_access_token', 255)->nullable()->after('mp_public_key');
            }

            if (!Schema::hasColumn('companies', 'payment_providers_enabled')) {
                // JSON que lista proveedores habilitados: ["webpay","mercadopago"]
                $table->json('payment_providers_enabled')->nullable()->after('mp_access_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'payment_providers_enabled',
                'mp_access_token',
                'mp_public_key',
                'webpay_api_key',
                'webpay_commerce_code',
                'webpay_environment'
            ] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
