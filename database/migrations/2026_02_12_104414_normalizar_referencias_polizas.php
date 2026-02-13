<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalizar referencias en cat_polizas (quitar prefijo 'F-')
        DB::statement("
            UPDATE cat_polizas
            SET referencia = TRIM(LEADING 'F-' FROM referencia)
            WHERE referencia LIKE 'F-%'
        ");

        // Normalizar referencias en auxiliares (campo factura)
        DB::statement("
            UPDATE auxiliares
            SET factura = TRIM(LEADING 'F-' FROM factura)
            WHERE factura LIKE 'F-%'
        ");

        // Limpiar referencias vacías
        DB::statement("
            UPDATE cat_polizas
            SET referencia = NULL
            WHERE referencia = '' OR referencia = 'F-'
        ");

        DB::statement("
            UPDATE auxiliares
            SET factura = NULL
            WHERE factura = '' OR factura = 'F-'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay rollback necesario, los datos originales están transformados
        // Si se requiere rollback, se agregaría el prefijo 'F-' de nuevo
    }
};
