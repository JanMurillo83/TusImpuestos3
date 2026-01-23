<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Facturas;
use App\Models\SeriesFacturas;

class CorregirFoliosDuplicados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:corregir-folios-duplicados {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige folios duplicados en facturas asignando nuevos folios consecutivos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se realizarán cambios en la base de datos');
        }

        // Obtener todos los duplicados agrupados por serie, folio y team_id
        $duplicados = DB::table('facturas')
            ->select('serie', 'folio', 'team_id', DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
            ->groupBy('serie', 'folio', 'team_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        $totalDuplicados = $duplicados->count();

        if ($totalDuplicados === 0) {
            $this->info('✓ No se encontraron folios duplicados');
            return 0;
        }

        $this->info("Encontrados {$totalDuplicados} grupos de folios duplicados");

        $registrosCorregidos = 0;

        foreach ($duplicados as $duplicado) {
            $ids = explode(',', $duplicado->ids);
            // Mantener el primer registro (más antiguo), corregir los demás
            $primerID = array_shift($ids);

            $this->line("\n---");
            $this->info("Serie: {$duplicado->serie}, Folio: {$duplicado->folio}, Team: {$duplicado->team_id}");
            $this->line("Manteniendo ID: {$primerID}");
            $this->line("Corrigiendo IDs: " . implode(', ', $ids));

            foreach ($ids as $idDuplicado) {
                // Obtener el siguiente folio disponible para este team y serie
                $maxFolio = DB::table('facturas')
                    ->where('team_id', $duplicado->team_id)
                    ->where('serie', $duplicado->serie)
                    ->max('folio');

                $nuevoFolio = $maxFolio + 1;
                $nuevoDocto = $duplicado->serie . $nuevoFolio;

                $this->warn("  - ID {$idDuplicado}: Cambiar folio {$duplicado->folio} -> {$nuevoFolio}");

                if (!$dryRun) {
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
                }

                $registrosCorregidos++;
            }
        }

        $this->line("\n---");

        if ($dryRun) {
            $this->info("✓ DRY-RUN completado: Se corregirían {$registrosCorregidos} registros");
            $this->line("Ejecuta sin --dry-run para aplicar los cambios");
        } else {
            $this->info("✓ Corrección completada: {$registrosCorregidos} registros corregidos");
        }

        return 0;
    }
}
