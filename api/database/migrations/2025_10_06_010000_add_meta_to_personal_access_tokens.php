<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_access_tokens', 'meta')) {
                $table->json('meta')->nullable()->after('abilities');
            }
            if (!Schema::hasColumn('personal_access_tokens', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('personal_access_tokens', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('personal_access_tokens', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
        });
    }
};
