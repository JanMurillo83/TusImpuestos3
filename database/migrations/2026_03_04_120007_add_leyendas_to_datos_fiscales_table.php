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
        Schema::table('datos_fiscales', function (Blueprint $table) {
            $table->text('leyenda_cotizaciones')->nullable();
            $table->text('leyenda_facturas')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datos_fiscales', function (Blueprint $table) {
            $table->dropColumn(['leyenda_cotizaciones', 'leyenda_facturas']);
        });
    }
};
