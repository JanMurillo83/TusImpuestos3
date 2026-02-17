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
        $hoy = Carbon::now()->format('dmY');
        $rfc = $record->taxid;
        $fielcer = storage_path() . '/app/public/' . $record->archivocer;
        $fielkey = storage_path() . '/app/public/' . $record->archivokey;
        $fielpass = $record->fielpass;

        // Validar archivos FIEL
        if (!file_exists($fielcer) || !file_exists($fielkey) || $fielpass == '') {
            ValidaDescargas::create([
                'fecha' => Carbon::now(),
                'inicio' => $fecha_inicial,
                'fin' => $fecha_final,
                'recibidos' => 0,
                'emitidos' => 0,
                'estado' => 'Error: Archivos FIEL no encontrados o contraseña vacía',
                'team_id' => $record->id
            ]);

            return [
                'exito' => false,
                'mensaje' => 'Archivos FIEL no encontrados o contraseña vacía'
            ];
        }

        try {
            // Configurar rutas
            $cookieJarPath = storage_path() . '/app/public/cookies/';
            $cookieJarFile = storage_path() . '/app/public/cookies/' . $rfc . '.json';
            $downloadsPath_REC = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/XML/RECIBIDOS/';
            $downloadsPath_EMI = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/XML/EMITIDOS/';
            $downloadsPath2 = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/PDF/';

            // Crear directorios
            if (!is_dir($cookieJarPath)) mkdir($cookieJarPath, 0777, true);
            if (!is_dir($downloadsPath_REC)) mkdir($downloadsPath_REC, 0777, true);
            if (!is_dir($downloadsPath_EMI)) mkdir($downloadsPath_EMI, 0777, true);

            // Eliminar cookie específico del RFC
            if (File::exists($cookieJarFile)) unlink($cookieJarFile);
            if (!file_exists($cookieJarFile)) fopen($cookieJarFile, 'w');

            // Configurar cliente
            $client = new Client([
                'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
            ]);
            $gateway = new SatHttpGateway($client, new FileCookieJar($cookieJarFile, true));
            $credential = Credential::openFiles($fielcer, $fielkey, $fielpass);

            // Validar FIEL
            if (!$credential->isFiel() || !$credential->certificate()->validOn()) {
                ValidaDescargas::create([
                    'fecha' => Carbon::now(),
                    'inicio' => $fecha_inicial,
                    'fin' => $fecha_final,
                    'recibidos' => 0,
                    'emitidos' => 0,
                    'estado' => 'Error: FIEL no válida o expirada',
                    'team_id' => $record->id
                ]);

                return [
                    'exito' => false,
                    'mensaje' => 'FIEL no válida o expirada'
                ];
            }

            $fielSessionManager = FielSessionManager::create($credential);
            $satScraper = new SatScraper($fielSessionManager, $gateway);

            // Descargar Emitidos
            $query = new QueryByFilters(
                new \DateTimeImmutable($fecha_inicial),
                new \DateTimeImmutable($fecha_final)
            );
            $query->setDownloadType(DownloadType::emitidos())
                  ->setStateVoucher(StatesVoucherOption::vigentes());
            $list = $satScraper->listByPeriod($query);
            $satScraper->resourceDownloader(ResourceType::xml(), $list, 50)
                       ->saveTo($downloadsPath_EMI, true, 0777);
            $satScraper->resourceDownloader(ResourceType::pdf(), $list, 50)
                       ->saveTo($downloadsPath2, true, 0777);
            $this->procesarEmitidos($downloadsPath_EMI, $record->id);

            // Descargar Recibidos
            $query2 = new QueryByFilters(
                new \DateTimeImmutable($fecha_inicial),
                new \DateTimeImmutable($fecha_final)
            );
            $query2->setDownloadType(DownloadType::recibidos())
                   ->setStateVoucher(StatesVoucherOption::vigentes());
            $list2 = $satScraper->listByPeriod($query2);
            $satScraper->resourceDownloader(ResourceType::xml(), $list2, 50)
                       ->saveTo($downloadsPath_REC, true, 0777);
            $satScraper->resourceDownloader(ResourceType::pdf(), $list2, 50)
                       ->saveTo($downloadsPath2, true, 0777);
            $this->procesarRecibidos($downloadsPath_REC, $record->id);
            $this->procesarPDF($downloadsPath2, $record->id);

            // Registrar éxito
            ValidaDescargas::create([
                'fecha' => Carbon::now(),
                'inicio' => $fecha_inicial,
                'fin' => $fecha_final,
                'recibidos' => $list2->count(),
                'emitidos' => $list->count(),
                'estado' => 'Completado',
                'team_id' => $record->id
            ]);

            return [
                'exito' => true,
                'emitidos' => $list->count(),
                'recibidos' => $list2->count()
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

    /**
     * Procesa archivos XML recibidos
     */
    private function procesarRecibidos($archivo, $team): void
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        foreach($files as $desfile) {
            $file = $archivo . $desfile;
            try{
                $xmlContents = \file_get_contents($file);
                $cfdi = Cfdi::newFromString($xmlContents);
                $comprobante = $cfdi->getNode();
                $emisor = $comprobante->searchNode('cfdi:Emisor');
                $receptor = $comprobante->searchNode('cfdi:Receptor');
                $tfd = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
                $pagoscom = $comprobante->searchNode('cfdi:Complemento', 'pago20:Pagos');
                $impuestos = $comprobante->searchNode('cfdi:Impuestos');
                $tipocom = $comprobante['TipoDeComprobante'];
                $subtotal = 0;
                $descuento = 0;
                $traslado = 0;
                $retencion = 0;
                $total = 0;
                $tipocambio = 0;
                if ($tipocom != 'P') {
                    $subtotal = floatval($comprobante['SubTotal']);
                    $descuento = floatval($comprobante['Descuento']);
                    if (isset($impuestos['TotalImpuestosTrasladados'])) $traslado = floatval($impuestos['TotalImpuestosTrasladados']);
                    if (isset($impuestos['TotalImpuestosRetenidos'])) $retencion = floatval($impuestos['TotalImpuestosRetenidos']);
                    $total = floatval($comprobante['Total']);
                    $tipocambio = floatval($comprobante['TipoCambio']);
                }
                else
                {
                    if (!isset($pagoscom))
                    {
                        $pagostot = floatval(0.00);
                        $subtotal = floatval(0.00);
                        $traslado = floatval(0.00);
                        $retencion = floatval(0.00);
                        $total = floatval(0.00);
                        $tipocambio = 1;
                    }
                    $pagostot = $pagoscom?->searchNode('pago20:Totales') ?? 0;
                    $subtotal = floatval($pagostot['TotalTrasladosBaseIVA16']);
                    $traslado = floatval($pagostot['TotalTrasladosImpuestoIVA16']);
                    $retencion = floatval(0.00);
                    $total = floatval($pagostot['MontoTotalPagos']);
                    $tipocambio = 1;
                }
                $xmlContenido = \file_get_contents($file, false);
                $fech = $comprobante['Fecha'];
                list($fechacom, $horacom) = explode('T', $fech);
                list($aniocom, $mescom, $diacom) = explode('-', $fechacom);
                $uuid_val = strtoupper($tfd['UUID']);
                $uuid_v = DB::table('almacencfdis')->where(DB::raw("UPPER(UUID)"),$uuid_val)
                    ->where('team_id',$team)
                    ->where('xml_type','Recibidos')->exists();
                if (!$uuid_v) {
                    Almacencfdis::create([
                        'Serie' => $comprobante['Serie'],
                        'Folio' => $comprobante['Folio'],
                        'Version' => $comprobante['Version'],
                        'Fecha' => $comprobante['Fecha'],
                        'Moneda' => $comprobante['Moneda'],
                        'TipoDeComprobante' => $comprobante['TipoDeComprobante'],
                        'MetodoPago' => $comprobante['MetodoPago'],
                        'FormaPago' => $comprobante['FormaPago'],
                        'Emisor_Rfc' => $emisor['Rfc'],
                        'Emisor_Nombre' => $emisor['Nombre'],
                        'Emisor_RegimenFiscal' => $emisor['RegimenFiscal'],
                        'Receptor_Rfc' => $receptor['Rfc'],
                        'Receptor_Nombre' => $receptor['Nombre'],
                        'Receptor_RegimenFiscal' => $receptor['RegimenFiscal'],
                        'UUID' => $tfd['UUID'],
                        'Total' => $total,
                        'SubTotal' => $subtotal,
                        'Descuento' => $descuento,
                        'TipoCambio' => $tipocambio,
                        'TotalImpuestosTrasladados' => $traslado,
                        'TotalImpuestosRetenidos' => $retencion,
                        'content' => $xmlContenido,
                        'user_tax' => $emisor['Rfc'],
                        'used' => 'NO',
                        'xml_type' => 'Recibidos',
                        'periodo' => intval($mescom),
                        'ejercicio' => intval($aniocom),
                        'team_id' => $team,
                        'archivoxml'=>$file
                    ]);
                    Xmlfiles::create([
                        'taxid' => $emisor['Rfc'],
                        'uuid' => $tfd['UUID'],
                        'content' => $xmlContenido,
                        'periodo' => $mescom,
                        'ejercicio' => $aniocom,
                        'tipo' => 'Recibidos',
                        'solicitud' => 'Importacion',
                        'team_id' => $team,
                    ]);
                }
            }
            catch (\Exception $e){
                error_log($e->getMessage());
            }
        }
    }

    /**
     * Procesa archivos XML emitidos
     */
    private function procesarEmitidos($archivo, $team): void
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        foreach($files as $desfile) {
            $file = $archivo . $desfile;
            try{
                $xmlContents = \file_get_contents($file);
                $cfdi = Cfdi::newFromString($xmlContents);
                $comprobante = $cfdi->getNode();
                $emisor = $comprobante->searchNode('cfdi:Emisor');
                $receptor = $comprobante->searchNode('cfdi:Receptor');
                $tfd = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
                $pagoscom = $comprobante->searchNode('cfdi:Complemento', 'pago20:Pagos');
                $impuestos = $comprobante->searchNode('cfdi:Impuestos');
                $tipocom = $comprobante['TipoDeComprobante'];
                $subtotal = 0;
                $descuento = 0;
                $traslado = 0;
                $retencion = 0;
                $total = 0;
                $tipocambio = 0;
                if ($tipocom != 'P') {
                    $subtotal = floatval($comprobante['SubTotal']);
                    $descuento = floatval($comprobante['Descuento']);
                    if (isset($impuestos['TotalImpuestosTrasladados'])) $traslado = floatval($impuestos['TotalImpuestosTrasladados']);
                    if (isset($impuestos['TotalImpuestosRetenidos'])) $retencion = floatval($impuestos['TotalImpuestosRetenidos']);
                    $total = floatval($comprobante['Total']);
                    $tipocambio = floatval($comprobante['TipoCambio']);
                }
                else
                {
                    if (!isset($pagoscom))
                    {
                        $pagostot = floatval(0.00);
                        $subtotal = floatval(0.00);
                        $traslado = floatval(0.00);
                        $retencion = floatval(0.00);
                        $total = floatval(0.00);
                        $tipocambio = 1;
                    }
                    $pagostot = $pagoscom->searchNode('pago20:Totales');
                    $subtotal = floatval($pagostot['TotalTrasladosBaseIVA16']);
                    $traslado = floatval($pagostot['TotalTrasladosImpuestoIVA16']);
                    $retencion = floatval(0.00);
                    $total = floatval($pagostot['MontoTotalPagos']);
                    $tipocambio = 1;
                }
                $xmlContenido = \file_get_contents($file, false);
                $fech = $comprobante['Fecha'];
                list($fechacom, $horacom) = explode('T', $fech);
                list($aniocom, $mescom, $diacom) = explode('-', $fechacom);
                $uuid_val = strtoupper($tfd['UUID']);
                $uuid_v = DB::table('almacencfdis')->where(DB::raw("UPPER(UUID)"),$uuid_val)
                    ->where('team_id',$team)
                    ->where('xml_type','Emitidos')->exists();
                if (!$uuid_v) {
                    Almacencfdis::create([
                        'Serie' => $comprobante['Serie'],
                        'Folio' => $comprobante['Folio'],
                        'Version' => $comprobante['Version'],
                        'Fecha' => $comprobante['Fecha'],
                        'Moneda' => $comprobante['Moneda'],
                        'TipoDeComprobante' => $comprobante['TipoDeComprobante'],
                        'MetodoPago' => $comprobante['MetodoPago'],
                        'FormaPago' => $comprobante['FormaPago'],
                        'Emisor_Rfc' => $emisor['Rfc'],
                        'Emisor_Nombre' => $emisor['Nombre'],
                        'Emisor_RegimenFiscal' => $emisor['RegimenFiscal'],
                        'Receptor_Rfc' => $receptor['Rfc'],
                        'Receptor_Nombre' => $receptor['Nombre'],
                        'Receptor_RegimenFiscal' => $receptor['RegimenFiscal'],
                        'UUID' => $tfd['UUID'],
                        'Total' => $total,
                        'SubTotal' => $subtotal,
                        'Descuento' => $descuento,
                        'TipoCambio' => $tipocambio,
                        'TotalImpuestosTrasladados' => $traslado,
                        'TotalImpuestosRetenidos' => $retencion,
                        'content' => $xmlContenido,
                        'user_tax' => $emisor['Rfc'],
                        'used' => 'NO',
                        'xml_type' => 'Emitidos',
                        'periodo' => intval($mescom),
                        'ejercicio' => intval($aniocom),
                        'team_id' => $team,
                        'archivoxml'=>$file
                    ]);
                    Xmlfiles::create([
                        'taxid' => $emisor['Rfc'],
                        'uuid' => $tfd['UUID'],
                        'content' => $xmlContenido,
                        'periodo' => $mescom,
                        'ejercicio' => $aniocom,
                        'tipo' => 'Emitidos',
                        'solicitud' => 'Importacion',
                        'team_id' => $team
                    ]);
                }
            }
            catch (\Exception $e){
                error_log($e->getMessage());
            }
        }
    }

    /**
     * Procesa archivos PDF
     */
    private function procesarPDF($archivo, $team): void
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        foreach($files as $desfile) {
            try {
                $file = $archivo . $desfile;
                $fileInfo = pathinfo($file);
                $uuid = strtoupper($fileInfo['filename']);
                DB::table('almacencfdis')->where(DB::raw("UPPER(UUID)"), $uuid)->update([
                    'archivopdf' => $file,
                ]);
            }catch (\Exception $e){
                error_log($e->getMessage());
            }
        }
    }
}
