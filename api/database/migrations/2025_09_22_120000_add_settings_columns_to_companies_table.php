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
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'currency_code')) {
                $table->string('currency_code', 3)->default('CLP')->after('address');
            }
            if (!Schema::hasColumn('companies', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('currency_code');
            }
            if (!Schema::hasColumn('companies', 'default_payment_terms')) {
                $table->string('default_payment_terms', 255)->nullable()->after('tax_rate');
            }
            if (!Schema::hasColumn('companies', 'logo_path')) {
                $table->string('logo_path', 255)->nullable()->after('default_payment_terms');
            }
            if (!Schema::hasColumn('companies', 'send_email_on_invoice')) {
                $table->boolean('send_email_on_invoice')->default(true)->after('logo_path');
            }
            if (!Schema::hasColumn('companies', 'send_email_on_payment')) {
                $table->boolean('send_email_on_payment')->default(true)->after('send_email_on_invoice');
            }
            if (!Schema::hasColumn('companies', 'portal_enabled')) {
                $table->boolean('portal_enabled')->default(true)->after('send_email_on_payment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'portal_enabled')) {
                $table->dropColumn('portal_enabled');
            }
            if (Schema::hasColumn('companies', 'send_email_on_payment')) {
                $table->dropColumn('send_email_on_payment');
            }
            if (Schema::hasColumn('companies', 'send_email_on_invoice')) {
                $table->dropColumn('send_email_on_invoice');
            }
            if (Schema::hasColumn('companies', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
            if (Schema::hasColumn('companies', 'default_payment_terms')) {
                $table->dropColumn('default_payment_terms');
            }
            if (Schema::hasColumn('companies', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
            if (Schema::hasColumn('companies', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
