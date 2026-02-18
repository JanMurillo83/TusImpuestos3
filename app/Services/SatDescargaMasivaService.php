<?php

namespace App\Services;

use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\Credentials\Credential;
use ZipArchive;

/**
 * Servicio para descarga masiva de CFDIs usando el Web Service oficial del SAT
 *
 * Este servicio proporciona:
 * - Solicitud de descarga masiva (query)
 * - Verificación de estado de solicitud
 * - Descarga de paquetes ZIP
 * - Extracción automática de XMLs
 * - Manejo robusto de errores
 * - Logging estructurado
 */
class SatDescargaMasivaService
{
    private Team $team;
    private ?Service $service = null;
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
            'downloadsPath' => [
                'xml_emitidos' => storage_path("/app/public/cfdis/{$rfc}/{$timestamp}/XML/EMITIDOS/"),
                'xml_recibidos' => storage_path("/app/public/cfdis/{$rfc}/{$timestamp}/XML/RECIBIDOS/"),
                'zip' => storage_path("/app/public/zipdescargas/"),
            ],
        ];
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
            Log::error('Error validando FIEL para descarga masiva', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'error' => $e->getMessage()
            ]);

            return ['valid' => false, 'error' => 'Error al validar FIEL: ' . $e->getMessage()];
        }
    }

    /**
     * Inicializa el servicio de descarga masiva
     */
    public function initializeService(): array
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

            // Crear FIEL
            $fiel = Fiel::create(
                file_get_contents($this->config['fielcer']),
                file_get_contents($this->config['fielkey']),
                $this->config['fielpass']
            );

            // Crear servicio
            $webClient = new GuzzleWebClient();
            $requestBuilder = new FielRequestBuilder($fiel);
            $this->service = new Service($requestBuilder, $webClient);

            Log::info('Servicio de descarga masiva inicializado correctamente', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc']
            ]);

            return ['valid' => true, 'service' => $this->service];

        } catch (\Exception $e) {
            Log::error('Error inicializando servicio de descarga masiva', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['valid' => false, 'error' => 'Error al inicializar servicio de descarga masiva: ' . $e->getMessage()];
        }
    }

    /**
     * Solicita una descarga masiva de CFDIs
     */
    public function solicitarDescarga(string $fechaInicial, string $fechaFinal, string $tipo = 'emitidos'): array
    {
        try {
            if (!$this->service) {
                $init = $this->initializeService();
                if (!$init['valid']) {
                    return ['success' => false, 'error' => $init['error']];
                }
            }

            // Agregar hora para evitar conflictos (el SAT usa segundos para diferenciar versiones)
            $fechaInicioCompleta = $fechaInicial . ' 00:00:00';
            $fechaFinalCompleta = $fechaFinal . ' 23:59:59';

            // Crear parámetros de consulta
            $downloadType = $tipo === 'emitidos' ? DownloadType::issued() : DownloadType::received();

            $query = QueryParameters::create()
                ->withPeriod(DateTimePeriod::createFromValues($fechaInicioCompleta, $fechaFinalCompleta))
                ->withRequestType(RequestType::xml())
                ->withDocumentStatus(DocumentStatus::active())
                ->withDownloadType($downloadType);

            Log::info('Solicitando descarga masiva al SAT', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
                'tipo' => $tipo
            ]);

            // Enviar solicitud
            $queryResult = $this->service->query($query);

            // Verificar respuesta
            if (!$queryResult->getStatus()->isAccepted()) {
                $errorMsg = $queryResult->getStatus()->getMessage();
                Log::error('Solicitud de descarga masiva rechazada', [
                    'team_id' => $this->team->id,
                    'rfc' => $this->config['rfc'],
                    'error' => $errorMsg
                ]);

                return ['success' => false, 'error' => 'Solicitud rechazada por el SAT: ' . $errorMsg];
            }

            $requestId = $queryResult->getRequestId();

            Log::info('Solicitud de descarga masiva aceptada', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'request_id' => $requestId,
                'tipo' => $tipo
            ]);

            return [
                'success' => true,
                'request_id' => $requestId,
                'tipo' => $tipo
            ];

        } catch (\Exception $e) {
            Log::error('Error solicitando descarga masiva', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => 'Error al solicitar descarga masiva: ' . $e->getMessage()];
        }
    }

    /**
     * Verifica el estado de una solicitud y descarga los paquetes si están listos
     */
    public function verificarYDescargar(string $requestId, string $tipo = 'emitidos', int $maxIntentos = 3, int $esperaSegundos = 10): array
    {
        try {
            if (!$this->service) {
                $init = $this->initializeService();
                if (!$init['valid']) {
                    return ['success' => false, 'error' => $init['error']];
                }
            }

            $intento = 0;
            $paquetesDescargados = [];

            while ($intento < $maxIntentos) {
                Log::info('Verificando estado de solicitud', [
                    'team_id' => $this->team->id,
                    'rfc' => $this->config['rfc'],
                    'request_id' => $requestId,
                    'intento' => $intento + 1,
                    'max_intentos' => $maxIntentos
                ]);

                // Verificar estado
                $verify = $this->service->verify($requestId);

                // Validar respuesta del servicio
                if (!$verify->getStatus()->isAccepted()) {
                    $errorMsg = $verify->getStatus()->getMessage();
                    Log::error('Verificación rechazada por el SAT', [
                        'team_id' => $this->team->id,
                        'rfc' => $this->config['rfc'],
                        'request_id' => $requestId,
                        'error' => $errorMsg
                    ]);

                    return ['success' => false, 'error' => 'Verificación rechazada: ' . $errorMsg, 'status' => 'rejected'];
                }

                // Validar código de solicitud
                if (!$verify->getCodeRequest()->isAccepted()) {
                    $errorMsg = $verify->getCodeRequest()->getMessage();
                    Log::error('Código de solicitud no aceptado', [
                        'team_id' => $this->team->id,
                        'rfc' => $this->config['rfc'],
                        'request_id' => $requestId,
                        'error' => $errorMsg
                    ]);

                    return ['success' => false, 'error' => 'Código de solicitud inválido: ' . $errorMsg, 'status' => 'invalid'];
                }

                $statusRequest = $verify->getStatusRequest();

                // Verificar estados de error
                if ($statusRequest->isExpired() || $statusRequest->isFailure() || $statusRequest->isRejected()) {
                    $errorMsg = 'Solicitud ' . ($statusRequest->isExpired() ? 'expirada' : ($statusRequest->isFailure() ? 'fallida' : 'rechazada'));
                    Log::error('Solicitud en estado de error', [
                        'team_id' => $this->team->id,
                        'rfc' => $this->config['rfc'],
                        'request_id' => $requestId,
                        'status' => $errorMsg
                    ]);

                    return ['success' => false, 'error' => $errorMsg, 'status' => 'error'];
                }

                // Si aún está en proceso, esperar
                if ($statusRequest->isInProgress() || $statusRequest->isAccepted()) {
                    $intento++;

                    if ($intento < $maxIntentos) {
                        Log::info('Solicitud aún en proceso, esperando...', [
                            'team_id' => $this->team->id,
                            'rfc' => $this->config['rfc'],
                            'request_id' => $requestId,
                            'esperando_segundos' => $esperaSegundos
                        ]);

                        sleep($esperaSegundos);
                        continue;
                    } else {
                        Log::warning('Solicitud aún en proceso, se alcanzó el máximo de intentos', [
                            'team_id' => $this->team->id,
                            'rfc' => $this->config['rfc'],
                            'request_id' => $requestId
                        ]);

                        return ['success' => false, 'error' => 'Solicitud aún en proceso, intente más tarde', 'status' => 'in_progress'];
                    }
                }

                // Si llegamos aquí, la solicitud está completada y lista para descargar
                $packageIds = $verify->getPackagesIds();

                Log::info('Solicitud completada, descargando paquetes', [
                    'team_id' => $this->team->id,
                    'rfc' => $this->config['rfc'],
                    'request_id' => $requestId,
                    'paquetes_count' => count($packageIds)
                ]);

                // Descargar cada paquete
                foreach ($packageIds as $packageId) {
                    try {
                        $download = $this->service->download($packageId);

                        if (!$download->getStatus()->isAccepted()) {
                            Log::warning('Paquete no disponible para descarga', [
                                'team_id' => $this->team->id,
                                'rfc' => $this->config['rfc'],
                                'package_id' => $packageId,
                                'status' => $download->getStatus()->getMessage()
                            ]);
                            continue;
                        }

                        // Guardar paquete ZIP
                        $zipPath = $this->config['downloadsPath']['zip'];
                        $zipFile = $zipPath . $packageId . '.zip';

                        file_put_contents($zipFile, $download->getPackageContent());

                        Log::info('Paquete descargado', [
                            'team_id' => $this->team->id,
                            'rfc' => $this->config['rfc'],
                            'package_id' => $packageId,
                            'zip_file' => $zipFile
                        ]);

                        // Extraer XMLs del ZIP
                        $extractResult = $this->extractZipPackage($zipFile, $tipo);

                        if ($extractResult['success']) {
                            $paquetesDescargados[] = [
                                'package_id' => $packageId,
                                'zip_file' => $zipFile,
                                'xml_count' => $extractResult['xml_count'],
                                'destination' => $extractResult['destination']
                            ];
                        }

                    } catch (\Exception $e) {
                        Log::error('Error descargando paquete individual', [
                            'team_id' => $this->team->id,
                            'rfc' => $this->config['rfc'],
                            'package_id' => $packageId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                return [
                    'success' => true,
                    'paquetes' => $paquetesDescargados,
                    'paquetes_count' => count($paquetesDescargados),
                    'status' => 'completed'
                ];
            }

            // Si salimos del loop sin retornar, es porque se excedieron los intentos
            return ['success' => false, 'error' => 'Se excedió el tiempo de espera', 'status' => 'timeout'];

        } catch (\Exception $e) {
            Log::error('Error verificando y descargando solicitud', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => 'Error verificando solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Extrae XMLs de un paquete ZIP descargado
     */
    private function extractZipPackage(string $zipFile, string $tipo): array
    {
        try {
            $destination = $tipo === 'emitidos'
                ? $this->config['downloadsPath']['xml_emitidos']
                : $this->config['downloadsPath']['xml_recibidos'];

            $zip = new ZipArchive();

            if ($zip->open($zipFile) !== true) {
                return ['success' => false, 'error' => 'No se pudo abrir el archivo ZIP'];
            }

            // Extraer todos los archivos
            $zip->extractTo($destination);
            $xmlCount = $zip->numFiles;
            $zip->close();

            Log::info('Paquete ZIP extraído', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'zip_file' => $zipFile,
                'destination' => $destination,
                'xml_count' => $xmlCount
            ]);

            return [
                'success' => true,
                'destination' => $destination,
                'xml_count' => $xmlCount
            ];

        } catch (\Exception $e) {
            Log::error('Error extrayendo paquete ZIP', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'zip_file' => $zipFile,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => 'Error extrayendo ZIP: ' . $e->getMessage()];
        }
    }

    /**
     * Proceso completo: solicitar, esperar y descargar
     */
    public function descargarCompleto(string $fechaInicial, string $fechaFinal, string $tipo = 'emitidos'): array
    {
        try {
            // Paso 1: Solicitar descarga
            $solicitud = $this->solicitarDescarga($fechaInicial, $fechaFinal, $tipo);

            if (!$solicitud['success']) {
                return $solicitud;
            }

            $requestId = $solicitud['request_id'];

            // Paso 2: Esperar y descargar (3 intentos, 10 segundos entre cada uno)
            $descarga = $this->verificarYDescargar($requestId, $tipo, 3, 10);

            return $descarga;

        } catch (\Exception $e) {
            Log::error('Error en proceso completo de descarga masiva', [
                'team_id' => $this->team->id,
                'rfc' => $this->config['rfc'],
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => 'Error en descarga masiva: ' . $e->getMessage()];
        }
    }

    /**
     * Crea los directorios necesarios para las descargas
     */
    private function createDirectories(): void
    {
        $directories = [
            $this->config['downloadsPath']['xml_emitidos'],
            $this->config['downloadsPath']['xml_recibidos'],
            $this->config['downloadsPath']['zip'],
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
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

    /**
     * Calcula el número estimado de días entre dos fechas
     */
    public static function calcularDias(string $fechaInicial, string $fechaFinal): int
    {
        $inicio = Carbon::parse($fechaInicial);
        $fin = Carbon::parse($fechaFinal);

        return $inicio->diffInDays($fin);
    }
}
