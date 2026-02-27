<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('cotizaciones', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('team_id');
            }
            if (!Schema::hasColumn('cotizaciones', 'estado_comercial')) {
                $table->string('estado_comercial', 16)->default('OPEN')->after('created_by_user_id');
                $table->index('estado_comercial');
            }
            if (!Schema::hasColumn('cotizaciones', 'probabilidad')) {
                $table->decimal('probabilidad', 5, 2)->default(0.20)->after('estado_comercial');
            }
            if (!Schema::hasColumn('cotizaciones', 'descuento_pct')) {
                $table->decimal('descuento_pct', 5, 2)->default(0)->after('probabilidad');
            }
            if (!Schema::hasColumn('cotizaciones', 'segmento_id')) {
                $table->foreignId('segmento_id')->nullable()->constrained('comercial_segmentos')->nullOnDelete()->after('descuento_pct');
            }
            if (!Schema::hasColumn('cotizaciones', 'canal_id')) {
                $table->foreignId('canal_id')->nullable()->constrained('comercial_canales')->nullOnDelete()->after('segmento_id');
            }
            if (!Schema::hasColumn('cotizaciones', 'cierre_estimado')) {
                $table->date('cierre_estimado')->nullable()->after('canal_id');
            }
            if (!Schema::hasColumn('cotizaciones', 'vigencia_hasta')) {
                $table->date('vigencia_hasta')->nullable()->after('cierre_estimado');
            }
            if (!Schema::hasColumn('cotizaciones', 'motivo_ganada_id')) {
                $table->foreignId('motivo_ganada_id')->nullable()->constrained('comercial_motivos_ganada')->nullOnDelete()->after('vigencia_hasta');
            }
            if (!Schema::hasColumn('cotizaciones', 'motivo_perdida_id')) {
                $table->foreignId('motivo_perdida_id')->nullable()->constrained('comercial_motivos_perdida')->nullOnDelete()->after('motivo_ganada_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('cotizaciones', 'motivo_perdida_id')) {
                $table->dropConstrainedForeignId('motivo_perdida_id');
            }
            if (Schema::hasColumn('cotizaciones', 'motivo_ganada_id')) {
                $table->dropConstrainedForeignId('motivo_ganada_id');
            }
            if (Schema::hasColumn('cotizaciones', 'vigencia_hasta')) {
                $table->dropColumn('vigencia_hasta');
            }
            if (Schema::hasColumn('cotizaciones', 'cierre_estimado')) {
                $table->dropColumn('cierre_estimado');
            }
            if (Schema::hasColumn('cotizaciones', 'canal_id')) {
                $table->dropConstrainedForeignId('canal_id');
            }
            if (Schema::hasColumn('cotizaciones', 'segmento_id')) {
                $table->dropConstrainedForeignId('segmento_id');
            }
            if (Schema::hasColumn('cotizaciones', 'descuento_pct')) {
                $table->dropColumn('descuento_pct');
            }
            if (Schema::hasColumn('cotizaciones', 'probabilidad')) {
                $table->dropColumn('probabilidad');
            }
            if (Schema::hasColumn('cotizaciones', 'estado_comercial')) {
                $table->dropIndex(['estado_comercial']);
                $table->dropColumn('estado_comercial');
            }
            if (Schema::hasColumn('cotizaciones', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
        });
    }
};
