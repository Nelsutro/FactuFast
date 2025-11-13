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
            if (!Schema::hasColumn('companies', 'flow_api_key')) {
                $table->string('flow_api_key')->nullable();
            }
            if (!Schema::hasColumn('companies', 'flow_secret_key')) {
                $table->string('flow_secret_key')->nullable();
            }
            if (!Schema::hasColumn('companies', 'flow_environment')) {
                $table->enum('flow_environment', ['sandbox', 'production'])->default('sandbox');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['flow_api_key', 'flow_secret_key', 'flow_environment']);
        });
    }
};
