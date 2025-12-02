<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índices para acelerar consultas frecuentes
        if (Schema::hasTable('auxiliares')) {
            Schema::table('auxiliares', function (Blueprint $table) {
                // combinaciones usadas en where: codigo + a_periodo + a_ejercicio + team_id
                $table->index(['codigo', 'a_periodo', 'a_ejercicio', 'team_id'], 'aux_cod_per_eje_team_idx');
                $table->index(['codigo', 'a_periodo', 'team_id'], 'aux_cod_per_team_idx');
                $table->index(['factura', 'team_id'], 'aux_factura_team_idx');
                $table->index(['cat_polizas_id'], 'aux_poliza_idx');
            });
        }

        if (Schema::hasTable('saldos_reportes')) {
            Schema::table('saldos_reportes', function (Blueprint $table) {
                $table->index(['team_id', 'nivel'], 'salrep_team_nivel_idx');
                $table->index(['team_id', 'codigo'], 'salrep_team_codigo_idx');
                $table->index(['codigo'], 'salrep_codigo_idx');
                $table->index(['acumula'], 'salrep_acumula_idx');
            });
        }

        if (Schema::hasTable('cuentas_cobrar_tables')) {
            Schema::table('cuentas_cobrar_tables', function (Blueprint $table) {
                $table->index(['team_id', 'periodo', 'ejercicio', 'tipo'], 'cc_team_per_eje_tipo_idx');
                $table->index(['documento'], 'cc_documento_idx');
            });
        }

        if (Schema::hasTable('cuentas_pagar_tables')) {
            Schema::table('cuentas_pagar_tables', function (Blueprint $table) {
                $table->index(['team_id', 'periodo', 'ejercicio', 'tipo'], 'cp_team_per_eje_tipo_idx');
                $table->index(['documento'], 'cp_documento_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('auxiliares')) {
            Schema::table('auxiliares', function (Blueprint $table) {
                $table->dropIndex('aux_cod_per_eje_team_idx');
                $table->dropIndex('aux_cod_per_team_idx');
                $table->dropIndex('aux_factura_team_idx');
                $table->dropIndex('aux_poliza_idx');
            });
        }

        if (Schema::hasTable('saldos_reportes')) {
            Schema::table('saldos_reportes', function (Blueprint $table) {
                $table->dropIndex('salrep_team_nivel_idx');
                $table->dropIndex('salrep_team_codigo_idx');
                $table->dropIndex('salrep_codigo_idx');
                $table->dropIndex('salrep_acumula_idx');
            });
        }

        if (Schema::hasTable('cuentas_cobrar_tables')) {
            Schema::table('cuentas_cobrar_tables', function (Blueprint $table) {
                $table->dropIndex('cc_team_per_eje_tipo_idx');
                $table->dropIndex('cc_documento_idx');
            });
        }

        if (Schema::hasTable('cuentas_pagar_tables')) {
            Schema::table('cuentas_pagar_tables', function (Blueprint $table) {
                $table->dropIndex('cp_team_per_eje_tipo_idx');
                $table->dropIndex('cp_documento_idx');
            });
        }
    }
};
