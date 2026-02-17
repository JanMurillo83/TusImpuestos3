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
use Illuminate\Support\Facades\File;
use PhpCfdi\CfdiSatScraper\QueryByFilters;
use PhpCfdi\CfdiSatScraper\ResourceType;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;
use PhpCfdi\CfdiSatScraper\SatScraper;
use PhpCfdi\CfdiSatScraper\Sessions\Fiel\FielSessionManager;
use PhpCfdi\Credentials\Credential;
use App\Services\CfdiSatScraperService;
use App\Services\XmlProcessorService;

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
                            ->action(function(Set $set, Get $get){
                                try {
                                    $record = Team::where('id', Filament::getTenant()->id)->first();
                                    $fecha_inicial = Carbon::create($get('fecha_inicial'))->format('Y-m-d');
                                    $fecha_final = Carbon::create($get('fecha_final'))->format('Y-m-d');

                                    // Inicializar servicio
                                    $scraperService = new CfdiSatScraperService($record);

                                    // Validar e inicializar
                                    $init = $scraperService->initializeScraper();
                                    if (!$init['valid']) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body($init['error'])
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Consultar emitidos (sin filtro de vigentes para mostrar todos)
                                    $emitidosResult = $scraperService->listByPeriod($fecha_inicial, $fecha_final, 'emitidos', false);
                                    if (!$emitidosResult['success']) {
                                        throw new \Exception($emitidosResult['error']);
                                    }

                                    // Construir array de emitidos
                                    $emitidos = [];
                                    $config = $scraperService->getConfig();
                                    foreach ($emitidosResult['list'] as $cfdi) {
                                        $uuid = $cfdi->uuid();

                                        // Verificar si existe en sistema
                                        $alm_ = 'NO';
                                        $asociado = 'N/A';
                                        if (DB::table('almacencfdis')->where('team_id', $record->id)->where('UUID', $uuid)->exists()) {
                                            $alm_cfdi = DB::table('almacencfdis')->where('team_id', $record->id)->where('UUID', $uuid)->first();
                                            $alm_ = 'SI';
                                            $asociado = $alm_cfdi->used;
                                        }

                                        $emitidos[] = [
                                            'rfc_receptor' => $cfdi->get('rfcReceptor'),
                                            'nombre' => $cfdi->get('nombreReceptor'),
                                            'fecha' => $cfdi->get('fechaEmision'),
                                            'tipo' => $cfdi->get('efectoComprobante'),
                                            'total' => $cfdi->get('total'),
                                            'estado' => $cfdi->get('estadoComprobante'),
                                            'uuid' => $uuid,
                                            'en_sistema' => $alm_,
                                            'asociado' => $asociado,
                                            'archivo_xml' => $config['tempPath'] . $uuid . '.xml',
                                            'archivo_pdf' => $config['tempPath'] . $uuid . '.pdf'
                                        ];
                                    }
                                    $set('emitidos', $emitidos);

                                    // Consultar recibidos (sin filtro de vigentes para mostrar todos)
                                    $recibidosResult = $scraperService->listByPeriod($fecha_inicial, $fecha_final, 'recibidos', false);
                                    if (!$recibidosResult['success']) {
                                        throw new \Exception($recibidosResult['error']);
                                    }

                                    // Construir array de recibidos
                                    $recibidos = [];
                                    foreach ($recibidosResult['list'] as $cfdi) {
                                        $uuid = $cfdi->uuid();

                                        // Verificar si existe en sistema
                                        $alm_ = 'NO';
                                        $asociado = 'N/A';
                                        if (DB::table('almacencfdis')->where('team_id', $record->id)->where('UUID', $uuid)->exists()) {
                                            $alm_cfdi = DB::table('almacencfdis')->where('team_id', $record->id)->where('UUID', $uuid)->first();
                                            $alm_ = 'SI';
                                            $asociado = $alm_cfdi->used;
                                        }

                                        $recibidos[] = [
                                            'rfc_receptor' => $cfdi->get('rfcEmisor'),
                                            'nombre' => $cfdi->get('nombreEmisor'),
                                            'fecha' => $cfdi->get('fechaEmision'),
                                            'tipo' => $cfdi->get('efectoComprobante'),
                                            'total' => $cfdi->get('total'),
                                            'estado' => $cfdi->get('estadoComprobante'),
                                            'uuid' => $uuid,
                                            'en_sistema' => $alm_,
                                            'asociado' => $asociado,
                                            'archivo_xml' => $config['tempPath'] . $uuid . '.xml',
                                            'archivo_pdf' => $config['tempPath'] . $uuid . '.pdf'
                                        ];
                                    }
                                    $set('recibidos', $recibidos);

                                    Notification::make()
                                        ->title('Consulta Completada')
                                        ->body("Emitidos: {$emitidosResult['count']}, Recibidos: {$recibidosResult['count']}")
                                        ->success()
                                        ->send();

                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error en la Consulta')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
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

    public static function descargar_uuid($uuid, $tipo): void
    {
        try {
            $record = Team::where('id', Filament::getTenant()->id)->first();

            // Inicializar servicios
            $scraperService = new CfdiSatScraperService($record);
            $xmlProcessor = new XmlProcessorService();

            // Inicializar scraper
            $init = $scraperService->initializeScraper();
            if (!$init['valid']) {
                throw new \Exception($init['error']);
            }

            // Descargar por UUID
            $result = $scraperService->downloadByUuid($uuid, $tipo);
            if (!$result['success']) {
                throw new \Exception($result['error']);
            }

            // Procesar el archivo XML descargado
            $xmlType = $tipo === 'emitidos' ? 'Emitidos' : 'Recibidos';
            $processResult = $xmlProcessor->processXmlFile($result['xml_file'], $record->id, $xmlType);

            if (!$processResult['success'] && !$processResult['skipped']) {
                throw new \Exception($processResult['error']);
            }

        } catch (\Exception $e) {
            \Log::error('Error descargando UUID', [
                'uuid' => $uuid,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

}
