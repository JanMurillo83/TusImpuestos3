<?php

namespace App\Filament\Pages;

use App\Exports\BalanceExport;
use App\Exports\MainExport;
use App\Models\MainReportes;
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
        return [
            Select::make('reporte')
                ->options(MainReportes::all()->pluck('reporte','id'))
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
                ->visible(function (Get $get){
                    $reporte = $get('reporte');
                    if($reporte == 4) return true;
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
                ->visible(function (Get $get){
                    $reporte = $get('reporte');
                    if($reporte == 4) return true;
                    return false;
                }),
            Fieldset::make('Filtro de Periodo')
                ->schema([
                    Select::make('periodo_ini')->inlineLabel()
                        ->label('Periodo')->options(['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'])
                        ->default(Filament::getTenant()->periodo),
                ])->columns(2)
                ->visible(function (Get $get){
                    $reporte = $get('reporte');
                    if($reporte == 4||$reporte == null) return false;
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
                    })->action(function(Get $get){
                        $no_reporte = intval($get('reporte'));
                        $record = MainReportes::where('id',$get('reporte'))->first();
                        $reporte = $record->reporte;
                        $team_id = Filament::getTenant()->id;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = $get('periodo_ini') ?? null;
                        if($periodo == null) $periodo = Filament::getTenant()->periodo;
                        if($no_reporte != 4){
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

                        $ruta = public_path().'/TMPCFDI/'.$reporte.'_'.$team_id.'.pdf';
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
                            ->scale(0.6)->savePdf($ruta);
                        $this->ReportePDF = base64_encode(file_get_contents($ruta));
                        (new \App\Http\Controllers\ReportesController)->ContabilizaReporte(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, Filament::getTenant()->id);
                    }),
                Actions\Action::make('Exportar Excel')
                ->icon('fas-file-excel')->extraAttributes(['style'=>'width: 14rem'])
                ->action(function(Get $get){
                        $no_reporte = intval($get('reporte'));
                        $record = MainReportes::where('id',$get('reporte'))->first();
                        $reporte = $record->reporte;
                        $team_id = Filament::getTenant()->id;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = $get('periodo_ini') ?? null;
                        if($periodo == null) $periodo = Filament::getTenant()->periodo;
                        if($no_reporte != 4){
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

                        $ruta = public_path().'/TMPCFDI/'.$reporte.'_'.$team_id.'.pdf';
                        if(\File::exists($ruta)) unlink($ruta);
                        $logo = public_path().'/images/MainLogo.png';
                        //$logo_64 = 'data:image/png;base64,'.base64_encode(file_get_contents($logo));
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
