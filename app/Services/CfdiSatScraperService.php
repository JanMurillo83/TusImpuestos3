<?php

namespace App\Services;

use App\Models\Team;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpCfdi\CfdiSatScraper\Filters\DownloadType;
use PhpCfdi\CfdiSatScraper\Filters\Options\StatesVoucherOption;
use PhpCfdi\CfdiSatScraper\QueryByFilters;
use PhpCfdi\CfdiSatScraper\ResourceType;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;
use PhpCfdi\CfdiSatScraper\SatScraper;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionManager;
use PhpCfdi\Credentials\Credential;

/**
 * Servicio centralizado para la descarga de CFDIs desde el SAT
 *
 * Este servicio proporciona una interfaz unificada para:
 * - Consultar CFDIs por período
 * - Descargar CFDIs por UUID
 * - Gestión de sesiones y cookies
 * - Manejo robusto de errores
 * - Logging estructurado
 */
class CfdiSatScraperService
{
    private Team $team;
    private ?SatScraper $scraper = null;
    private string $cookieJarFile;
    private array $config;

    /**
     * Constructor del servicio
     */
    public function __construct(Team $team)
    {
        $this->team = $team;
        $this->initializeConfig();
    }

    /**
     * Inicializa la configuración del servicio
     */
    private function initializeConfig(): void
    {
        $timestamp = Carbon::now()->format('dmYHis');
        $rfc = $this->team->taxid;

        $this->config = [
            'rfc' => $rfc,
            'fielcer' => storage_path('/app/public/' . $this->team->archivocer),
            'fielkey' => storage_path('/app/public/' . $this->team->archivokey),
            'fielpass' => $this->team->fielpass,
            'cookieJarPath' => storage_path('/app/public/cookies/'),
            'cookieJarFile' => storage_path('/app/public/cookies/' . $rfc . '.json'),
            'downloadsPath' => [
                'xml_emitidos' => storage_path("/app/public/cfdis/{$rfc}/{$timestamp}/XML/EMITIDOS/"),
                'xml_recibidos' => storage_path("/app/public/cfdis/{$rfc}/{$timestamp}/XML/RECIBIDOS/"),
                'pdf' => storage_path("/app/public/cfdis/{$rfc}/{$timestamp}/PDF/"),
            ],
            'tempPath' => storage_path("/app/public/TEMP_{$rfc}/"),
        ];

        $this->cookieJarFile = $this->config['cookieJarFile'];
    }

    /**
     * Valida que los archivos FIEL existan y la contraseña esté configurada
     */
    public function validateFielFiles(): array
    {
        $fielcer = $this->config['fielcer'];
        $fielkey = $this->config['fielkey'];
        $fielpass = $this->config['fielpass'];

        if (!file_exists($fielcer)) {
            return ['valid' => false, 'error' => 'Archivo CER de FIEL no encontrado'];
        }

        if (!file_exists($fielkey)) {
            return ['valid' => false, 'error' => 'Archivo KEY de FIEL no encontrado'];
        }

        if (empty($fielpass)) {
            return ['valid' => false, 'error' => 'Contraseña de FIEL no configurada'];
        }

        return ['valid' => true];
    }

    /**
     * Valida que la FIEL sea válida y no esté expirada
     */
    public function validateFielCredentials(): array
    {
        try {
            $credential = Credential::openFiles(
                $this->config['fielcer'],
                $this->config['fielkey'],
                $this->config['fielpass']
            );

            if (!$credential->isFiel()) {
                return ['valid' => false, 'error' => 'El certificado no es una FIEL válida'];
            }

            if (!$credential->certificate()->validOn()) {
                $vigencia = Carbon::parse($credential->certificate()->validToDateTime());
                return [
                    'valid' => false,
                    'error' => 'La FIEL ha expirado',
                    'vigencia' => $vigencia->format('Y-m-d')
                ];
            }

            return [
                'valid' => true,
                'vigencia' => Carbon::parse($credential->certificate()->validToDateTime())->format('Y-m-d')
            ];

        } catch (\Exception $e) {
            Log::error('Error validando FIEL', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'error' => $e->getMessage()
            ]);

            return ['valid' => false, 'error' => 'Error al validar FIEL: ' . $e->getMessage()];
        }
    }

    /**
     * Inicializa el scraper del SAT
     */
    public function initializeScraper(): array
    {
        try {
            // Validar archivos FIEL
            $validation = $this->validateFielFiles();
            if (!$validation['valid']) {
                return $validation;
            }

            // Validar credenciales FIEL
            $credentialValidation = $this->validateFielCredentials();
            if (!$credentialValidation['valid']) {
                return $credentialValidation;
            }

            // Crear directorios necesarios
            $this->createDirectories();

            // Limpiar cookie existente
            $this->cleanCookie();

            // Configurar cliente HTTP con SSL personalizado
            $client = new Client([
                'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
                'timeout' => 120,
                'connect_timeout' => 30,
            ]);

            $gateway = new SatHttpGateway($client, new FileCookieJar($this->cookieJarFile, true));

            $credential = Credential::openFiles(
                $this->config['fielcer'],
                $this->config['fielkey'],
                $this->config['fielpass']
            );

            $fielSessionManager = FielSessionManager::create($credential);
            $this->scraper = new SatScraper($fielSessionManager, $gateway);

            Log::info('SatScraper inicializado correctamente', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc']
            ]);

            return ['valid' => true, 'scraper' => $this->scraper];

        } catch (\Exception $e) {
            Log::error('Error inicializando SatScraper', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['valid' => false, 'error' => 'Error al inicializar conexión con SAT: ' . $e->getMessage()];
        }
    }

    /**
     * Consulta CFDIs por período de fechas
     */
    public function listByPeriod(string $fechaInicial, string $fechaFinal, string $tipo = 'emitidos', bool $soloVigentes = true): array
    {
        try {
            if (!$this->scraper) {
                $init = $this->initializeScraper();
                if (!$init['valid']) {
                    return ['success' => false, 'error' => $init['error'], 'list' => []];
                }
            }

            $query = new QueryByFilters(
                new \DateTimeImmutable($fechaInicial),
                new \DateTimeImmutable($fechaFinal)
            );

            $downloadType = $tipo === 'emitidos' ? DownloadType::emitidos() : DownloadType::recibidos();
            $query->setDownloadType($downloadType);

            if ($soloVigentes) {
                $query->setStateVoucher(StatesVoucherOption::vigentes());
            }

            Log::info('Consultando CFDIs por período', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
                'tipo' => $tipo,
                'solo_vigentes' => $soloVigentes
            ]);

            $list = $this->scraper->listByPeriod($query);

            Log::info('Consulta de CFDIs completada', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'tipo' => $tipo,
                'count' => $list->count()
            ]);

            return [
                'success' => true,
                'list' => $list,
                'count' => $list->count()
            ];

        } catch (\Exception $e) {
            Log::error('Error consultando CFDIs por período', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage(), 'list' => []];
        }
    }

    /**
     * Consulta CFDIs por lista de UUIDs
     */
    public function listByUuids(array $uuids, string $tipo = 'emitidos'): array
    {
        try {
            if (!$this->scraper) {
                $init = $this->initializeScraper();
                if (!$init['valid']) {
                    return ['success' => false, 'error' => $init['error'], 'list' => []];
                }
            }

            $downloadType = $tipo === 'emitidos' ? DownloadType::emitidos() : DownloadType::recibidos();

            Log::info('Consultando CFDIs por UUIDs', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'tipo' => $tipo,
                'uuids_count' => count($uuids)
            ]);

            $list = $this->scraper->listByUuids($uuids, $downloadType);

            return [
                'success' => true,
                'list' => $list,
                'count' => $list->count()
            ];

        } catch (\Exception $e) {
            Log::error('Error consultando CFDIs por UUIDs', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage(), 'list' => []];
        }
    }

    /**
     * Descarga recursos (XML o PDF) para una lista de CFDIs
     */
    public function downloadResources($list, string $resourceType = 'xml', string $tipo = 'emitidos', int $concurrency = 50): array
    {
        try {
            if (!$this->scraper) {
                $init = $this->initializeScraper();
                if (!$init['valid']) {
                    return ['success' => false, 'error' => $init['error']];
                }
            }

            $resource = $resourceType === 'xml' ? ResourceType::xml() : ResourceType::pdf();

            $destinationPath = $resourceType === 'xml'
                ? ($tipo === 'emitidos' ? $this->config['downloadsPath']['xml_emitidos'] : $this->config['downloadsPath']['xml_recibidos'])
                : $this->config['downloadsPath']['pdf'];

            Log::info('Descargando recursos', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'resource_type' => $resourceType,
                'tipo' => $tipo,
                'destination' => $destinationPath,
                'count' => is_countable($list) ? count($list) : 0
            ]);

            $downloader = $this->scraper->resourceDownloader($resource, $list, $concurrency);
            $downloader->saveTo($destinationPath, true, 0777);

            Log::info('Descarga de recursos completada', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'resource_type' => $resourceType,
                'tipo' => $tipo
            ]);

            return [
                'success' => true,
                'destination_path' => $destinationPath
            ];

        } catch (\Exception $e) {
            Log::error('Error descargando recursos', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'resource_type' => $resourceType,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Descarga XML y PDF por UUID específico
     */
    public function downloadByUuid(string $uuid, string $tipo = 'emitidos'): array
    {
        try {
            $listResult = $this->listByUuids([$uuid], $tipo);

            if (!$listResult['success']) {
                return $listResult;
            }

            $list = $listResult['list'];
            $tempPath = $this->config['tempPath'];

            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0777, true);
            }

            // Descargar XML
            $xmlDownload = $this->scraper->resourceDownloader(ResourceType::xml(), $list, 10);
            $xmlDownload->saveTo($tempPath, true, 0777);

            // Descargar PDF
            $pdfDownload = $this->scraper->resourceDownloader(ResourceType::pdf(), $list, 10);
            $pdfDownload->saveTo($tempPath, true, 0777);

            return [
                'success' => true,
                'xml_file' => $tempPath . $uuid . '.xml',
                'pdf_file' => $tempPath . $uuid . '.pdf',
            ];

        } catch (\Exception $e) {
            Log::error('Error descargando por UUID', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'uuid' => $uuid,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crea los directorios necesarios para las descargas
     */
    private function createDirectories(): void
    {
        $directories = [
            $this->config['cookieJarPath'],
            $this->config['downloadsPath']['xml_emitidos'],
            $this->config['downloadsPath']['xml_recibidos'],
            $this->config['downloadsPath']['pdf'],
            $this->config['tempPath'],
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    /**
     * Limpia el archivo de cookies
     */
    private function cleanCookie(): void
    {
        if (File::exists($this->cookieJarFile)) {
            File::delete($this->cookieJarFile);
        }

        if (!file_exists($this->cookieJarFile)) {
            touch($this->cookieJarFile);
        }
    }

    /**
     * Limpia archivos temporales
     */
    public function cleanupTempFiles(): void
    {
        try {
            $tempPath = $this->config['tempPath'];

            if (is_dir($tempPath)) {
                $files = File::files($tempPath);
                foreach ($files as $file) {
                    File::delete($file);
                }
            }

            Log::info('Archivos temporales limpiados', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc']
            ]);

        } catch (\Exception $e) {
            Log::warning('Error limpiando archivos temporales', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene la configuración actual
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Obtiene el equipo actual
     */
    public function getTeam(): Team
    {
        return $this->team;
    }
}
