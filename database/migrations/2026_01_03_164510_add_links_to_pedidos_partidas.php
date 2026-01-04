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
        Schema::table('pedidos_partidas', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos_partidas', 'cotizacion_partida_id')) {
                $table->unsignedBigInteger('cotizacion_partida_id')->nullable()->after('siguiente');
                $table->index('cotizacion_partida_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_partidas', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_partidas', 'cotizacion_partida_id')) {
                $table->dropIndex(['cotizacion_partida_id']);
                $table->dropColumn('cotizacion_partida_id');
            }
        });
    }
};
