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
        Schema::table('saldos_reportes', function (Blueprint $table) {
            if (!Schema::hasColumn('saldos_reportes', 'ejercicio')) {
                $table->integer('ejercicio')->nullable();
            }
            if (!Schema::hasColumn('saldos_reportes', 'periodo')) {
                $table->integer('periodo')->nullable();
            }

            // Agregar índice para mejorar rendimiento de filtros por periodo
            $table->index(['team_id', 'ejercicio', 'periodo'], 'idx_saldos_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saldos_reportes', function (Blueprint $table) {
            $table->dropIndex('idx_saldos_periodo');
            $table->dropColumn(['ejercicio', 'periodo']);
        });
    }
};
