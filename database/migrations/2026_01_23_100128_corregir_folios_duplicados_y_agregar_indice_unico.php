<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SeriesFacturas;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migración:
     * 1. Corrige todos los folios duplicados en la tabla facturas
     * 2. Agrega un índice único para prevenir futuros duplicados
     */
    public function up(): void
    {
        // PASO 1: Corregir folios duplicados existentes
        $this->corregirFoliosDuplicados();

        // PASO 2: Agregar índice único
        Schema::table('facturas', function (Blueprint $table) {
            $table->unique(['serie', 'folio', 'team_id'], 'unique_folio_factura');
        });
    }

    /**
     * Corrige todos los folios duplicados en la tabla facturas
     */
    private function corregirFoliosDuplicados(): void
    {
        // Obtener todos los duplicados agrupados por serie, folio y team_id
        $duplicados = DB::table('facturas')
            ->select('serie', 'folio', 'team_id', DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
            ->groupBy('serie', 'folio', 'team_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        if ($duplicados->count() === 0) {
            echo "✓ No se encontraron folios duplicados\n";
            return;
        }

        echo "Encontrados {$duplicados->count()} grupos de folios duplicados\n";
        $registrosCorregidos = 0;

        foreach ($duplicados as $duplicado) {
            $ids = explode(',', $duplicado->ids);
            // Mantener el primer registro (más antiguo), corregir los demás
            $primerID = array_shift($ids);

            foreach ($ids as $idDuplicado) {
                // Obtener el siguiente folio disponible para este team y serie
                $maxFolio = DB::table('facturas')
                    ->where('team_id', $duplicado->team_id)
                    ->where('serie', $duplicado->serie)
                    ->max('folio');

                $nuevoFolio = $maxFolio + 1;
                $nuevoDocto = $duplicado->serie . $nuevoFolio;

                // Actualizar el registro duplicado
                DB::table('facturas')
                    ->where('id', $idDuplicado)
                    ->update([
                        'folio' => $nuevoFolio,
                        'docto' => $nuevoDocto,
                        'updated_at' => now(),
                    ]);

                // Actualizar el contador de la serie si es necesario
                $serieRecord = SeriesFacturas::where('team_id', $duplicado->team_id)
                    ->where('serie', $duplicado->serie)
                    ->where('tipo', 'F')
                    ->first();

                if ($serieRecord && $serieRecord->folio < $nuevoFolio) {
                    $serieRecord->update(['folio' => $nuevoFolio]);
                }

                $registrosCorregidos++;
            }
        }

        echo "✓ Corrección completada: {$registrosCorregidos} registros corregidos\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique('unique_folio_factura');
        });

        // Nota: No se revierten las correcciones de folios duplicados
        // ya que esos cambios son necesarios para la integridad de datos
    }
};
