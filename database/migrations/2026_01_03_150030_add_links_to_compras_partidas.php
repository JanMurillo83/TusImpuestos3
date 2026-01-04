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
        Schema::table('compras_partidas', function (Blueprint $table) {
            if (!Schema::hasColumn('compras_partidas', 'orden_partida_id')) {
                $table->unsignedBigInteger('orden_partida_id')->nullable()->after('idorden');
                $table->index('orden_partida_id');
            }
            if (!Schema::hasColumn('compras_partidas', 'requisicion_partida_id')) {
                $table->unsignedBigInteger('requisicion_partida_id')->nullable()->after('orden_partida_id');
                $table->index('requisicion_partida_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras_partidas', function (Blueprint $table) {
            if (Schema::hasColumn('compras_partidas', 'orden_partida_id')) {
                $table->dropIndex(['orden_partida_id']);
                $table->dropColumn('orden_partida_id');
            }
            if (Schema::hasColumn('compras_partidas', 'requisicion_partida_id')) {
                $table->dropIndex(['requisicion_partida_id']);
                $table->dropColumn('requisicion_partida_id');
            }
        });
    }
};
