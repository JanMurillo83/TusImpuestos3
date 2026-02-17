<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\ValidaDescargas;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpCfdi\CfdiSatScraper\Filters\DownloadType;
use PhpCfdi\CfdiSatScraper\Filters\Options\StatesVoucherOption;
use PhpCfdi\CfdiSatScraper\QueryByFilters;
use PhpCfdi\CfdiSatScraper\ResourceType;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;
use PhpCfdi\CfdiSatScraper\SatScraper;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionManager;
use PhpCfdi\Credentials\Credential;
use App\Models\Almacencfdis;
use App\Models\Xmlfiles;
use App\Services\CfdiSatScraperService;
use App\Services\XmlProcessorService;

class DescargaAutomaticaSAT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sat:descargar-automatico
                            {--fecha-inicio= : Fecha inicial (Y-m-d). Por defecto: primer día del mes actual}
                            {--fecha-fin= : Fecha final (Y-m-d). Por defecto: día actual}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga automática de CFDIs del SAT para todos los teams activos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('Iniciando Descarga Automática de CFDIs del SAT');
        $this->info('==============================================');

        // Calcular fechas
        $fecha_inicial = $this->option('fecha-inicio')
            ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $fecha_final = $this->option('fecha-fin')
            ?? Carbon::now()->format('Y-m-d');

        $this->info("Período: {$fecha_inicial} al {$fecha_final}");

        // Limpiar todos los archivos de cookies antes de iniciar
        $this->limpiarCookies();

        // Obtener todos los teams con descarga activa
        $teams = Team::where('descarga_cfdi', 'SI')->get();

        if ($teams->isEmpty()) {
            $this->warn('No hay equipos con descarga de CFDI activa.');
            return 0;
        }

        $this->info("Teams a procesar: {$teams->count()}");
        $this->newLine();

        $exitosos = 0;
        $fallidos = 0;

        foreach ($teams as $record) {
            $this->info("Procesando: {$record->name} (RFC: {$record->taxid})");

            $resultado = $this->procesarTeam($record, $fecha_inicial, $fecha_final);

            if ($resultado['exito']) {
                $exitosos++;
                $this->info("  ✓ Completado - Emitidos: {$resultado['emitidos']}, Recibidos: {$resultado['recibidos']}");
            } else {
                $fallidos++;
                $this->error("  ✗ Error: {$resultado['mensaje']}");
            }

            $this->newLine();
        }

        $this->info('==============================================');
        $this->info("Proceso finalizado");
        $this->info("Exitosos: {$exitosos} | Fallidos: {$fallidos}");
        $this->info('==============================================');

        return 0;
    }

    /**
     * Limpia todos los archivos de cookies
     */
    private function limpiarCookies()
    {
        $cookiePath = storage_path('app/public/cookies/');

        if (!is_dir($cookiePath)) {
            mkdir($cookiePath, 0777, true);
            $this->info('Directorio de cookies creado.');
            return;
        }

        $archivos = File::glob($cookiePath . '*.json');
        $eliminados = 0;

        foreach ($archivos as $archivo) {
            if (File::exists($archivo)) {
                File::delete($archivo);
                $eliminados++;
            }
        }

        if ($eliminados > 0) {
            $this->info("Cookies limpiadas: {$eliminados} archivo(s) eliminado(s).");
        }
    }

    /**
     * Procesa la descarga de un team
     */
    private function procesarTeam($record, $fecha_inicial, $fecha_final)
    {
        try {
            // Inicializar servicios
            $scraperService = new CfdiSatScraperService($record);
            $xmlProcessor = new XmlProcessorService();

            // Validar archivos FIEL
            $validation = $scraperService->validateFielFiles();
            if (!$validation['valid']) {
                ValidaDescargas::create([
                    'fecha' => Carbon::now(),
                    'inicio' => $fecha_inicial,
                    'fin' => $fecha_final,
                    'recibidos' => 0,
                    'emitidos' => 0,
                    'estado' => 'Error: ' . $validation['error'],
                    'team_id' => $record->id
                ]);

                return [
                    'exito' => false,
                    'mensaje' => $validation['error']
                ];
            }

            // Inicializar scraper
            $init = $scraperService->initializeScraper();
            if (!$init['valid']) {
                ValidaDescargas::create([
                    'fecha' => Carbon::now(),
                    'inicio' => $fecha_inicial,
                    'fin' => $fecha_final,
                    'recibidos' => 0,
                    'emitidos' => 0,
                    'estado' => 'Error: ' . $init['error'],
                    'team_id' => $record->id
                ]);

                return [
                    'exito' => false,
                    'mensaje' => $init['error']
                ];
            }

            // Consultar y descargar emitidos
            $emitidosResult = $scraperService->listByPeriod($fecha_inicial, $fecha_final, 'emitidos', true);
            if (!$emitidosResult['success']) {
                throw new \Exception('Error consultando emitidos: ' . $emitidosResult['error']);
            }

            $scraperService->downloadResources($emitidosResult['list'], 'xml', 'emitidos', 50);
            $scraperService->downloadResources($emitidosResult['list'], 'pdf', 'emitidos', 50);

            // Consultar y descargar recibidos
            $recibidosResult = $scraperService->listByPeriod($fecha_inicial, $fecha_final, 'recibidos', true);
            if (!$recibidosResult['success']) {
                throw new \Exception('Error consultando recibidos: ' . $recibidosResult['error']);
            }

            $scraperService->downloadResources($recibidosResult['list'], 'xml', 'recibidos', 50);
            $scraperService->downloadResources($recibidosResult['list'], 'pdf', 'recibidos', 50);

            // Procesar archivos XML y PDF
            $config = $scraperService->getConfig();
            $xmlProcessor->processDirectory($config['downloadsPath']['xml_emitidos'], $record->id, 'Emitidos');
            $xmlProcessor->processDirectory($config['downloadsPath']['xml_recibidos'], $record->id, 'Recibidos');
            $xmlProcessor->processPdfDirectory($config['downloadsPath']['pdf'], $record->id);

            // Registrar éxito
            ValidaDescargas::create([
                'fecha' => Carbon::now(),
                'inicio' => $fecha_inicial,
                'fin' => $fecha_final,
                'recibidos' => $recibidosResult['count'],
                'emitidos' => $emitidosResult['count'],
                'estado' => 'Completado',
                'team_id' => $record->id
            ]);

            return [
                'exito' => true,
                'emitidos' => $emitidosResult['count'],
                'recibidos' => $recibidosResult['count']
            ];

        } catch (\Exception $e) {
            ValidaDescargas::create([
                'fecha' => Carbon::now(),
                'inicio' => $fecha_inicial,
                'fin' => $fecha_final,
                'recibidos' => 0,
                'emitidos' => 0,
                'estado' => $e->getMessage(),
                'team_id' => $record->id
            ]);

            return [
                'exito' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

}
