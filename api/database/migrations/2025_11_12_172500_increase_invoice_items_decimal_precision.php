<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('quantity', 15, 3)->change(); // Más precisión para cantidades
            $table->decimal('unit_price', 15, 2)->change(); // Precios hasta $9,999,999,999,999.99
            $table->decimal('amount', 15, 2)->change(); // Totales hasta $9,999,999,999,999.99
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change();
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('amount', 10, 2)->change();
        });
    }
};