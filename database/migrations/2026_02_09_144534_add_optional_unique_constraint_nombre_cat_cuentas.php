<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTA: Esta migración está comentada por defecto porque tener nombres duplicados
     * puede ser intencional (ej: "Sueldos y salarios" en diferentes secciones del catálogo).
     * Solo descomente si está seguro de que desea impedir nombres duplicados.
     */
    public function up(): void
    {
        // DESCOMENTE SOLO SI DESEA IMPEDIR NOMBRES DUPLICADOS
        // Schema::table('cat_cuentas', function (Blueprint $table) {
        //     $table->unique(['nombre', 'team_id'], 'unique_nombre_team');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('cat_cuentas', function (Blueprint $table) {
        //     $table->dropUnique('unique_nombre_team');
        // });
    }
};
