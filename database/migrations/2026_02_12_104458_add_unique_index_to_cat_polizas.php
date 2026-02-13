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
        // Verificar si existen duplicados
        $duplicados = DB::select("
            SELECT
                team_id,
                tipo,
                folio,
                periodo,
                ejercicio,
                COUNT(*) as cantidad
            FROM cat_polizas
            GROUP BY team_id, tipo, folio, periodo, ejercicio
            HAVING cantidad > 1
        ");

        if (!empty($duplicados)) {
            echo "\n";
            echo "╔═══════════════════════════════════════════════════════════════╗\n";
            echo "║  ⚠️  ADVERTENCIA: PÓLIZAS DUPLICADAS DETECTADAS             ║\n";
            echo "╚═══════════════════════════════════════════════════════════════╝\n";
            echo "\n";
            echo "Se encontraron " . count($duplicados) . " grupos de pólizas con folios duplicados.\n";
            echo "\n";
            echo "ACCIÓN REQUERIDA:\n";
            echo "1. Ejecuta el comando de análisis para revisar los duplicados:\n";
            echo "   php artisan polizas:analizar-duplicados --export\n";
            echo "\n";
            echo "2. Revisa el reporte generado en storage/app/\n";
            echo "\n";
            echo "3. Corrige o elimina manualmente las pólizas duplicadas\n";
            echo "\n";
            echo "4. Vuelve a ejecutar la migración\n";
            echo "\n";
            echo "La migración se ha detenido para proteger tus datos.\n";
            echo "\n";

            throw new \Exception(
                "No se puede crear el índice único porque existen pólizas con folios duplicados. " .
                "Ejecuta 'php artisan polizas:analizar-duplicados --export' para analizar los duplicados."
            );
        }

        // Si no hay duplicados, crear el índice único
        Schema::table('cat_polizas', function (Blueprint $table) {
            $table->unique(['team_id', 'tipo', 'folio', 'periodo', 'ejercicio'], 'unique_poliza_folio');
        });

        echo "\n✓ Índice único creado exitosamente. No se encontraron duplicados.\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cat_polizas', function (Blueprint $table) {
            $table->dropUnique('unique_poliza_folio');
        });
    }
};
