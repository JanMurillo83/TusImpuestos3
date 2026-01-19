<?php

namespace App\Filament\Pages;

use App\Models\Almacencfdis;
use App\Models\Team;
use App\Models\Xmlfiles;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Support\Facades\DB;
use PhpCfdi\CfdiSatScraper\QueryByFilters;
use PhpCfdi\CfdiSatScraper\ResourceType;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;
use PhpCfdi\CfdiSatScraper\SatScraper;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionManager;
use PhpCfdi\Credentials\Credential;

class ConsultaCFDISAT extends Page implements HasForms,HasActions
{
    use InteractsWithForms,InteractsWithActions;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Operaciones CFDI';
    protected static ?string $navigationLabel = 'Consulta CFDI SAT';
    protected static ?string $title = 'Consulta CFDI SAT';
    protected static string $view = 'filament.pages.consulta-c-f-d-i-s-a-t';
    protected static bool $shouldRegisterNavigation = false;
    public ?string $fecha_inicial;
    public ?string $fecha_final;
    public array $emitidos = [];
    public array $recibidos = [];

    public function mount(){
        $fecha_inicial = Carbon::now()->subDays(1)->format('Y-m-d');
        $fecha_final = Carbon::now()->subDays(1)->format('Y-m-d');
        $emitidos = [];
        $recibidos = [];
        $data = ['fecha_inicial'=>$fecha_inicial,'fecha_final'=>$fecha_final,'emitidos'=>$emitidos,'recibidos'=>$recibidos];
        $this->form->fill($data);
    }
    public function form(Form $form): Form
    {
        return $form->schema([
            Fieldset::make('Periodo')
                ->schema([
                    DatePicker::make('fecha_inicial')
                        ->label('Fecha Inicial')
                        ->default(Carbon::now()
                            ->subDays(1)
                            ->format('Y-m-d')),
                    DatePicker::make('fecha_final')
                        ->label('Fecha Final')
                        ->default(Carbon::now()
                            ->subDays(1)
                            ->format('Y-m-d')),
                    Actions::make([
                        Actions\Action::make('Consulta')
                            ->icon('fas-search')
                            ->label('Consulta')
                            ->action(function(Set $set,Get $get){
                                $record = Team::where('id',Filament::getTenant()->id)->first();
                                $fecha_inicial = Carbon::create($get('fecha_inicial'))->format('Y-m-d');
                                $fecha_final = Carbon::create($get('fecha_final'))->format('Y-m-d');
                                $hoy = Carbon::now()->format('d').Carbon::now()->format('m').Carbon::now()->format('Y');
                                $rfc = $record->taxid;
                                $fielcer = storage_path().'/app/public/'.$record->archivocer;
                                $fielkey = storage_path().'/app/public/'.$record->archivokey;
                                $fielpass = $record->fielpass;
                                //dd($fecha_inicial,$fecha_final,$rfc,$fielcer,$fielkey,$fielpass);
                                $cookieJarPath = storage_path().'/app/public/cookies/';
                                $cookieJarFile = storage_path().'/app/public/cookies/'.$rfc.'.json';
                                $downloadsPath_REC = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/XML/RECIBIDOS/';
                                $downloadsPath_EMI = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/XML/EMITIDOS/';
                                $downloadsPath2 = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/PDF/';
                                if (!is_dir($cookieJarPath)) {mkdir($cookieJarPath, 0777, true);}
                                if (!is_dir($downloadsPath_REC)) {mkdir($downloadsPath_REC, 0777, true);}
                                if (!is_dir($downloadsPath_EMI)) {mkdir($downloadsPath_EMI, 0777, true);}
                                if (!file_exists($cookieJarFile)) {fopen($cookieJarFile, 'w');}
                                $client = new Client([
                                    'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
                                ]);
                                $gateway = new SatHttpGateway($client, new FileCookieJar($cookieJarFile, true));
                                $credential = Credential::openFiles($fielcer, $fielkey, $fielpass);
                                $fielSessionManager = FielSessionManager::create($credential);
                                $satScraper = new SatScraper($fielSessionManager, $gateway);
                                $query = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                                $query->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::emitidos());
                                $list = $satScraper->listByPeriod($query);
                                $emitidos = [];
                                foreach ($list as $cfdi) {
                                    $UU = $cfdi->uuid();
                                    $uuids = [$UU];
                                    $file_path = storage_path().'/app/public/TEMP_'.$record->taxid;
                                    $xml_file = storage_path().'/app/public/TEMP_'.$record->taxid.'/'.$UU.'.xml';
                                    $pdf_file = storage_path().'/app/public/TEMP_'.$record->taxid.'/'.$UU.'.pdf';
                                    $list_f = $satScraper->listByUuids($uuids,\PhpCfdi\CfdiSatScraper\Filters\DownloadType::emitidos());
                                    //$satScraper->resourceDownloader(ResourceType::xml(), $list_f, 50)->saveTo($file_path, true, 0777);
                                    //$satScraper->resourceDownloader(ResourceType::pdf(), $list_f, 50)->saveTo($file_path, true, 0777);
                                    //dd($archivo_xml,$archivo_pdf);
                                    $alm_ = 'NO';
                                    $asociado = 'N/A';
                                    if(DB::table('almacencfdis')->where('team_id',$record->id)->where('UUID',$UU)->exists()) {
                                        $alm_cfdi = DB::table('almacencfdis')->where('team_id', $record->id)->where('UUID', $UU)->first();
                                        $alm_ = 'SI';
                                        $asociado = $alm_cfdi->used;
                                    }
                                    $emitidos[] = [
                                        'rfc_receptor'=>$cfdi->get('rfcReceptor'),
                                        'nombre'=>$cfdi->get('nombreReceptor'),
                                        'fecha'=>$cfdi->get('fechaEmision'),
                                        'tipo'=>$cfdi->get('efectoComprobante'),
                                        'total'=>$cfdi->get('total'),
                                        'estado'=>$cfdi->get('estadoComprobante'),
                                        'uuid'=>$cfdi->uuid(),
                                        'en_sistema'=>$alm_,
                                        'asociado'=>$asociado,
                                        'archivo_xml'=>$xml_file,
                                        'archivo_pdf'=>$pdf_file
                                    ];
                                }
                                $set('emitidos', $emitidos);
                                $query2 = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                                $query2->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::recibidos());
                                $list2 = $satScraper->listByPeriod($query2);
                                $recibidos = [];
                                foreach ($list2 as $cfdi) {
                                    $UU = $cfdi->uuid();
                                    $uuids = [$UU];
                                    $file_path = storage_path().'/app/public/TEMP_'.$record->taxid;
                                    $xml_file = storage_path().'/app/public/TEMP_'.$record->taxid.'/'.$UU.'.xml';
                                    $pdf_file = storage_path().'/app/public/TEMP_'.$record->taxid.'/'.$UU.'.pdf';
                                    $list_f = $satScraper->listByUuids($uuids,\PhpCfdi\CfdiSatScraper\Filters\DownloadType::emitidos());
                                    //$satScraper->resourceDownloader(ResourceType::xml(), $list_f, 50)->saveTo($file_path, true, 0777);
                                    //$satScraper->resourceDownloader(ResourceType::pdf(), $list_f, 50)->saveTo($file_path, true, 0777);
                                    $alm_ = 'NO';
                                    $asociado = 'N/A';
                                    if(DB::table('almacencfdis')->where('team_id',$record->id)->where('UUID',$UU)->exists()) {
                                        $alm_cfdi = DB::table('almacencfdis')->where('team_id', $record->id)->where('UUID', $UU)->first();
                                        $alm_ = 'SI';
                                        $asociado = $alm_cfdi->used;
                                    }
                                    $recibidos[] = [
                                        'rfc_receptor'=>$cfdi->get('rfcEmisor'),
                                        'nombre'=>$cfdi->get('nombreEmisor'),
                                        'fecha'=>$cfdi->get('fechaEmision'),
                                        'tipo'=>$cfdi->get('efectoComprobante'),
                                        'total'=>$cfdi->get('total'),
                                        'estado'=>$cfdi->get('estadoComprobante'),
                                        'uuid'=>$cfdi->uuid(),
                                        'en_sistema'=>$alm_,
                                        'asociado'=>$asociado,
                                        'archivo_xml'=>$xml_file,
                                        'archivo_pdf'=>$pdf_file
                                    ];
                                }
                                $set('recibidos', $recibidos);
                            }),
                    ]),
                    TableRepeater::make('emitidos')
                        ->addable(false)
                        ->reorderable(false)
                        ->deletable(false)
                        ->emptyLabel('No existen registros')
                        ->streamlined()
                        ->headers([
                            Header::make('RFC Receptor'),
                            Header::make('Razón Social'),
                            Header::make('Fecha'),
                            Header::make('Tipo'),
                            Header::make('Total'),
                            Header::make('Estado'),
                            Header::make('UUID'),
                            Header::make('Existe en Sistema'),
                            Header::make('Asociado'),
                            Header::make('')
                        ])
                        ->schema([
                            TextInput::make('rfc_receptor')->readOnly(),
                            TextInput::make('nombre')->readOnly(),
                            TextInput::make('fecha')->readOnly(),
                            TextInput::make('tipo')->readOnly(),
                            TextInput::make('total')->readOnly(),
                            TextInput::make('estado')->readOnly(),
                            TextInput::make('uuid')->readOnly(),
                            TextInput::make('en_sistema')->readOnly(),
                            TextInput::make('asociado')->readOnly(),
                            Hidden::make('archivo_xml'),
                            Hidden::make('archivo_pdf'),
                            Actions::make([
                                Actions\Action::make('Descargar XML')
                                    ->icon('fas-download')->iconButton()
                                    ->disabled(function (Get $get){
                                        if($get('en_sistema')=='NO') return false;
                                        else return true;
                                    })
                                    ->action(function(Get $get){
                                        $uuid = $get('uuid');
                                        $tipo = 'emitidos';
                                        self::descargar_uuid($uuid,$tipo);
                                        Notification::make()->title('Archivo Descragado')->success()->send();
                                    })
                            ])
                        ])->columnSpanFull(),
                    TableRepeater::make('recibidos')
                        ->addable(false)
                        ->reorderable(false)
                        ->deletable(false)
                        ->emptyLabel('No existen registros')
                        ->streamlined()
                        ->headers([
                            Header::make('RFC Emisor'),
                            Header::make('Razón Social'),
                            Header::make('Fecha'),
                            Header::make('Tipo'),
                            Header::make('Total'),
                            Header::make('Estado'),
                            Header::make('UUID'),
                            Header::make('Existe en Sistema'),
                            Header::make('Asociado'),
                            Header::make(''),
                        ])
                        ->schema([
                            TextInput::make('rfc_receptor')->readOnly(),
                            TextInput::make('nombre')->readOnly(),
                            TextInput::make('fecha')->readOnly(),
                            TextInput::make('tipo')->readOnly(),
                            TextInput::make('total')->readOnly(),
                            TextInput::make('estado')->readOnly(),
                            TextInput::make('uuid')->readOnly(),
                            TextInput::make('en_sistema')->readOnly(),
                            TextInput::make('asociado')->readOnly(),
                            Hidden::make('archivo_xml'),
                            Hidden::make('archivo_pdf'),
                            Actions::make([
                                Actions\Action::make('Descargar XML')
                                    ->icon('fas-download')->iconButton()
                                    ->disabled(function (Get $get){
                                        if($get('en_sistema')=='NO') return false;
                                        else return true;
                                    })
                                    ->action(function(Get $get){
                                        $uuid = $get('uuid');
                                        $tipo = 'recibidos';
                                        self::descargar_uuid($uuid,$tipo);
                                        Notification::make()->title('Archivo Descragado')->success()->send();
                                    })
                            ])
                        ])->columnSpanFull()
                ])->columns(3)
        ]);
    }

    public static function descargar_uuid($uuid,$tipo): void
    {
        $record = Team::where('id',Filament::getTenant()->id)->first();
        $rfc = $record->taxid;
        $hoy = Carbon::now()->format('d').Carbon::now()->format('m').Carbon::now()->format('Y');
        $fielcer = storage_path().'/app/public/'.$record->archivocer;
        $fielkey = storage_path().'/app/public/'.$record->archivokey;
        $fielpass = $record->fielpass;
        $cookieJarPath = storage_path().'/app/public/cookies/';
        $cookieJarFile = storage_path().'/app/public/cookies/'.$rfc.'.json';
        $downloadsPath_REC = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/XML/RECIBIDOS/';
        $downloadsPath_EMI = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/XML/EMITIDOS/';
        $downloadsPath2 = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/PDF/';
        if (!is_dir($cookieJarPath)) {mkdir($cookieJarPath, 0777, true);}
        if (!is_dir($downloadsPath_REC)) {mkdir($downloadsPath_REC, 0777, true);}
        if (!is_dir($downloadsPath_EMI)) {mkdir($downloadsPath_EMI, 0777, true);}
        if (!file_exists($cookieJarFile)) {fopen($cookieJarFile, 'w');}
        $client = new Client([
            'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
        ]);
        $gateway = new SatHttpGateway($client, new FileCookieJar($cookieJarFile, true));
        $credential = Credential::openFiles($fielcer, $fielkey, $fielpass);
        $fielSessionManager = FielSessionManager::create($credential);
        $satScraper = new SatScraper($fielSessionManager, $gateway);
        $uuids = [$uuid];
        $file_path = storage_path().'/app/public/TEMP_'.$record->taxid;
        $xml_file = storage_path().'/app/public/TEMP_'.$record->taxid.'/'.$uuid.'.xml';
        $pdf_file = storage_path().'/app/public/TEMP_'.$record->taxid.'/'.$uuid.'.pdf';
        if($tipo=='emitidos') {
            $list = $satScraper->listByUuids($uuids, \PhpCfdi\CfdiSatScraper\Filters\DownloadType::emitidos());
        }
        else {
            $list = $satScraper->listByUuids($uuids, \PhpCfdi\CfdiSatScraper\Filters\DownloadType::recibidos());
        }
        $satScraper->resourceDownloader(ResourceType::xml(), $list, 50)->saveTo($file_path, true, 0777);
        $satScraper->resourceDownloader(ResourceType::pdf(), $list, 50)->saveTo($file_path, true, 0777);

        if($tipo=='emitidos')
            self::ProcesaEmitidos($xml_file,$record->id);
        else
            self::ProcesaRecibidos($pdf_file,$record->id);

    }

    public static function ProcesaRecibidos($archivo,$team): void
    {
        $files = [$archivo];
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

    public static function ProcesaEmitidos($archivo,$team): void
    {
        $files = [$archivo];
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

    public static function ProcesaPDF($archivo,$team): void
    {
        $files = [$archivo];
        foreach($files as $desfile) {
            try {
                $file = $desfile;
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
