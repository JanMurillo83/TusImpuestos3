<?php

namespace App\Filament\Pages;

use App\Exports\BalanceExport;
use App\Exports\DiotExport;
use App\Exports\MainExport;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\MainReportes;
use App\Services\DiotService;
use App\Services\DiotTxtGenerator;
use App\Services\CatalogoCuentasXmlService;
use App\Services\BalanzaComprobacionXmlService;
use App\Services\PolizasXmlService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use Maatwebsite\Excel\Facades\Excel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use Spatie\Browsershot\Browsershot;

class AdminReportesC extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-print';
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Reportes Contables';
    protected static ?string $pluralLabel = 'Reportes Contables';
    protected static string $view = 'filament.pages.admin-reportes-c';
    public ?string $reporte = '';
    public ?string $fecha_inicial = '';
    public ?string $fecha_final = '';
    public ?string $periodo_ini = '';
    public ?string $periodo_fin = '';
    public ?string $cuenta_ini = '';
    public ?string $cuenta_fin = '';
    public ?string $ReportePDF = '';
    public ?string $Reporte_PDF = '';
    public ?string $NewRuta = '';
    public static function shouldRegisterNavigation () : bool
    {
        return auth()->user()->hasRole(['administrador','contador']);
    }
    public function mount():void
    {
        (new \App\Http\Controllers\ReportesController)->ContabilizaReporte(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, Filament::getTenant()->id);
        $this->periodo_ini = Filament::getTenant()->periodo;
        $this->periodo_fin = Filament::getTenant()->periodo;
        $data = [
            'reporte'=>$this->reporte,
            'fecha_inicial'=>$this->fecha_inicial,
            'fecha_final'=>$this->fecha_final,
            'cuenta_ini'=>$this->cuenta_ini,
            'cuenta_fin'=>$this->cuenta_fin,
            'periodo_ini'=>$this->periodo_ini,
            'periodo_fin'=>$this->periodo_fin,
        ];
        $this->ReporteForm->fill($data);
    }

    protected function getReporteFormSchema(): array
    {
        $idAux = MainReportes::where('reporte', 'Reporte de Auxiliares')->first()?->id ?? 4;
        return [
            Select::make('reporte')
                ->options(MainReportes::where('tipo','contable')->pluck('reporte','id'))
                ->required()->live(),
            Fieldset::make('Cuentas Contables')
                ->schema([
                    Group::make([
                        Select::make('cuenta_ini')
                            ->label('Cuenta Inicial')->inlineLabel()
                            ->options(
                                DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                    ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo','asc')->pluck('mostrar','codigo')
                            )
                            ->searchable()->columnSpanFull(),
                        Select::make('cuenta_fin')
                            ->label('Cuenta Final')->inlineLabel()
                            ->options(
                                DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                    ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo','asc')->pluck('mostrar','codigo')
                            )
                            ->searchable()->columnSpanFull(),
                    ])->columnSpan(1),
                ])->columns(1)
                ->visible(function (Get $get) use ($idAux) {
                    $reporte = $get('reporte');
                    if($reporte == $idAux) return true;
                    return false;
                }),
            Fieldset::make('Filtro de Periodo')
                ->schema([
                    Select::make('periodo_ini')->inlineLabel()
                        ->label('Periodo Inicial')->options(['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'])
                        ->default(Filament::getTenant()->periodo),
                    Select::make('periodo_fin')->inlineLabel()
                        ->label('Periodo Final')->options(['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'])
                        ->default(Filament::getTenant()->periodo),
                ])->columns(2)
                ->visible(function (Get $get) use ($idAux) {
                    $reporte = $get('reporte');
                    if($reporte == $idAux) return true;
                    return false;
                }),
            Fieldset::make('Filtro de Periodo')
                ->schema([
                    Select::make('periodo_ini')->inlineLabel()
                        ->label('Periodo')->options(['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'])
                        ->default(Filament::getTenant()->periodo),
                ])->columns(2)
                ->visible(function (Get $get) use ($idAux) {
                    $reporte = $get('reporte');
                    if($reporte == $idAux||$reporte == null) return false;
                    return true;
                }),
            Actions::make([
                Actions\Action::make('Vista Previa')
                ->icon('fas-file-pdf')->extraAttributes(['style'=>'width: 14rem'])
                    ->disabled(function (Get $get){
                        $rep = $get('reporte');
                        $repo = MainReportes::where('id',$rep)->first();
                        $repor = $repo?->pdf ?? '';
                        if($repor == 'SI') return false;
                        else return true;
                    })->action(function(Get $get) use ($idAux) {
                        $this->Reporte_PDF = '';
                        $this->ReportePDF = '';
                        $no_reporte = intval($get('reporte'));

                        $record = MainReportes::where('id',$get('reporte'))->first();
                        $reporte = $record->reporte;
                        $team_id = Filament::getTenant()->id;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = $get('periodo_ini') ?? null;
                        if($periodo == null) $periodo = Filament::getTenant()->periodo;
                        if($no_reporte != $idAux){
                            if($periodo != Filament::getTenant()->periodo){
                                (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            }
                        }
                        $cuentaIni = $get('cuenta_ini') ?? null;
                        $cuentaFin = $get('cuenta_fin') ?? null;
                        $fechaIni = $get('periodo_ini') ?? null;
                        $fechaFin = $get('periodo_fin') ?? null;
                        //dd($cuentaIni,$cuentaFin,$fechaIni,$fechaFin);
                        $path = $record->ruta;

                        $ruta = public_path().'/TMPCFDI/'.str_replace(' ','',$reporte).'_'.$team_id.'.pdf';
                        //dd($ruta);
                        if(\File::exists($ruta)) unlink($ruta);
                        $logo = public_path().'/images/MainLogo.png';
                        $logo_64 = 'data:image/png;base64,'.base64_encode(file_get_contents($logo));
                        $data = [
                            'empresa'=>$team_id,
                            'periodo'=>$periodo,
                            'ejercicio'=>$ejercicio,
                            'cuenta_ini'=>$cuentaIni,
                            'cuenta_fin'=>$cuentaFin,
                            'mes_ini'=>$fechaIni,
                            'mes_fin'=>$fechaFin,
                            'logo'=>$logo_64
                        ];
                        $html = \Illuminate\Support\Facades\View::make($path, $data)->render();
                        Browsershot::html($html)->format('Letter')
                            ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                            ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                            ->noSandbox()
                            ->scale(0.8)->savePdf($ruta);
                        if($no_reporte == 4) {
                            $this->ReportePDF = '';
                            $url_file = public_path('TMPCFDI/Auxiliar.pdf');
                            if(File::exists($url_file)) unlink($url_file);
                            File::copy($ruta, $url_file);
                            $this->Reporte_PDF = 'LLeno';
                        }
                        else {
                            $this->Reporte_PDF = '';
                            $this->ReportePDF = base64_encode(file_get_contents($ruta));
                        }
                        //dd($this->ReportePDF);
                        //(new \App\Http\Controllers\ReportesController)->ContabilizaReporte(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, Filament::getTenant()->id);

                    }),
                Actions\Action::make('Exportar Excel')
                ->icon('fas-file-excel')->extraAttributes(['style'=>'width: 14rem'])
                ->action(function(Get $get) use ($idAux) {
                        $no_reporte = intval($get('reporte'));
                    if($no_reporte == 6) {
                        $empresa = Filament::getTenant()->id;
                        $periodo = $get('periodo_ini') ?? null;
                        if($periodo == null) $periodo = Filament::getTenant()->periodo;
                        $ejercicio = Filament::getTenant()->ejercicio;

                        $diotService = new DiotService();
                        $datos = $diotService->obtenerDatosDiot($periodo, $ejercicio, $empresa);

                        return (new DiotExport($datos, $empresa, $periodo, $ejercicio))
                            ->download('DIOT_' . $ejercicio . '_' . str_pad($periodo, 2, '0', STR_PAD_LEFT) . '.xlsx');
                    }
                    if($no_reporte == 7) {
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = $get('periodo_ini') ?? null;
                        if($periodo == null) $periodo = Filament::getTenant()->periodo;
                        $team_id = Filament::getTenant()->id;

                        $diotService = new DiotService();
                        $datos = $diotService->obtenerDatosDiot($periodo, $ejercicio, $team_id);

                        $txtGenerator = new DiotTxtGenerator();
                        $ruta = $txtGenerator->generar($datos, $periodo, $ejercicio, $team_id);

                        return response()->download($ruta);
                    }
                        $record = MainReportes::where('id',$get('reporte'))->first();
                        $reporte = $record->reporte;
                        $team_id = Filament::getTenant()->id;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = $get('periodo_ini') ?? null;
                        if($periodo == null) $periodo = Filament::getTenant()->periodo;
                        if($no_reporte != $idAux){
                            if($periodo != Filament::getTenant()->periodo){
                                (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            }
                        }
                        $cuentaIni = $get('cuenta_ini') ?? null;
                        $cuentaFin = $get('cuenta_fin') ?? null;
                        $fechaIni = $get('periodo_ini') ?? null;
                        $fechaFin = $get('periodo_fin') ?? null;
                        //dd($cuentaIni,$cuentaFin,$fechaIni,$fechaFin);
                        $path = $record->ruta_excel;
                        $logo_64 = '';
                        $data = [
                            'empresa'=>$team_id,
                            'periodo'=>$periodo,
                            'ejercicio'=>$ejercicio,
                            'cuenta_ini'=>$cuentaIni,
                            'cuenta_fin'=>$cuentaFin,
                            'mes_ini'=>$fechaIni,
                            'mes_fin'=>$fechaFin,
                            'logo'=>$logo_64
                        ];
                        $nombre = $reporte.'_'.$periodo.'_'.$ejercicio.'.xlsx';
                        return (new MainExport($path, $data))->download($nombre);
                    }),
                Actions\Action::make('Descargar XML')
                    ->icon('fas-file-code')->extraAttributes(['style'=>'width: 14rem'])
                    ->disabled(function (Get $get){
                        $rep = $get('reporte');
                        $repo = MainReportes::where('id',$rep)->first();
                        $ruta = $repo?->ruta ?? '';
                        // Solo habilitar para reportes XML (CatalogoCuentas_XML, BalanzaComprobacion_XML, PolizasPeriodo_XML)
                        if(str_contains($ruta, '_XML')) return false;
                        else return true;
                    })
                    ->action(function(Get $get) {
                        $record = MainReportes::where('id',$get('reporte'))->first();
                        $ruta = $record->ruta;
                        $team_id = Filament::getTenant()->id;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = $get('periodo_ini') ?? Filament::getTenant()->periodo;

                        try {
                            if($ruta == 'CatalogoCuentas_XML') {
                                $service = new CatalogoCuentasXmlService();
                                $archivoGenerado = $service->generar($team_id, $ejercicio, $periodo);
                                return response()->download($archivoGenerado);
                            }
                            elseif($ruta == 'BalanzaComprobacion_XML') {
                                $service = new BalanzaComprobacionXmlService();
                                $archivoGenerado = $service->generar($team_id, $ejercicio, $periodo);
                                return response()->download($archivoGenerado);
                            }
                            elseif($ruta == 'PolizasPeriodo_XML') {
                                $service = new PolizasXmlService();
                                $archivoGenerado = $service->generar($team_id, $ejercicio, $periodo);
                                return response()->download($archivoGenerado);
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al generar XML')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
        ];
    }

    protected function getPreviewFormSchema(): array
    {
        return [
            View::make('ReportesAdmin.your-pdf-viewer')
                ->columnSpanFull()
                ->viewData(function () {
                    return ['pdfBase64' => $this->ReportePDF ?? 'No se ha cargado'] ;
                })->visible(function (){
                    if($this->ReportePDF == '') return false;
                    else return true;
                }),
            PdfViewerField::make('Visor')
                ->columnSpanFull()
                ->minHeight('80svh')
                ->visible(function (){
                    if($this->Reporte_PDF == '') return false;
                    else return true;
                })
                ->fileUrl(URL::asset('TMPCFDI/Auxiliar.pdf')),
        ];
    }

    protected function getForms(): array
    {
        return [
            'ReporteForm' => $this->makeForm()
                ->schema($this->getReporteFormSchema()),
            'PreviewForm' => $this->makeForm()
                ->schema($this->getPreviewFormSchema()),
        ];
    }
}
