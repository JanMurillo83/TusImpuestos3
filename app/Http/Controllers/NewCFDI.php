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

        try {
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
                'data_recibidos' => $recibidosResult['list']
            ];

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
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
