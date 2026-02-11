<?php

namespace App\Console\Commands;

use App\Services\PolizaCierreService;
use Illuminate\Console\Command;

class GenerarPolizaCierre extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poliza:cierre
                            {team_id : ID de la empresa}
                            {ejercicio : Año del ejercicio a cerrar}
                            {--periodo=12 : Periodo donde generar (12 o 13)}
                            {--cuenta-resultado=304001 : Código de cuenta para resultado del ejercicio}
                            {--eliminar : Eliminar póliza de cierre existente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera o elimina la póliza de cierre del ejercicio contable';

    protected $polizaCierreService;

    public function __construct(PolizaCierreService $polizaCierreService)
    {
        parent::__construct();
        $this->polizaCierreService = $polizaCierreService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $team_id = $this->argument('team_id');
        $ejercicio = $this->argument('ejercicio');
        $periodo = $this->option('periodo');
        $cuentaResultado = $this->option('cuenta-resultado');
        $eliminar = $this->option('eliminar');

        try {
            if ($eliminar) {
                $this->info("Eliminando póliza de cierre...");
                $this->polizaCierreService->eliminarPolizaCierre($team_id, $ejercicio, $periodo);
                $this->info("✓ Póliza de cierre eliminada exitosamente");
                return Command::SUCCESS;
            }

            $this->info("Generando póliza de cierre para el ejercicio {$ejercicio}...");
            $poliza = $this->polizaCierreService->generarPolizaCierre($team_id, $ejercicio, $periodo, $cuentaResultado);

            $this->newLine();
            $this->info("✓ Póliza de cierre generada exitosamente");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID Póliza', $poliza->id],
                    ['Tipo', $poliza->tipo],
                    ['Folio', $poliza->folio],
                    ['Fecha', $poliza->fecha->format('Y-m-d')],
                    ['Periodo', $poliza->periodo],
                    ['Ejercicio', $poliza->ejercicio],
                    ['Cargos', '$' . number_format($poliza->cargos, 2)],
                    ['Abonos', '$' . number_format($poliza->abonos, 2)],
                    ['Resultado', $poliza->abonos - $poliza->cargos >= 0 ? 'UTILIDAD' : 'PÉRDIDA'],
                    ['Monto Resultado', '$' . number_format(abs($poliza->abonos - $poliza->cargos), 2)],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
