<?php

namespace App\Http\Controllers;

use App\Models\Almacencfdis;
use App\Models\Team;
use App\Models\TempCfdis;
use App\Models\Xmlfiles;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use Filament\Facades\Filament;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpCfdi\CfdiSatScraper\Contracts\ResourceDownloadHandlerInterface;
use PhpCfdi\CfdiSatScraper\Exceptions\ResourceDownloadError;
use PhpCfdi\CfdiSatScraper\Exceptions\ResourceDownloadRequestExceptionError;
use PhpCfdi\CfdiSatScraper\Exceptions\ResourceDownloadResponseError;
use PhpCfdi\CfdiSatScraper\Filters\DownloadType;
use PhpCfdi\CfdiSatScraper\QueryByFilters;
use PhpCfdi\CfdiSatScraper\ResourceType;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;
use PhpCfdi\CfdiSatScraper\SatScraper;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionManager;
use PhpCfdi\Credentials\Credential;
use Psr\Http\Message\ResponseInterface;
use App\Services\CfdiSatScraperService;
use App\Services\SatDescargaMasivaService;
use App\Services\XmlProcessorService;

class NewCFDI extends Controller
{
    public function borrar($team_id){
        TempCfdis::where('team_id', $team_id)->delete();
    }

    public function graba(array $data) :int
    {
        foreach (array_chunk($data, 100) as $chunk) {
            TempCfdis::insert($chunk);
        }
        return count($data);
    }

    public function Scraper($team_id,$fecha_i,$fecha_f) : array
    {
        $record = Team::where('id',$team_id)->first();
        $fecha_inicial = Carbon::create($fecha_i)->format('Y-m-d');
        $fecha_final = Carbon::create($fecha_f)->format('Y-m-d');

        // Calcular días y decidir estrategia
        $dias = SatDescargaMasivaService::calcularDias($fecha_inicial, $fecha_final);
        $usarDescargaMasiva = $dias > 30;

        // ESTRATEGIA HÍBRIDA CON FALLBACK
        if ($usarDescargaMasiva) {
            // INTENTO 1: Descarga Masiva para consulta rápida
            try {
                return $this->consultarConDescargaMasiva($record, $fecha_inicial, $fecha_final);
            } catch (\Exception $e) {
                // FALLBACK: Si falla descarga masiva, usar scraper
                return $this->consultarConScraper($record, $fecha_inicial, $fecha_final);
            }
        } else {
            // INTENTO 1: Scraper (método tradicional)
            try {
                return $this->consultarConScraper($record, $fecha_inicial, $fecha_final);
            } catch (\Exception $e) {
                // FALLBACK: Si falla scraper, usar descarga masiva
                return $this->consultarConDescargaMasiva($record, $fecha_inicial, $fecha_final);
            }
        }
    }

    /**
     * Consulta CFDIs usando el scraper tradicional
     */
    private function consultarConScraper($record, $fecha_inicial, $fecha_final): array
    {
        $scraperService = new CfdiSatScraperService($record);

        // Validar archivos FIEL
        $validation = $scraperService->validateFielFiles();
        if (!$validation['valid']) {
            throw new \Exception($validation['error']);
        }

        // Inicializar scraper
        $init = $scraperService->initializeScraper();
        if (!$init['valid']) {
            throw new \Exception($init['error']);
        }

        // Consultar emitidos (sin filtro de vigentes para mostrar todos)
        $emitidosResult = $scraperService->listByPeriod($fecha_inicial, $fecha_final, 'emitidos', false);
        if (!$emitidosResult['success']) {
            throw new \Exception($emitidosResult['error']);
        }

        // Consultar recibidos (sin filtro de vigentes para mostrar todos)
        $recibidosResult = $scraperService->listByPeriod($fecha_inicial, $fecha_final, 'recibidos', false);
        if (!$recibidosResult['success']) {
            throw new \Exception($recibidosResult['error']);
        }

        return [
            'recibidos' => $recibidosResult['count'],
            'emitidos' => $emitidosResult['count'],
            'data_emitidos' => $emitidosResult['list'],
            'data_recibidos' => $recibidosResult['list'],
            'metodo' => 'Scraper'
        ];
    }

    /**
     * Consulta CFDIs usando descarga masiva (solo obtiene metadata de los XMLs)
     */
    private function consultarConDescargaMasiva($record, $fecha_inicial, $fecha_final): array
    {
        $masivaService = new SatDescargaMasivaService($record);

        // Solicitar y descargar emitidos
        $emitidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'emitidos');
        if (!$emitidosResult['success']) {
            throw new \Exception('Error en descarga masiva de emitidos: ' . ($emitidosResult['error'] ?? 'Error desconocido'));
        }

        // Solicitar y descargar recibidos
        $recibidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'recibidos');
        if (!$recibidosResult['success']) {
            throw new \Exception('Error en descarga masiva de recibidos: ' . ($recibidosResult['error'] ?? 'Error desconocido'));
        }

        // Procesar los XMLs descargados para extraer metadata
        $config = $masivaService->getConfig();
        $emitidosMetadata = $this->extractMetadataFromXmls($config['downloadsPath']['xml_emitidos'], 'emitidos');
        $recibidosMetadata = $this->extractMetadataFromXmls($config['downloadsPath']['xml_recibidos'], 'recibidos');

        return [
            'recibidos' => count($recibidosMetadata),
            'emitidos' => count($emitidosMetadata),
            'data_emitidos' => $emitidosMetadata,
            'data_recibidos' => $recibidosMetadata,
            'metodo' => 'Descarga Masiva'
        ];
    }

    /**
     * Extrae metadata de archivos XML (similar a lo que regresa el scraper)
     */
    private function extractMetadataFromXmls(string $directorio, string $tipo): array
    {
        $metadata = [];

        if (!is_dir($directorio)) {
            return $metadata;
        }

        $files = array_diff(scandir($directorio), array('.', '..'));

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'xml') {
                continue;
            }

            try {
                $filePath = $directorio . $file;
                $xmlContents = file_get_contents($filePath);
                $cfdi = Cfdi::newFromString($xmlContents);
                $comprobante = $cfdi->getNode();
                $emisor = $comprobante->searchNode('cfdi:Emisor');
                $receptor = $comprobante->searchNode('cfdi:Receptor');
                $tfd = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');

                // Crear objeto compatible con el formato de scraper
                $metadata[] = (object) [
                    'uuid' => $tfd['UUID'] ?? '',
                    'rfcEmisor' => $emisor['Rfc'] ?? '',
                    'nombreEmisor' => $emisor['Nombre'] ?? '',
                    'rfcReceptor' => $receptor['Rfc'] ?? '',
                    'nombreReceptor' => $receptor['Nombre'] ?? '',
                    'pacCertifico' => $tfd['RfcProvCertif'] ?? '',
                    'fechaEmision' => $comprobante['Fecha'] ?? '',
                    'fechaCertificacion' => $tfd['FechaTimbrado'] ?? '',
                    'total' => '$' . number_format(floatval($comprobante['Total'] ?? 0), 2),
                    'efectoComprobante' => $comprobante['TipoDeComprobante'] ?? '',
                    'estadoComprobante' => 'Vigente', // Asumimos vigente ya que viene de descarga activa
                    'fechaDeCancelacion' => null,
                ];

            } catch (\Exception $e) {
                // Si hay error procesando un XML, continuar con el siguiente
                continue;
            }
        }

        return $metadata;
    }

    public function Descarga($team_id,$uuids) : array
    {
        $record = Team::where('id',$team_id)->first();

        try {
            $scraperService = new CfdiSatScraperService($record);
            $xmlProcessor = new XmlProcessorService();

            // Validar archivos FIEL
            $validation = $scraperService->validateFielFiles();
            if (!$validation['valid']) {
                throw new \Exception($validation['error']);
            }

            // Inicializar scraper
            $init = $scraperService->initializeScraper();
            if (!$init['valid']) {
                throw new \Exception($init['error']);
            }

            // Consultar y descargar por UUIDs - emitidos
            $emitidosResult = $scraperService->listByUuids($uuids, 'emitidos');
            if ($emitidosResult['success']) {
                $scraperService->downloadResources($emitidosResult['list'], 'xml', 'emitidos', 50);
            }

            // Consultar y descargar por UUIDs - recibidos
            $recibidosResult = $scraperService->listByUuids($uuids, 'recibidos');
            if ($recibidosResult['success']) {
                $scraperService->downloadResources($recibidosResult['list'], 'xml', 'recibidos', 50);
            }

            // Procesar archivos descargados
            $config = $scraperService->getConfig();
            $resultEmitidos = $xmlProcessor->processDirectory($config['downloadsPath']['xml_emitidos'], $team_id, 'Emitidos');
            $resultRecibidos = $xmlProcessor->processDirectory($config['downloadsPath']['xml_recibidos'], $team_id, 'Recibidos');

            return [
                'data_emitidos' => $resultEmitidos['success'],
                'data_recibidos' => $resultRecibidos['success']
            ];

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}
