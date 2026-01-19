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
        $hoy = Carbon::now()->format('d').Carbon::now()->format('m').Carbon::now()->format('Y').Carbon::now()->format('H').Carbon::now()->format('i').Carbon::now()->format('s');
        $rfc = $record->taxid;
        $fielcer = storage_path().'/app/public/'.$record->archivocer;
        $fielkey = storage_path().'/app/public/'.$record->archivokey;
        $fielpass = $record->fielpass;
        $count_emitidos = 0;
        $count_recibidos = 0;
        $data_emitidos = [];
        $data_recibidos = [];
        if(file_exists($fielcer) && file_exists($fielkey) && $fielpass != '') {
            try {
                $cookieJarPath = storage_path() . '/app/public/cookies/';
                $cookieJarFile = storage_path() . '/app/public/cookies/' . $rfc . '.json';
                if(File::exists($cookieJarFile)) unlink($cookieJarFile);
                $downloadsPath_REC = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/XML/RECIBIDOS/';
                $downloadsPath_EMI = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/XML/EMITIDOS/';
                $downloadsPath2 = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/PDF/';
                if (!is_dir($cookieJarPath)) {
                    mkdir($cookieJarPath, 0777, true);
                }
                if (!is_dir($downloadsPath_REC)) {
                    mkdir($downloadsPath_REC, 0777, true);
                }
                if (!is_dir($downloadsPath_EMI)) {
                    mkdir($downloadsPath_EMI, 0777, true);
                }
                if(file_exists($cookieJarFile)) unlink($cookieJarFile);
                if (!file_exists($cookieJarFile)) {
                    fopen($cookieJarFile, 'w');
                }
                $client = new Client([
                    'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
                ]);
                $gateway = new SatHttpGateway($client, new FileCookieJar($cookieJarFile, true));
                $credential = Credential::openFiles($fielcer, $fielkey, $fielpass);
                $fielSessionManager = FielSessionManager::create($credential);
                if($credential->isFiel()&&$credential->certificate()->validOn()) {
                    $satScraper = new SatScraper($fielSessionManager, $gateway);
                    $query = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                    $query->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::emitidos());
                    $list = $satScraper->listByPeriod($query);
                    $query2 = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                    $query2->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::recibidos());
                    $list2 = $satScraper->listByPeriod($query2);
                    $count_recibidos = $list2->count();
                    $count_emitidos = $list->count();
                    $data_emitidos = $list;
                    $data_recibidos = $list2;
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return ['recibidos' => $count_recibidos, 'emitidos' => $count_emitidos,'data_emitidos' => $data_emitidos,'data_recibidos' => $data_recibidos];
    }

    public function Descarga($team_id,$uuids) : array
    {
        $data_emitidos=0;
        $data_recibidos=0;
        $record = Team::where('id',$team_id)->first();
        $rfc = $record->taxid;
        $fielcer = storage_path().'/app/public/'.$record->archivocer;
        $fielkey = storage_path().'/app/public/'.$record->archivokey;
        $fielpass = $record->fielpass;
        $hoy = Carbon::now()->format('dmYHis');
        if(file_exists($fielcer) && file_exists($fielkey) && $fielpass != '') {
            try {
                $cookieJarPath = storage_path() . '/app/public/cookies/';
                $cookieJarFile = storage_path() . '/app/public/cookies/' . $rfc . '.json';
                if(File::exists($cookieJarFile)) unlink($cookieJarFile);
                $downloadsPath_REC = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/XML/RECIBIDOS/';
                $downloadsPath_EMI = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/XML/EMITIDOS/';
                if (!is_dir($cookieJarPath)) {
                    mkdir($cookieJarPath, 0777, true);
                }
                if (!is_dir($downloadsPath_REC)) {
                    mkdir($downloadsPath_REC, 0777, true);
                }
                if (!is_dir($downloadsPath_EMI)) {
                    mkdir($downloadsPath_EMI, 0777, true);
                }
                if(file_exists($cookieJarFile)) unlink($cookieJarFile);
                if (!file_exists($cookieJarFile)) {
                    fopen($cookieJarFile, 'w');
                }
                $client = new Client([
                    'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
                ]);
                $gateway = new SatHttpGateway($client, new FileCookieJar($cookieJarFile, true));
                $credential = Credential::openFiles($fielcer, $fielkey, $fielpass);
                $fielSessionManager = FielSessionManager::create($credential);
                if($credential->isFiel()&&$credential->certificate()->validOn()) {
                    $satScraper = new SatScraper($fielSessionManager, $gateway);
                    $list = $satScraper->listByUuids($uuids,DownloadType::emitidos());
                    $satScraper->resourceDownloader(ResourceType::xml(), $list)->saveTo($downloadsPath_EMI, true, 0777);
                    $list2 = $satScraper->listByUuids($uuids,DownloadType::recibidos());
                    $satScraper->resourceDownloader(ResourceType::xml(), $list2)->saveTo($downloadsPath_REC, true, 0777);
                    \Safe\sleep(1);
                    $extension = '*.xml';
                    $files = glob($downloadsPath_EMI . $extension);
                    $data_emitidos+= self::Procesa($files,$team_id,'Emitidos');
                    $files2 = glob($downloadsPath_REC . $extension);
                    $data_recibidos+= self::Procesa($files2,$team_id,'Recibidos');
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return ['data_emitidos' => $data_emitidos,'data_recibidos' => $data_recibidos];
    }

    public function Procesa($files,$team,$tipo_f) : int
    {
        $count_f = 0;
        foreach($files as $desfile) {
            $file = $desfile;
            try{
                $xmlContents = \file_get_contents($file);
                $cfdi = Cfdi::newFromString($xmlContents);
                $comprobante = $cfdi->getNode();
                //dd($comprobante);
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
                //dd($xmlContenido);
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
                        'xml_type' => $tipo_f,
                        'periodo' => intval($mescom),
                        'ejercicio' => intval($aniocom),
                        'team_id' => $team,
                        'archivoxml'=>$file
                    ]);
                    $count_f++;
                }
            }
            catch (\Exception $e){
                throw new \Exception($e->getMessage());
            }
        }
        return $count_f;
    }
}
