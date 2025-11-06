<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Models\Almacencfdis;
use App\Models\DescargasArchivosSat;
use App\Models\DescargasSolicitudesSat;
use App\Models\Solicitudes;
use App\Models\Team;
use App\Models\ValidaDescargas;
use App\Models\Xmlfiles;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpCfdi\CfdiSatScraper\Filters\Options\StatesVoucherOption;
use PhpCfdi\CfdiSatScraper\QueryByFilters;
use PhpCfdi\CfdiSatScraper\ResourceType;
use PhpCfdi\CfdiSatScraper\SatScraper;
use PhpCfdi\CfdiSatScraper\Sessions\Ciec\CiecSessionManager;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionManager;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionData;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Pfx\PfxExporter;
use PhpCfdi\Credentials\Pfx\PfxReader;
use PhpCfdi\ImageCaptchaResolver\BoxFacturaAI\BoxFacturaAIResolver;
use PhpCfdi\ImageCaptchaResolver\CaptchaImage;
use PhpCfdi\ImageCaptchaResolver\UnableToResolveCaptchaException;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceType;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;



class DescargasSAT extends Page implements HasTable,HasForms
{
    use InteractsWithTable,InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-download';
    protected static string $view = 'filament.clusters.herramientas.pages.descargas-s-a-t';
    protected static ?string $cluster = Herramientas::class;
    protected static ?string $title = 'Descargas SAT';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(Team::query())
            ->striped()
            ->columns([
                TextColumn::make('id')->label('Registro'),
                TextColumn::make('taxid')->label('RFC')->searchable(),
                TextColumn::make('name')->label('Razón Social')->searchable(),
                TextColumn::make('archivocer')->label('FIEL CER'),
                TextColumn::make('archivokey')->label('FIEL KEY'),
            ])
            ->actions([
                ActionGroup::make([
                \Filament\Tables\Actions\EditAction::make()
                ->label('Editar')
                ->icon('fas-edit')
                ->form(function ($record,Form $form) {
                    return $form->schema([
                        TextInput::make('taxid')->label('RFC')->required()->maxLength(14)->default($record->taxid),
                        TextInput::make('name')->label('RFC')->required()->default($record->name)->columnSpan(3),
                        FileUpload::make('archivocer')->label('FIEL CER')->required()->disk('public')->visibility('public')->columnSpan(2),
                        FileUpload::make('archivokey')->label('FIEL KEY')->required()->disk('public')->visibility('public')->columnSpan(2),
                        TextInput::make('fielpass')->label('Contraseña FIEL')->required()->password()->default($record->fielpass)->revealable(),
                        TextInput::make('claveciec')->label('Clave CIEC')->required()->password()->default($record->fielpass)->revealable(),
                    ])->columns(4);
                }),
                Action::make('Limpiar')
                ->icon('fas-trash')
                ->label('Limpiar')
                ->action(function($record){
                    DB::statement("START TRANSACTION;");
                    DB::statement("DELETE t1 FROM almacencfdis t1
                        INNER JOIN almacencfdis t2 WHERE t1.id < t2.id
                        AND t1.team_id = t2.team_id AND t1.UUID = t2.UUID
                        AND t1.team_id = $record->id;");
                    DB::statement("COMMIT;");
                    Notification::make()->title('Proceso Completado')->success()->send();
                }),
                    Action::make('Descargas')
                        ->icon('fas-download')
                        ->label('Descargar')
                        ->form([
                            DatePicker::make('fecha_inicial')
                                ->label('Fecha Inicial')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                            DatePicker::make('fecha_final')
                                ->label('Fecha Final')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                        ])
                        ->action(function($record,$data) {
                            $fecha_inicial = Carbon::create($data['fecha_inicial'])->format('Y-m-d');
                            $fecha_final = Carbon::create($data['fecha_final'])->format('Y-m-d');
                            $hoy = Carbon::now()->format('d').Carbon::now()->format('m').Carbon::now()->format('Y');
                            $rfc = $record->taxid;
                            $claveCiec = $record->claveciec;
                            $cookieJarPath = storage_path().'/app/public/cookies/';
                            $cookieJarFile = storage_path().'/app/public/cookies/'.$rfc.'.json';
                            $downloadsPath = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/XML/';
                            $downloadsPath2 = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/PDF/';
                            if (!is_dir($cookieJarPath)) {mkdir($cookieJarPath, 0777, true);}
                            if (!is_dir($downloadsPath)) {mkdir($downloadsPath, 0777, true);}
                            if (!file_exists($cookieJarFile)) {fopen($cookieJarFile, 'w');}
                            $client = new Client([
                                'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
                            ]);
                            $gateway = new SatHttpGateway($client, new FileCookieJar($cookieJarFile, true));
                            $configsFile = storage_path().'/app/public/Aimodel/configs.yaml';
                            $captchaResolver = BoxFacturaAIResolver::createFromConfigs($configsFile);
                            $ciecSessionManager = CiecSessionManager::create($rfc, $claveCiec, $captchaResolver);
                            $satScraper = new SatScraper($ciecSessionManager, $gateway);
                            $query = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                            $query->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::emitidos())->setStateVoucher(StatesVoucherOption::vigentes());
                            $list = $satScraper->listByPeriod($query);
                            $satScraper->resourceDownloader(ResourceType::xml(), $list, 50)->saveTo($downloadsPath, true, 0777);
                            $satScraper->resourceDownloader(ResourceType::pdf(), $list, 50)->saveTo($downloadsPath2, true, 0777);
                            $query2 = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                            $query2->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::recibidos())->setStateVoucher(StatesVoucherOption::vigentes());
                            $list2 = $satScraper->listByPeriod($query2);
                            $satScraper->resourceDownloader(ResourceType::xml(), $list2, 50)->saveTo($downloadsPath, true, 0777);
                            $satScraper->resourceDownloader(ResourceType::pdf(), $list2, 50)->saveTo($downloadsPath2, true, 0777);
                            $this->ProcesaArchivoZ_F($downloadsPath,$record->id,$rfc);
                            ValidaDescargas::create([
                                'fecha'=>Carbon::now(),
                                'inicio'=>$fecha_inicial,
                                'fin'=>$fecha_final,
                                'recibidos'=>$list2->count(),
                                'emitidos'=>$list->count(),
                                'estado'=>'Completado',
                                'team_id'=>$record->id
                            ]);
                            Notification::make()->title('Proceso Completado')->success()->send();
                        })
            ])
            ],ActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('Descarga')
                ->label('Descargar')
                ->icon('fas-download')
                ->form([
                    DatePicker::make('fecha_inicial')
                    ->label('Fecha Inicial')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                    DatePicker::make('fecha_final')
                        ->label('Fecha Final')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                ])
                ->action(function($data){
                    $teams = Team::all();
                    $fecha_inicial = Carbon::create($data['fecha_inicial'])->format('Y-m-d');
                    $fecha_final = Carbon::create($data['fecha_final'])->format('Y-m-d');
                    $hoy = Carbon::now()->format('d').Carbon::now()->format('m').Carbon::now()->format('Y');
                    foreach ($teams as $record) {
                        $rfc = $record->taxid;
                        $claveCiec = $record?->claveciec ?? 'NA';
                        try {
                            if ($claveCiec != 'NA') {
                                $cookieJarPath = storage_path() . '/app/public/cookies/';
                                $cookieJarFile = storage_path() . '/app/public/cookies/' . $rfc . '.json';
                                $downloadsPath = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/XML/';
                                $downloadsPath2 = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/PDF/';
                                if (!is_dir($cookieJarPath)) {
                                    mkdir($cookieJarPath, 0777, true);
                                }
                                if (!is_dir($downloadsPath)) {
                                    mkdir($downloadsPath, 0777, true);
                                }
                                if (!file_exists($cookieJarFile)) {
                                    fopen($cookieJarFile, 'w');
                                }
                                $client = new Client([
                                    'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
                                ]);
                                $gateway = new SatHttpGateway($client, new FileCookieJar($cookieJarFile, true));
                                $configsFile = storage_path() . '/app/public/Aimodel/configs.yaml';
                                $captchaResolver = BoxFacturaAIResolver::createFromConfigs($configsFile);
                                $ciecSessionManager = CiecSessionManager::create($rfc, $claveCiec, $captchaResolver);
                                $satScraper = new SatScraper($ciecSessionManager, $gateway);
                                $query = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                                $query->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::emitidos())->setStateVoucher(StatesVoucherOption::vigentes());
                                $list = $satScraper->listByPeriod($query);
                                $satScraper->resourceDownloader(ResourceType::xml(), $list, 50)->saveTo($downloadsPath, true, 0777);
                                $satScraper->resourceDownloader(ResourceType::pdf(), $list, 50)->saveTo($downloadsPath2, true, 0777);
                                $query2 = new QueryByFilters(new \DateTimeImmutable($fecha_inicial), new \DateTimeImmutable($fecha_final));
                                $query2->setDownloadType(\PhpCfdi\CfdiSatScraper\Filters\DownloadType::recibidos())->setStateVoucher(StatesVoucherOption::vigentes());
                                $list2 = $satScraper->listByPeriod($query2);
                                $satScraper->resourceDownloader(ResourceType::xml(), $list2, 50)->saveTo($downloadsPath, true, 0777);
                                $satScraper->resourceDownloader(ResourceType::pdf(), $list2, 50)->saveTo($downloadsPath2, true, 0777);
                                $this->ProcesaArchivoZ_F($downloadsPath, $record->id, $rfc);
                                ValidaDescargas::create([
                                    'fecha' => Carbon::now(),
                                    'inicio' => $fecha_inicial,
                                    'fin' => $fecha_final,
                                    'recibidos' => $list2->count(),
                                    'emitidos' => $list->count(),
                                    'estado' => 'Completado',
                                    'team_id' => $record->id
                                ]);
                            } else {
                                ValidaDescargas::create([
                                    'fecha' => Carbon::now(),
                                    'inicio' => $fecha_inicial,
                                    'fin' => $fecha_final,
                                    'recibidos' => 0,
                                    'emitidos' => 0,
                                    'estado' => 'Error no existe CIEC',
                                    'team_id' => $record->id
                                ]);
                            }
                        }catch (\Exception $e) {
                            ValidaDescargas::create([
                                'fecha' => Carbon::now(),
                                'inicio' => $fecha_inicial,
                                'fin' => $fecha_final,
                                'recibidos' => 0,
                                'emitidos' => 0,
                                'estado' => $e->getMessage(),
                                'team_id' => $record->id
                            ]);
                        }
                    }
                    Notification::make()->title('Proceso Completado')->success()->send();
                }),
                Action::make('Limpiar DB')
                ->icon('fas-trash')
                ->label('Limpiar DB')
                ->action(function(){
                    $teams = Team::all();
                    foreach ($teams as $team) {
                        DB::statement("START TRANSACTION;");
                        DB::statement("DELETE t1 FROM almacencfdis t1
                        INNER JOIN almacencfdis t2 WHERE t1.id < t2.id
                        AND t1.team_id = t2.team_id AND t1.UUID = t2.UUID
                        AND t1.id > 0 AND t1.team_id = $team->id;");
                        DB::statement("COMMIT;");
                    }
                    Notification::make()->title('Proceso Completado')->success()->send();
                })
            ]);
    }
    public function ProcesaArchivoZ_F($archivo,$team,$taxid): int
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        $contador = 0;
        foreach($files as $desfile)
        {
            $file = $archivo.$desfile;
            try {
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
                        if (!isset($pagoscom)) {
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
                $tiposol = "NO IDENTIFICADO";
                if ($emisor['Rfc'] == $taxid) $tiposol = "Emitidos";
                else if ($receptor['Rfc'] == $taxid) $tiposol = "Recibidos";
                if ($tiposol == 'Emitidos') {
                    if ($emisor['Rfc'] == $taxid) {
                        //$uuidno = count(Almacencfdis::where(['UUID' => $tfd['UUID'], 'team_id' => Filament::getTenant()->id])->get() ?? 0);
                        $uuid_v = Almacencfdis::where('UUID',$tfd['UUID'])->where('team_id',$team)->exists();
                        if (!$uuid_v) {
                            Almacencfdis::firstOrCreate([
                                'Serie' => $comprobante['Serie'],
                                'Folio' => $comprobante['Folio'],
                                'Version' => $comprobante['Version'],
                                'Fecha' => $comprobante['Fecha'],
                                'Moneda' => $comprobante['Moneda'],
                                'TipoDeComprobante' => $comprobante['TipoDeComprobante'],
                                'MetodoPago' => $comprobante['MetodoPago'],
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
                                'xml_type' => $tiposol,
                                'periodo' => intval($mescom),
                                'ejercicio' => intval($aniocom),
                                'team_id' => $team
                            ]);
                            Xmlfiles::firstOrCreate([
                                'taxid' => $emisor['Rfc'],
                                'uuid' => $tfd['UUID'],
                                'content' => $xmlContenido,
                                'periodo' => $mescom,
                                'ejercicio' => $aniocom,
                                'tipo' => $tiposol,
                                'solicitud' => 'Importacion',
                                'team_id' => $team
                            ]);
                        }
                    }
                } else {
                    if ($receptor['Rfc'] == $taxid) {
                        //$uuidno = count(Almacencfdis::where(['UUID' => $tfd['UUID'], 'team_id' => Filament::getTenant()->id])->get() ?? 0);
                        $uuid_v = Almacencfdis::where('UUID',$tfd['UUID'])->where('team_id',$team)->exists();
                        if (!$uuid_v){
                            Almacencfdis::firstOrCreate([
                                'Serie' => $comprobante['Serie'],
                                'Folio' => $comprobante['Folio'],
                                'Version' => $comprobante['Version'],
                                'Fecha' => $comprobante['Fecha'],
                                'Moneda' => $comprobante['Moneda'],
                                'TipoDeComprobante' => $comprobante['TipoDeComprobante'],
                                'MetodoPago' => $comprobante['MetodoPago'],
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
                                'xml_type' => $tiposol,
                                'periodo' => intval($mescom),
                                'ejercicio' => intval($aniocom),
                                'team_id' => $team
                            ]);
                            Xmlfiles::firstOrCreate([
                                'taxid' => $emisor['Rfc'],
                                'uuid' => $tfd['UUID'],
                                'content' => $xmlContenido,
                                'periodo' => $mescom,
                                'ejercicio' => $aniocom,
                                'tipo' => $tiposol,
                                'solicitud' => 'Importacion',
                                'team_id' => $team
                            ]);
                        }
                    }
                }
                $contador++;
            }
            catch (\Exception $e) {
                dd($file,$e->getMessage());
            }
            //-----------------------------------------
        }
        return $contador;
    }
}
