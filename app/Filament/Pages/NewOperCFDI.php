<?php

namespace App\Filament\Pages;

use App\Http\Controllers\NewCFDI;
use App\Models\DatosFiscales;
use App\Models\Team;
use App\Models\TempCfdis;
use App\Models\ValidaDescargas;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
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
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use phpDocumentor\Reflection\Types\Integer;
use function Psl\Str\replace;

class NewOperCFDI extends Page implements HasForms,HasActions, HasTable
{
    use \Filament\Forms\Concerns\InteractsWithForms, \Filament\Actions\Concerns\InteractsWithActions, InteractsWithTable;
    protected static ?string $navigationIcon = 'fas-building-circle-arrow-right';
    protected static ?string $navigationGroup = 'Operaciones CFDI';
    protected static ?string $navigationLabel = 'CFDI SAT';
    protected static ?string $title = 'CFDI SAT';
    protected static string $view = 'filament.pages.new-oper-c-f-d-i';
    protected static bool $shouldRegisterNavigation = false;

    public ?string $fecha_inicial;
    public ?string $fecha_final;
    public function mount(){
        app(NewCFDI::class)->borrar(Filament::getTenant()->id);
    }
    public function table(Table $table ):Table
    {
        return $table
            ->query(TempCfdis::query())
            ->modifyQueryUsing(fn($query) => $query->where('team_id',Filament::getTenant()->id))
            ->columns([
                TextColumn::make('UUID')->label('UUID'),
                TextColumn::make('RfcEmisor')->label('Emisor RFC'),
                TextColumn::make('NombreEmisor')->label('Emisor Nombre'),
                TextColumn::make('RfcReceptor')->label('Receptor RFC'),
                TextColumn::make('NombreReceptor')->label('Receptor Nombre'),
                TextColumn::make('FechaEmision')->label('Fecha')
                ->getStateUsing(fn($state) => Carbon::parse($state)->format('d-m-Y')),
                TextColumn::make('Monto')
                ->getStateUsing(fn($state) => '$'.number_format($state,2)),
                TextColumn::make('EfectoComprobante')->label('Efecto'),
                TextColumn::make('Estatus')
                ->getStateUsing(function ($state) {
                    if($state==1)return '<span class="text-green-700">Vigente</span>';
                    else return '<span class="text-red-700">Cancelado</span>';
                }),
                TextColumn::make('FechaCancelacion')->label('F.Cancelacion'),
                TextColumn::make('Tipo')
            ])->headerActions([
                Action::make('Descargar')
                ->icon('fas-download')
                ->form(function (Form $form){
                    return $form->schema([
                        Section::make('Filtro')
                        ->schema([
                            DatePicker::make('fecha_inicial')
                            ->label('Fecha Inicial')
                            ->default(Carbon::now()->format('Y-m-d')),
                            DatePicker::make('fecha_final')
                            ->label('Fecha Final')
                            ->default(Carbon::now()->format('Y-m-d')),
                        ])
                    ]);
                })
                ->modalWidth('sm')
                ->modalSubmitActionLabel('Descargar')
                ->action(function (array $data){
                    set_time_limit(300);
                    $inicial = Carbon::parse($data['fecha_inicial'])->format('Y-m-d');
                    $final = Carbon::parse($data['fecha_final'])->format('Y-m-d');
                    $record = Team::where('id',Filament::getTenant()->id)->first();
                    $resultado = self::Scraper($record,$inicial,$final);
                    $no_emitidos = $resultado['emitidos'];
                    $no_recibidos = $resultado['recibidos'];
                    $data_emitidos = $resultado['data_emitidos'];
                    $data_recibidos = $resultado['data_recibidos'];
                    if($no_emitidos==0 && $no_recibidos==0) {
                        Notification::make()
                            ->title('Proceso Terminado')
                            ->body('NO se encontraron registros para la fecha seleccionada')
                            ->danger()->send();
                        return;
                    }
                    $all_data = [];
                    foreach ($data_emitidos as $data) {
                        /** @var \PhpCfdi\CfdiSatScraper\Metadata $data */
                        $all_data[] = [
                            "UUID" => $data->uuid(),
                            "RfcEmisor" => $data->rfcEmisor,
                            "NombreEmisor" => $data->nombreEmisor,
                            "RfcReceptor" => $data->rfcReceptor,
                            "NombreReceptor" => $data->nombreReceptor,
                            "RfcPac" => $data->pacCertifico,
                            "FechaEmision" => $data->fechaEmision,
                            "FechaCertificacionSat" => $data->fechaCertificacion,
                            "Monto" => floatval(str_replace([',','$'],['',''],$data->total)),
                            "EfectoComprobante" => $data->efectoComprobante,
                            "Estatus" => $data->estadoComprobante,
                            "FechaCancelacion" => $data->fechaDeCancelacion,
                            "Tipo" => 'Emitidos',
                            "team_id" => Filament::getTenant()->id
                        ];
                    }
                    foreach ($data_recibidos as $data) {
                        /** @var \PhpCfdi\CfdiSatScraper\Metadata $data */
                        $all_data[]=[
                            "UUID" => $data->uuid(),
                            "RfcEmisor" => $data->rfcEmisor,
                            "NombreEmisor" => $data->nombreEmisor,
                            "RfcReceptor" => $data->rfcReceptor,
                            "NombreReceptor" => $data->nombreReceptor,
                            "RfcPac" => $data->pacCertifico,
                            "FechaEmision" => $data->fechaEmision,
                            "FechaCertificacionSat" => $data->fechaCertificacion,
                            "Monto" => floatval(str_replace([',','$'],['',''],$data->total)),
                            "EfectoComprobante" => $data->efectoComprobante,
                            "Estatus" => $data->estadoComprobante,
                            "FechaCancelacion" => $data->fechaDeCancelacion,
                            "Tipo" => 'Recibidos',
                            "team_id" => Filament::getTenant()->id
                        ];
                    }

                    $regs = app(NewCFDI::class)->graba($all_data);
                    Notification::make()
                        ->title('Proceso Terminado')
                        ->body('Se han procesado '.$regs.'Registros Totales - '.$no_emitidos.' emitidos y '.$no_recibidos.' recibidos')
                        ->success()->send();
                })
            ],HeaderActionsPosition::Bottom);
    }

    public function Scraper($record,$fecha_i,$fecha_f) : array
    {
        $fecha_inicial = Carbon::create($fecha_i)->format('Y-m-d');
        $fecha_final = Carbon::create($fecha_f)->format('Y-m-d');
        $hoy = Carbon::now()->format('d').Carbon::now()->format('m').Carbon::now()->format('Y');
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
}
