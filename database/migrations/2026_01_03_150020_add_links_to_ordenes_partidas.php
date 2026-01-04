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
        Schema::table('ordenes_partidas', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_partidas', 'requisicion_partida_id')) {
                $table->unsignedBigInteger('requisicion_partida_id')->nullable()->after('idcompra');
                $table->index('requisicion_partida_id');
            }
            if (!Schema::hasColumn('ordenes_partidas', 'pendientes')) {
                $table->decimal('pendientes', 18, 8)->nullable()->after('cant');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_partidas', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_partidas', 'requisicion_partida_id')) {
                $table->dropIndex(['requisicion_partida_id']);
                $table->dropColumn('requisicion_partida_id');
            }
            if (Schema::hasColumn('ordenes_partidas', 'pendientes')) {
                $table->dropColumn('pendientes');
            }
        });
    }
};
