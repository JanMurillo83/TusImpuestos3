<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas_partidas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas_partidas', 'remision_partida_id')) {
                $table->unsignedBigInteger('remision_partida_id')->nullable()->after('siguiente');
                $table->index('remision_partida_id');
            }
            if (!Schema::hasColumn('facturas_partidas', 'pedido_partida_id')) {
                $table->unsignedBigInteger('pedido_partida_id')->nullable()->after('remision_partida_id');
                $table->index('pedido_partida_id');
            }
            if (!Schema::hasColumn('facturas_partidas', 'cotizacion_partida_id')) {
                $table->unsignedBigInteger('cotizacion_partida_id')->nullable()->after('pedido_partida_id');
                $table->index('cotizacion_partida_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas_partidas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas_partidas', 'remision_partida_id')) {
                $table->dropIndex(['remision_partida_id']);
                $table->dropColumn('remision_partida_id');
            }
            if (Schema::hasColumn('facturas_partidas', 'pedido_partida_id')) {
                $table->dropIndex(['pedido_partida_id']);
                $table->dropColumn('pedido_partida_id');
            }
            if (Schema::hasColumn('facturas_partidas', 'cotizacion_partida_id')) {
                $table->dropIndex(['cotizacion_partida_id']);
                $table->dropColumn('cotizacion_partida_id');
            }
        });
    }
};
