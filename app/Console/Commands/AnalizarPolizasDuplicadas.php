<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\CatPolizas;
use App\Models\Auxiliares;

class AnalizarPolizasDuplicadas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:analizar-duplicados {--export : Exportar a archivo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analiza pólizas con folios duplicados para determinar si son realmente duplicados o pólizas diferentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Analizando pólizas duplicadas...');
        $this->newLine();

        // Buscar duplicados
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
            ORDER BY team_id, ejercicio, periodo, tipo, folio
        ");

        if (empty($duplicados)) {
            $this->info('✓ No se encontraron pólizas con folios duplicados');
            return 0;
        }

        $this->warn('Se encontraron ' . count($duplicados) . ' grupos de pólizas con folios duplicados');
        $this->newLine();

        $reporteCompleto = [];
        $totalPolizasDuplicadas = 0;

        foreach ($duplicados as $dup) {
            $polizas = CatPolizas::where('team_id', $dup->team_id)
                ->where('tipo', $dup->tipo)
                ->where('folio', $dup->folio)
                ->where('periodo', $dup->periodo)
                ->where('ejercicio', $dup->ejercicio)
                ->orderBy('id')
                ->get();

            $totalPolizasDuplicadas += $polizas->count();

            $this->line("═══════════════════════════════════════════════════════════════");
            $this->info("Team: {$dup->team_id} | Tipo: {$dup->tipo} | Folio: {$dup->folio} | Periodo: {$dup->periodo}/{$dup->ejercicio}");
            $this->line("Cantidad de pólizas con este folio: {$dup->cantidad}");
            $this->newLine();

            $detalleGrupo = [];

            foreach ($polizas as $index => $poliza) {
                $partidas = Auxiliares::where('cat_polizas_id', $poliza->id)->count();

                $this->line("  Póliza #{$poliza->id} (Registro " . ($index + 1) . " de {$dup->cantidad})");
                $this->line("    Fecha:      {$poliza->fecha->format('Y-m-d')}");
                $this->line("    Concepto:   {$poliza->concepto}");
                $this->line("    Referencia: {$poliza->referencia}");
                $this->line("    Cargos:     $" . number_format($poliza->cargos, 2));
                $this->line("    Abonos:     $" . number_format($poliza->abonos, 2));
                $this->line("    Partidas:   {$partidas}");
                $this->line("    Creado:     {$poliza->created_at}");
                $this->line("    UUID:       {$poliza->uuid}");

                // Determinar si parece duplicado real
                $esPosibleDuplicado = $this->analizarSiEsDuplicado($poliza, $polizas, $index);

                if ($esPosibleDuplicado) {
                    $this->warn("    ⚠ POSIBLE DUPLICADO REAL");
                } else {
                    $this->info("    ✓ Parece ser póliza diferente (mismo folio por error)");
                }

                $this->newLine();

                $detalleGrupo[] = [
                    'id' => $poliza->id,
                    'fecha' => $poliza->fecha->format('Y-m-d'),
                    'concepto' => $poliza->concepto,
                    'referencia' => $poliza->referencia,
                    'cargos' => $poliza->cargos,
                    'abonos' => $poliza->abonos,
                    'partidas' => $partidas,
                    'uuid' => $poliza->uuid,
                    'posible_duplicado' => $esPosibleDuplicado,
                    'creado' => $poliza->created_at->format('Y-m-d H:i:s'),
                ];
            }

            $reporteCompleto[] = [
                'team_id' => $dup->team_id,
                'tipo' => $dup->tipo,
                'folio' => $dup->folio,
                'periodo' => $dup->periodo,
                'ejercicio' => $dup->ejercicio,
                'cantidad' => $dup->cantidad,
                'polizas' => $detalleGrupo,
            ];
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("RESUMEN:");
        $this->info("  - Grupos con duplicados: " . count($duplicados));
        $this->info("  - Total de pólizas afectadas: " . $totalPolizasDuplicadas);
        $this->newLine();

        // Exportar si se solicita
        if ($this->option('export')) {
            $this->exportarReporte($reporteCompleto);
        }

        return 0;
    }

    /**
     * Analiza si una póliza es realmente un duplicado comparándola con otras del mismo grupo
     */
    private function analizarSiEsDuplicado($poliza, $polizas, $indexActual)
    {
        foreach ($polizas as $index => $otraPoliza) {
            if ($index === $indexActual) continue;

            // Si tienen el mismo concepto, fecha y montos, probablemente sea duplicado
            if (
                $poliza->concepto === $otraPoliza->concepto &&
                $poliza->fecha->format('Y-m-d') === $otraPoliza->fecha->format('Y-m-d') &&
                abs($poliza->cargos - $otraPoliza->cargos) < 0.01 &&
                abs($poliza->abonos - $otraPoliza->abonos) < 0.01
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Exporta el reporte a un archivo JSON
     */
    private function exportarReporte($reporte)
    {
        $filename = storage_path('app/polizas_duplicadas_' . date('Y-m-d_His') . '.json');
        file_put_contents($filename, json_encode($reporte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✓ Reporte exportado a: {$filename}");
    }
}
