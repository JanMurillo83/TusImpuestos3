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

    /**
     * @deprecated Usar NewCFDI->Scraper() que utiliza CfdiSatScraperService
     */
    public function Scraper($record,$fecha_i,$fecha_f) : array
    {
        return app(NewCFDI::class)->Scraper($record->id, $fecha_i, $fecha_f);
    }
}
