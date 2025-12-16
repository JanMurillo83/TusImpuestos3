<?php

namespace App\Filament\Pages;

use App\Models\MainReportes;
use Filament\Facades\Filament;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;

class AdminReporteConta extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'fas-print';
    protected static bool $isScopedToTenant = false;
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Reportes Contables';
    protected static ?string $pluralLabel = 'Reportes Contables';
    protected static string $view = 'filament.pages.admin-reporte-conta';

    public ?string $ReportePDF;
    public function mount():void
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
    }
    public function table(Table $table):Table
    {
        return $table
            ->query(MainReportes::query())
            ->modifyQueryUsing(fn ($query) => $query->where('tipo','=','contable'))
            ->paginated(false)
            ->columns([
                TextColumn::make('reporte')->label('Reporte'),
                IconColumn::make('formato')->label('Formato')
                    ->icon(fn (string $state): string => match ($state) {
                        'pdf' => 'fas-file-pdf',
                        'xls' => 'fas-file-excel'
                    })
            ])->actions([
                Action::make('Generar')
                    ->icon('fas-print')
                    ->color('danger')
                    ->iconButton()
                    ->form(function (Form $form,$record){
                        $reporte = $record->reporte;
                        return $form->schema([
                            Fieldset::make('Cuentas Contables')
                            ->schema([
                                Select::make('cuenta_ini')
                                    ->label('Cuenta Inicial')
                                    ->options(
                                        DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                            ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo','asc')->pluck('mostrar','codigo')
                                    )
                                    ->searchable(),
                                Select::make('cuenta_fin')
                                    ->label('Cuenta Final')
                                    ->options(
                                        DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                            ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo','asc')->pluck('mostrar','codigo')
                                    )
                                    ->searchable(),
                            ])->columns(1)
                            ->visible(function () use ($reporte){
                                if($reporte == 'Reporte de Auxiliares') return true;
                                return false;
                            }),
                            Fieldset::make('Filtro de Periodo')
                            ->schema([
                                Select::make('periodo_ini')
                                    ->label('Periodo Inicial')->options(['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'])
                                    ->default(Filament::getTenant()->periodo),
                                Select::make('periodo_fin')
                                    ->label('Periodo Final')->options(['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'])
                                    ->default(Filament::getTenant()->periodo),
                            ])->columns(1)
                            ->visible(function ()use ($reporte){
                                if($reporte == 'Reporte de Auxiliares') return true;
                                return false;
                            }),
                            Fieldset::make('Periodo')
                            ->schema([
                                Select::make('periodo')
                                    ->label('Periodo')->options(['1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'])
                                    ->default(Filament::getTenant()->periodo),
                            ])->columns(1)
                            ->visible(function ()use ($reporte){
                                if($reporte == 'Reporte de Auxiliares') return false;
                                return true;
                            })
                        ]);
                    })
                    ->modalHeading('Generar Reporte')
                    ->modalWidth('sm')
                    ->modalSubmitActionLabel('Generar')
                    ->action(function ($record,$data){
                        $team_id = Filament::getTenant()->id;
                        $periodo = $data['periodo'] ?? null;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $cuentaIni = $data['cuenta_ini'] ?? null;
                        $cuentaFin = $data['cuenta_fin'] ?? null;
                        $fechaIni = $data['periodo_ini'] ?? null;
                        $fechaFin = $data['periodo_fin'] ?? null;
                        $path = $record->ruta;
                        $tipo = $record->formato;
                        $reporte = $record->reporte;
                        $ruta = public_path().'/TMPCFDI/reporte'.$team_id.'_'.$ejercicio.'_'.$periodo.'_'.$reporte.'.pdf';
                        if(\File::exists($ruta)) unlink($ruta);
                        $data = [
                            'empresa'=>$team_id,
                            'periodo'=>$periodo,
                            'ejercicio'=>$ejercicio,
                            'cuenta_ini'=>$cuentaIni,
                            'cuenta_fin'=>$cuentaFin,
                            'mes_ini'=>$fechaIni,
                            'mes_fin'=>$fechaFin];
                        if($tipo == 'pdf') {
                            $html = \Illuminate\Support\Facades\View::make($path, $data)->render();
                            Browsershot::html($html)->format('Letter')
                                ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                                ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                                ->noSandbox()
                                ->scale(0.8)->savePdf($ruta);
                            $this->ReportePDF = base64_encode(file_get_contents($ruta));
                            $this->getAction('Generar Reporte')->visible(true);
                            $this->replaceMountedAction('Generar Reporte');
                            $this->getAction('Generar Reporte')->visible(false);
                        }
                    })
            ]);
    }

    public function getActions() : array
    {
        return [
            \Filament\Actions\Action::make('Generar Reporte')
                ->modalWidth('full')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
            ->form(function (Form $form){
                return $form
                    ->schema([
                        View::make('ReportesAdmin.your-pdf-viewer')
                            ->columnSpanFull()
                            ->viewData(function () {
                                return ['pdfBase64' => $this->ReportePDF ?? 'No se ha cargado'] ;
                        })
                    ]);
            })->visible(false)
        ];
    }

}
