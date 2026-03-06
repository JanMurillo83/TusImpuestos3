<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('datos_fiscales', function (Blueprint $table) {
            if (!Schema::hasColumn('datos_fiscales', 'mostrar_clave_partidas')) {
                $table->boolean('mostrar_clave_partidas')->default(true);
            }
            if (!Schema::hasColumn('datos_fiscales', 'logo_ancho')) {
                $table->unsignedSmallInteger('logo_ancho')->default(200);
            }
        });

        DB::table('datos_fiscales')->whereNull('mostrar_clave_partidas')->update([
            'mostrar_clave_partidas' => 1,
        ]);
        DB::table('datos_fiscales')->whereNull('logo_ancho')->update([
            'logo_ancho' => 200,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datos_fiscales', function (Blueprint $table) {
            if (Schema::hasColumn('datos_fiscales', 'mostrar_clave_partidas')) {
                $table->dropColumn('mostrar_clave_partidas');
            }
            if (Schema::hasColumn('datos_fiscales', 'logo_ancho')) {
                $table->dropColumn('logo_ancho');
            }
        });
    }
};
