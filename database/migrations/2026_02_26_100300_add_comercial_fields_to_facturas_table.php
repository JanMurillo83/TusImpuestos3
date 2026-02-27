<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('team_id');
            }
            if (!Schema::hasColumn('facturas', 'segmento_id')) {
                $table->foreignId('segmento_id')->nullable()->constrained('comercial_segmentos')->nullOnDelete()->after('created_by_user_id');
            }
            if (!Schema::hasColumn('facturas', 'canal_id')) {
                $table->foreignId('canal_id')->nullable()->constrained('comercial_canales')->nullOnDelete()->after('segmento_id');
            }
            if (!Schema::hasColumn('facturas', 'motivo_ganada_id')) {
                $table->foreignId('motivo_ganada_id')->nullable()->constrained('comercial_motivos_ganada')->nullOnDelete()->after('canal_id');
            }
            if (!Schema::hasColumn('facturas', 'margen_pct')) {
                $table->decimal('margen_pct', 6, 4)->default(0)->after('motivo_ganada_id');
            }
            if (!Schema::hasColumn('facturas', 'cobranza_pct')) {
                $table->decimal('cobranza_pct', 6, 4)->default(0)->after('margen_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'cobranza_pct')) {
                $table->dropColumn('cobranza_pct');
            }
            if (Schema::hasColumn('facturas', 'margen_pct')) {
                $table->dropColumn('margen_pct');
            }
            if (Schema::hasColumn('facturas', 'motivo_ganada_id')) {
                $table->dropConstrainedForeignId('motivo_ganada_id');
            }
            if (Schema::hasColumn('facturas', 'canal_id')) {
                $table->dropConstrainedForeignId('canal_id');
            }
            if (Schema::hasColumn('facturas', 'segmento_id')) {
                $table->dropConstrainedForeignId('segmento_id');
            }
            if (Schema::hasColumn('facturas', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
        });
    }
};
