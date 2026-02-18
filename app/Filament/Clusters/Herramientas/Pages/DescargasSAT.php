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
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mockery\Exception;
use NunoMaduro\Collision\Adapters\Phpunit\State;
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
use App\Services\CfdiSatScraperService;
use App\Services\SatDescargaMasivaService;
use App\Services\XmlProcessorService;



class DescargasSAT extends Page implements HasTable,HasForms
{
    use InteractsWithTable,InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-download';
    protected static string $view = 'filament.clusters.herramientas.pages.descargas-s-a-t';
    protected static ?string $cluster = Herramientas::class;
    protected static ?string $title = 'Descargas SAT';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation () : bool
    {
        return auth()->user()->hasRole(['administrador']);
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(Team::query())
            ->striped()
            ->columns([
                TextColumn::make('id')->label('Registro'),
                TextColumn::make('taxid')->label('RFC')->searchable(),
                TextColumn::make('name')->label('Razón Social')->searchable(),
                TextColumn::make('estado_fiel')->label('Status FIEL'),
                TextColumn::make('vigencia_fiel')->label('Vigencia FIEL')->date('d-m-Y'),
                TextColumn::make('claveciec')->label('CIEC')
                    ->formatStateUsing(function ($state) {
                        if($state != ''){
                            return 'Si';
                        }else{
                            return 'No';
                        }
                    }),
                TextColumn::make('descarga_cfdi')->label('Servicio de Descarga')
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
                        FileUpload::make('archivocer')->label('FIEL CER')->required()->disk('public')->visibility('public')->columnSpan(2)->downloadable(),
                        FileUpload::make('archivokey')->label('FIEL KEY')->required()->disk('public')->visibility('public')->columnSpan(2)->downloadable(),
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
                    Action::make('Consulta_cfdi')
                    ->icon('fas-search')
                    ->label('Consulta CFDI SAT')
                    ->modalWidth('full')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->form(function ($record,Form $form) {
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
                                    ->action(function($record,Set $set,Get $get){
                                        $fecha_inicial = Carbon::create($get('fecha_inicial'))->format('Y-m-d');
                                        $fecha_final = Carbon::create($get('fecha_final'))->format('Y-m-d');
                                        $hoy = Carbon::now()->format('d').Carbon::now()->format('m').Carbon::now()->format('Y');
                                        $rfc = $record->taxid;
                                        $fielcer = storage_path().'/app/public/'.$record->archivocer;
                                        $fielkey = storage_path().'/app/public/'.$record->archivokey;
                                        $fielpass = $record->fielpass;
                                        $cookieJarPath = storage_path().'/app/public/cookies/';
                                        $cookieJarFile = storage_path().'/app/public/cookies/'.$rfc.'.json';
                                        if(File::exists($cookieJarFile)) unlink($cookieJarFile);
                                        $downloadsPath_REC = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/XML/RECIBIDOS/';
                                        $downloadsPath_EMI = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/XML/EMITIDOS/';
                                        $downloadsPath2 = storage_path().'/app/public/cfdis/'.$rfc.'/'.$hoy.'/PDF/';
                                        if (!is_dir($cookieJarPath)) {mkdir($cookieJarPath, 0777, true);}
                                        if (!is_dir($downloadsPath_REC)) {mkdir($downloadsPath_REC, 0777, true);}
                                        if (!is_dir($downloadsPath_EMI)) {mkdir($downloadsPath_EMI, 0777, true);}
                                        if(file_exists($cookieJarFile)) unlink($cookieJarFile);
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
                                            $satScraper->resourceDownloader(ResourceType::xml(), $list_f, 50)->saveTo($file_path, true, 0777);
                                            $satScraper->resourceDownloader(ResourceType::pdf(), $list_f, 50)->saveTo($file_path, true, 0777);
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
                                            $satScraper->resourceDownloader(ResourceType::xml(), $list_f, 50)->saveTo($file_path, true, 0777);
                                            $satScraper->resourceDownloader(ResourceType::pdf(), $list_f, 50)->saveTo($file_path, true, 0777);
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
                                            Actions\Action::make('XML')
                                                ->icon('fas-file')->iconButton()
                                                ->action(function(Get $get){
                                                    return response()->download($get('archivo_xml'));
                                                }),
                                            Actions\Action::make('PDF')
                                                ->icon('fas-file-pdf')->iconButton()
                                                ->action(function(Get $get){
                                                    return response()->download($get('archivo_pdf'));
                                                }),
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
                                                Actions\Action::make('XML')
                                                ->icon('fas-file')->iconButton()
                                                ->action(function(Get $get){
                                                    return response()->download($get('archivo_xml'));
                                                }),
                                                Actions\Action::make('PDF')
                                                ->icon('fas-file-pdf')->iconButton()
                                                    ->action(function(Get $get){
                                                        return response()->download($get('archivo_pdf'));
                                                    }),
                                            ])
                                        ])->columnSpanFull()
                                ])->columns(3)
                        ]);
                    }),
                    Action::make('Descargas')
                        ->icon('fas-download')
                        ->label('Descargar')
                        ->form([
                            DatePicker::make('fecha_inicial')
                                ->label('Fecha Inicial')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                            DatePicker::make('fecha_final')
                                ->label('Fecha Final')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                        ])->visible(function($record){
                            if($record->descarga_cfdi == 'SI') {
                                return true;
                            }else{
                                return false;
                            }
                        })
                        ->action(function($record,$data) {
                            try {
                                $fecha_inicial = Carbon::create($data['fecha_inicial'])->format('Y-m-d');
                                $fecha_final = Carbon::create($data['fecha_final'])->format('Y-m-d');

                                // Calcular días y decidir estrategia
                                $dias = SatDescargaMasivaService::calcularDias($fecha_inicial, $fecha_final);
                                $usarDescargaMasiva = $dias > 30; // Si es más de 30 días, usar descarga masiva

                                $metodoUsado = '';
                                $emitidosCount = 0;
                                $recibidosCount = 0;
                                $xmlProcessor = new XmlProcessorService();

                                // ESTRATEGIA HÍBRIDA CON FALLBACK
                                if ($usarDescargaMasiva) {
                                    // INTENTO 1: Descarga Masiva (método principal para períodos largos)
                                    try {
                                        $masivaService = new SatDescargaMasivaService($record);

                                        // Descargar emitidos
                                        $emitidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'emitidos');
                                        if ($emitidosResult['success']) {
                                            $emitidosCount = $emitidosResult['paquetes_count'] ?? 0;
                                        }

                                        // Descargar recibidos
                                        $recibidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'recibidos');
                                        if ($recibidosResult['success']) {
                                            $recibidosCount = $recibidosResult['paquetes_count'] ?? 0;
                                        }

                                        // Procesar archivos XML
                                        $config = $masivaService->getConfig();
                                        $xmlProcessor->processDirectory($config['downloadsPath']['xml_emitidos'], $record->id, 'Emitidos');
                                        $xmlProcessor->processDirectory($config['downloadsPath']['xml_recibidos'], $record->id, 'Recibidos');

                                        $metodoUsado = 'Descarga Masiva';

                                        // TODO: Aquí se pueden descargar PDFs con scraper si es necesario
                                        // usando los UUIDs extraídos de los XMLs

                                    } catch (\Exception $e) {
                                        // FALLBACK: Si falla descarga masiva, usar scraper
                                        Notification::make()
                                            ->title('Advertencia')
                                            ->body('Descarga masiva falló, intentando con método alterno...')
                                            ->warning()
                                            ->send();

                                        $scraperResult = self::descargarConScraper($record, $fecha_inicial, $fecha_final, $xmlProcessor);
                                        $emitidosCount = $scraperResult['emitidos'];
                                        $recibidosCount = $scraperResult['recibidos'];
                                        $metodoUsado = 'Scraper (Fallback)';
                                    }

                                } else {
                                    // INTENTO 1: Scraper (método principal para períodos cortos)
                                    try {
                                        $scraperResult = self::descargarConScraper($record, $fecha_inicial, $fecha_final, $xmlProcessor);
                                        $emitidosCount = $scraperResult['emitidos'];
                                        $recibidosCount = $scraperResult['recibidos'];
                                        $metodoUsado = 'Scraper';

                                    } catch (\Exception $e) {
                                        // FALLBACK: Si falla scraper, usar descarga masiva
                                        Notification::make()
                                            ->title('Advertencia')
                                            ->body('Scraper falló, intentando con descarga masiva...')
                                            ->warning()
                                            ->send();

                                        $masivaService = new SatDescargaMasivaService($record);

                                        $emitidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'emitidos');
                                        if ($emitidosResult['success']) {
                                            $emitidosCount = $emitidosResult['paquetes_count'] ?? 0;
                                        }

                                        $recibidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'recibidos');
                                        if ($recibidosResult['success']) {
                                            $recibidosCount = $recibidosResult['paquetes_count'] ?? 0;
                                        }

                                        $config = $masivaService->getConfig();
                                        $xmlProcessor->processDirectory($config['downloadsPath']['xml_emitidos'], $record->id, 'Emitidos');
                                        $xmlProcessor->processDirectory($config['downloadsPath']['xml_recibidos'], $record->id, 'Recibidos');

                                        $metodoUsado = 'Descarga Masiva (Fallback)';
                                    }
                                }

                                // Registrar descarga exitosa
                                ValidaDescargas::create([
                                    'fecha' => Carbon::now(),
                                    'inicio' => $fecha_inicial,
                                    'fin' => $fecha_final,
                                    'recibidos' => $recibidosCount,
                                    'emitidos' => $emitidosCount,
                                    'estado' => 'Completado - ' . $metodoUsado,
                                    'team_id' => $record->id
                                ]);

                                Notification::make()
                                    ->title('Proceso Completado')
                                    ->body("Método: {$metodoUsado} | Emitidos: {$emitidosCount}, Recibidos: {$recibidosCount}")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                ValidaDescargas::create([
                                    'fecha' => Carbon::now(),
                                    'inicio' => $fecha_inicial ?? null,
                                    'fin' => $fecha_final ?? null,
                                    'recibidos' => 0,
                                    'emitidos' => 0,
                                    'estado' => 'Error: ' . $e->getMessage(),
                                    'team_id' => $record->id
                                ]);

                                Notification::make()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('Importar XML')
                        ->label('Importar XML')
                        ->icon('fas-upload')
                        ->form([
                            FileUpload::make('archivo_xml')
                                ->directory('TMPCFDI')
                                ->preserveFilenames(),
                            TextInput::make('ruta_archivo')->readOnly(),
                        ])
                        ->action(function($data,$record){
                            $team_id = $record->id;
                            $archivo = storage_path('app/public/').$data['archivo_xml'];
                            //dd($archivo);
                            $xmlContents = \file_get_contents($archivo);
                            $cfdi = Cfdi::newFromString($xmlContents);
                            $comprobante = $cfdi->getQuickReader();
                            $emisor = $comprobante->Emisor;
                            $receptor = $comprobante->Receptor;

                            $tipo = 'CFDI no pertenece a la Razón Social';
                            if($emisor['Rfc']) {
                                self::ProcesaEmitidos_imp($archivo,$team_id);
                                $tipo = 'CFDI Emitido Procesado';
                            }
                            if($receptor['Rfc']) {
                                self::ProcesaRecibidos_imp($archivo,$team_id);
                                $tipo = 'CFDI Recibido Procesado';
                            }
                            Notification::make()->title($tipo)->success()->send();
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
                    $teams = Team::where('descarga_cfdi', 'SI')->get();
                    $fecha_inicial = Carbon::create($data['fecha_inicial'])->format('Y-m-d');
                    $fecha_final = Carbon::create($data['fecha_final'])->format('Y-m-d');

                    // Calcular días y decidir estrategia
                    $dias = SatDescargaMasivaService::calcularDias($fecha_inicial, $fecha_final);
                    $usarDescargaMasiva = $dias > 30;

                    $exitosos = 0;
                    $fallidos = 0;

                    foreach ($teams as $record) {
                        try {
                            $xmlProcessor = new XmlProcessorService();
                            $metodoUsado = '';
                            $emitidosCount = 0;
                            $recibidosCount = 0;

                            // ESTRATEGIA HÍBRIDA CON FALLBACK (igual que el action individual)
                            if ($usarDescargaMasiva) {
                                try {
                                    $masivaService = new SatDescargaMasivaService($record);
                                    $emitidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'emitidos');
                                    if ($emitidosResult['success']) {
                                        $emitidosCount = $emitidosResult['paquetes_count'] ?? 0;
                                    }
                                    $recibidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'recibidos');
                                    if ($recibidosResult['success']) {
                                        $recibidosCount = $recibidosResult['paquetes_count'] ?? 0;
                                    }
                                    $config = $masivaService->getConfig();
                                    $xmlProcessor->processDirectory($config['downloadsPath']['xml_emitidos'], $record->id, 'Emitidos');
                                    $xmlProcessor->processDirectory($config['downloadsPath']['xml_recibidos'], $record->id, 'Recibidos');
                                    $metodoUsado = 'Descarga Masiva';
                                } catch (\Exception $e) {
                                    $scraperResult = self::descargarConScraper($record, $fecha_inicial, $fecha_final, $xmlProcessor);
                                    $emitidosCount = $scraperResult['emitidos'];
                                    $recibidosCount = $scraperResult['recibidos'];
                                    $metodoUsado = 'Scraper (Fallback)';
                                }
                            } else {
                                try {
                                    $scraperResult = self::descargarConScraper($record, $fecha_inicial, $fecha_final, $xmlProcessor);
                                    $emitidosCount = $scraperResult['emitidos'];
                                    $recibidosCount = $scraperResult['recibidos'];
                                    $metodoUsado = 'Scraper';
                                } catch (\Exception $e) {
                                    $masivaService = new SatDescargaMasivaService($record);
                                    $emitidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'emitidos');
                                    if ($emitidosResult['success']) {
                                        $emitidosCount = $emitidosResult['paquetes_count'] ?? 0;
                                    }
                                    $recibidosResult = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'recibidos');
                                    if ($recibidosResult['success']) {
                                        $recibidosCount = $recibidosResult['paquetes_count'] ?? 0;
                                    }
                                    $config = $masivaService->getConfig();
                                    $xmlProcessor->processDirectory($config['downloadsPath']['xml_emitidos'], $record->id, 'Emitidos');
                                    $xmlProcessor->processDirectory($config['downloadsPath']['xml_recibidos'], $record->id, 'Recibidos');
                                    $metodoUsado = 'Descarga Masiva (Fallback)';
                                }
                            }

                            // Registrar éxito
                            ValidaDescargas::create([
                                'fecha' => Carbon::now(),
                                'inicio' => $fecha_inicial,
                                'fin' => $fecha_final,
                                'recibidos' => $recibidosCount,
                                'emitidos' => $emitidosCount,
                                'estado' => 'Completado - ' . $metodoUsado,
                                'team_id' => $record->id
                            ]);

                            $exitosos++;

                        } catch (\Exception $e) {
                            ValidaDescargas::create([
                                'fecha' => Carbon::now(),
                                'inicio' => $fecha_inicial,
                                'fin' => $fecha_final,
                                'recibidos' => 0,
                                'emitidos' => 0,
                                'estado' => 'Error: ' . $e->getMessage(),
                                'team_id' => $record->id
                            ]);

                            $fallidos++;
                        }
                    }

                    Notification::make()
                        ->title('Proceso Completado')
                        ->body("Exitosos: {$exitosos}, Fallidos: {$fallidos}")
                        ->success()
                        ->send();
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
                }),
                Action::make('Archivos PDF')
                ->icon('fas-file-pdf')
                ->label('Archivos PDF')
                ->action(function(){
                    $teams = Team::all();
                    foreach ($teams as $team) {
                        $ciecc = $team?->claveciec ?? 'NA';
                        if($ciecc != 'NA') {
                            $rfc = $team->taxid;
                            $hoy = Carbon::now()->format('d') . Carbon::now()->format('m') . Carbon::now()->format('Y');
                            $archivo = storage_path() . '/app/public/cfdis/' . $rfc . '/' . $hoy . '/PDF/';
                            $files = array_diff(scandir($archivo), array('.', '..'));
                            foreach ($files as $desfile) {
                                try {
                                    $file = $archivo . $desfile;
                                    $fileInfo = pathinfo($file);
                                    $uuid = strtoupper($fileInfo['filename']);
                                    DB::table('almacencfdis')->where(DB::raw("UPPER(UUID)"), $uuid)->update([
                                        'archivopdf' => $file,
                                    ]);
                                } catch (\Exception $e) {
                                    error_log($e->getMessage());
                                }
                            }
                        }
                    }
                }),
                Action::make('valida_fiel')
                ->icon('fas-file-pdf')
                ->label('Validacion FIEL')
                ->requiresConfirmation()
                ->action(function(){
                    $records = Team::all();
                    foreach ($records as $record) {
                        $rfc = $record->taxid;
                        $fielcer = storage_path() . '/app/public/' . $record->archivocer;
                        $fielkey = storage_path() . '/app/public/' . $record->archivokey;
                        $fielpass = $record->fielpass;
                        if (file_exists($fielcer) && file_exists($fielkey) && $fielpass != '') {
                            try {
                                $credential = Credential::openFiles($fielcer, $fielkey, $fielpass);
                                if($credential->isFiel()) {
                                    if($credential->certificate()->validOn()) {
                                        $record->estado_fiel = 'VALIDA';
                                        $record->vigencia_fiel = Carbon::create($credential->certificate()->validToDateTime())->format('Y-m-d');
                                        $record->descarga_cfdi = 'SI';
                                        $record->save();
                                    }
                                    else{
                                        $record->estado_fiel = 'EXPIRADA';
                                        $record->vigencia_fiel = Carbon::create($credential->certificate()->validToDateTime())->format('Y-m-d');
                                        $record->descarga_cfdi = 'NO';
                                        $record->save();
                                    }
                                }
                                else {
                                    $record->estado_fiel = 'NO VALIDA';
                                    $record->descarga_cfdi = 'NO';
                                    $record->save();
                                }
                            }catch(\Exception $e){
                                $record->estado_fiel = 'ERROR DE ARCHIVOS';
                                $record->descarga_cfdi = 'NO';
                                $record->save();
                            }
                        }else{
                            $record->estado_fiel = 'NO VALIDA';
                            $record->descarga_cfdi = 'NO';
                            $record->save();
                        }
                    }
                    Notification::make()->title('Proceso Completado')->success()->send();
                })
            ]);
    }

    /**
     * Método helper para descargar con scraper
     */
    private static function descargarConScraper($record, $fecha_inicial, $fecha_final, $xmlProcessor): array
    {
        $scraperService = new CfdiSatScraperService($record);

        // Inicializar scraper
        $init = $scraperService->initializeScraper();
        if (!$init['valid']) {
            throw new \Exception($init['error']);
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

        // Procesar archivos XML
        $config = $scraperService->getConfig();
        $xmlProcessor->processDirectory($config['downloadsPath']['xml_emitidos'], $record->id, 'Emitidos');
        $xmlProcessor->processDirectory($config['downloadsPath']['xml_recibidos'], $record->id, 'Recibidos');
        $xmlProcessor->processPdfDirectory($config['downloadsPath']['pdf'], $record->id);

        return [
            'emitidos' => $emitidosResult['count'],
            'recibidos' => $recibidosResult['count']
        ];
    }

    public function ProcesaRecibidos($archivo,$team): void
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        foreach($files as $desfile) {
            $file = $archivo . $desfile;
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
                    $pagostot = $pagoscom?->searchNode('pago20:Totales') ?? 0;
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

    public function ProcesaEmitidos($archivo,$team): void
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        foreach($files as $desfile) {
            $file = $archivo . $desfile;
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
                // Verificar si ya existe en almacencfdis (puede venir del timbrado automático)
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

    public function ProcesaPDF($archivo,$team): void
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

    //----------
    public static function ProcesaRecibidos_imp($archivo,$team): void
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
            catch (\Exception $e){
                error_log($e->getMessage());
            }
        }
    }

    public static function ProcesaEmitidos_imp($archivo,$team): void
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
            catch (\Exception $e){
                error_log($e->getMessage());
            }
        }
    }
}
