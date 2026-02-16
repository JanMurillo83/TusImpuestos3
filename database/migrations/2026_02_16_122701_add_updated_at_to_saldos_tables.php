<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FASE 2: Agregar columna updated_at para tracking de actualizaciones
     */
    public function up(): void
    {
        // Agregar updated_at a saldoscuentas
        if (Schema::hasTable('saldoscuentas') && !Schema::hasColumn('saldoscuentas', 'updated_at')) {
            Schema::table('saldoscuentas', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('team_id');
                $table->index(['team_id', 'updated_at']);
            });
        }

        // Agregar updated_at a saldos_reportes
        if (Schema::hasTable('saldos_reportes') && !Schema::hasColumn('saldos_reportes', 'updated_at')) {
            Schema::table('saldos_reportes', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('team_id');
                $table->index(['team_id', 'updated_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover updated_at de saldoscuentas
        if (Schema::hasTable('saldoscuentas') && Schema::hasColumn('saldoscuentas', 'updated_at')) {
            Schema::table('saldoscuentas', function (Blueprint $table) {
                $table->dropIndex(['team_id', 'updated_at']);
                $table->dropColumn('updated_at');
            });
        }

        // Remover updated_at de saldos_reportes
        if (Schema::hasTable('saldos_reportes') && Schema::hasColumn('saldos_reportes', 'updated_at')) {
            Schema::table('saldos_reportes', function (Blueprint $table) {
                $table->dropIndex(['team_id', 'updated_at']);
                $table->dropColumn('updated_at');
            });
        }
    }
};
