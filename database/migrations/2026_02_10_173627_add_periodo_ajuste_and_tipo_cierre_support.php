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
        // Agregar columna para indicar si es periodo de ajuste
        Schema::table('conta_periodos', function (Blueprint $table) {
            $table->boolean('es_ajuste')->default(false)->after('estado');
        });

        // Agregar columna para indicar si es póliza de cierre
        Schema::table('cat_polizas', function (Blueprint $table) {
            $table->boolean('es_cierre')->default(false)->after('tiposat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conta_periodos', function (Blueprint $table) {
            $table->dropColumn('es_ajuste');
        });

        Schema::table('cat_polizas', function (Blueprint $table) {
            $table->dropColumn('es_cierre');
        });
    }
};
