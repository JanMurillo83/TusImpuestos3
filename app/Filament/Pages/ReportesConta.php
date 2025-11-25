<?php

namespace App\Filament\Pages;

use App\Exports\AuxiliaresExport;
use App\Exports\BalanceExport;
use App\Exports\BalanzaExport;
use App\Exports\EdoreExport;
use App\Http\Controllers\RepContables;
use App\Http\Controllers\ReportesController;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ReportesConta extends Page implements HasForms
{
    Use InteractsWithForms;
    protected static ?string $navigationIcon = 'fas-print';
    //protected static ?string $cluster = ContReportes::class;
    //protected static bool $isScopedToTenant = false;
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Reportes';
    protected static ?string $pluralLabel = 'Reportes';
    protected static string $view = 'filament.pages.reportes-conta';

    public ?string $cuenta_ini_g;
    public ?string $cuenta_fin_g;
    public function form(Form $form): Form{
        return $form
            ->schema([
                Fieldset::make('Reportes PDF')
                ->schema([
                Actions::make([
                    Actions\Action::make('Balance_General')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('Balance General')->visible(true);
                            $this->replaceMountedAction('Balance General');
                            $this->getAction('Balance General')->visible(false);
                        }),
                    Actions\Action::make('Balanza_General')
                        ->label('Balanza de Comprobacion')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('Balanza General')->visible(true);
                            $this->replaceMountedAction('Balanza General');
                            $this->getAction('Balanza General')->visible(false);
                        }),
                    Actions\Action::make('Balanza_Nueva')
                        ->label('Balanza de Comprobación (Nuevo)')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('Balanza Nueva')->visible(true);
                            $this->replaceMountedAction('Balanza Nueva');
                            $this->getAction('Balanza Nueva')->visible(false);
                        })->visible(false),
                    Actions\Action::make('EdoRes')
                        ->label('Estado de Resultados')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('Estado de Resultados')->visible(true);
                            $this->replaceMountedAction('Estado de Resultados');
                            $this->getAction('Estado de Resultados')->visible(false);
                        }),
                    Actions\Action::make('Auxiliares_Periodo')
                        ->label('Auxiliares del Periodo (VP)')
                        ->form([
                            Select::make('cuenta_ini')
                                ->label('Cuenta Inicial')
                                ->options(
                                    DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                        ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                                )
                                ->searchable(),
                            Select::make('cuenta_fin')
                                ->label('Cuenta Final')
                                ->options(
                                    DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                        ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                                )
                                ->searchable()
                        ])
                        ->action(function ($data){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            $this->cuenta_ini_g = $data['cuenta_ini'] ?? null;
                            $this->cuenta_fin_g = $data['cuenta_fin'] ?? null;
                            (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('Auxiliares del Periodo')->visible(true);
                            $this->replaceMountedAction('Auxiliares del Periodo');
                            $this->getAction('Auxiliares del Periodo')->visible(false);
                        }),
                    Actions\Action::make('Auxiliares_Periodo_des')
                        ->label('Auxiliares del Periodo (Descarga)')
                        ->form([
                            Select::make('cuenta_ini')
                                ->label('Cuenta Inicial')
                                ->options(
                                    DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                        ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                                )
                                ->searchable(),
                            Select::make('cuenta_fin')
                                ->label('Cuenta Final')
                                ->options(
                                    DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                        ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                                )
                                ->searchable()
                        ])
                        ->action(function ($data){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            $state = $this->form->getState();
                            $cuentaIni = $data['cuenta_ini'] ?? null;
                            $cuentaFin = $data['cuenta_fin'] ?? null;
                            (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $archivo = 'Auxiliar-'.$periodo.'-'.$ejercicio.'.pdf';
                            $datos = ['empresa'=>$team_id,'periodo'=>$periodo,'ejercicio'=>$ejercicio,'cuenta_ini'=>$cuentaIni,'cuenta_fin'=>$cuentaFin];
                            $pdf = Pdf::loadView('AuxiliaresPeriodo', $datos);
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->stream();
                            }, $archivo);
                        }),
                    Actions\Action::make('Polizas_Descuadradas')
                        ->label('Pólizas Descuadradas')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            (new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('Polizas Descuadradas')->visible(true);
                            $this->replaceMountedAction('Polizas Descuadradas');
                            $this->getAction('Polizas Descuadradas')->visible(false);
                        }),
                    Actions\Action::make('DIOT General')
                    ->label('DIOT General')
                    ->action(function (){
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = Filament::getTenant()->periodo;
                        $team_id = Filament::getTenant()->id;
                        //(new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                        $this->getAction('DIOT G')->visible(true);
                        $this->replaceMountedAction('DIOT G');
                        $this->getAction('DIOT G')->visible(false);
                    }),
                    Actions\Action::make('DIOT Detalle')
                        ->label('DIOT Detalle')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            //(new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('DIOT D')->visible(true);
                            $this->replaceMountedAction('DIOT D');
                            $this->getAction('DIOT D')->visible(false);
                        }),
                    Actions\Action::make('Afectaciones')
                        ->label('Afectaciones IVA IETU')
                        ->action(function (){
                            $ejercicio = Filament::getTenant()->ejercicio;
                            $periodo = Filament::getTenant()->periodo;
                            $team_id = Filament::getTenant()->id;
                            //(new \App\Http\Controllers\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $team_id);
                            $this->getAction('AfectaIVAIETU')->visible(true);
                            $this->replaceMountedAction('AfectaIVAIETU');
                            $this->getAction('AfectaIVAIETU')->visible(false);
                        })
                ])
                ]),
                Fieldset::make('Reportes Excel')
                ->schema([
                    Actions::make([
                        Actions\Action::make('Balance_General_xls')
                        ->label('Balance General')
                        ->action(function (){
                            $empresa = Filament::getTenant()->id;
                            $periodo = Filament::getTenant()->periodo;
                            $ejercicio = Filament::getTenant()->ejercicio;
                            return (new BalanceExport($empresa,$periodo,$ejercicio))->download('BalanceGeneral.xlsx');
                        }),
                        Actions\Action::make('Balanza_General_xls')
                            ->label('Balanza de Comprobación')
                            ->action(function (){
                                $empresa = Filament::getTenant()->id;
                                $periodo = Filament::getTenant()->periodo;
                                $ejercicio = Filament::getTenant()->ejercicio;
                                return (new BalanzaExport($empresa,$periodo,$ejercicio))->download('BalanzaComprobacion.xlsx');
                            }),
                        Actions\Action::make('EstadoResultados_xls')
                            ->label('Estado de Resultados')
                            ->action(function (){
                                $empresa = Filament::getTenant()->id;
                                $periodo = Filament::getTenant()->periodo;
                                $ejercicio = Filament::getTenant()->ejercicio;
                                return (new EdoreExport($empresa,$periodo,$ejercicio))->download('EstadoResultados.xlsx');
                            }),
                        Actions\Action::make('Auxiliares_xls')
                            ->label('Auxiliares del Periodo')
                            ->form([
                                Select::make('cuenta_ini')
                                    ->label('Cuenta Inicial')
                                    ->options(
                                        DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                            ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->orderBy('codigo')->pluck('mostrar','codigo')
                                    )
                                    ->searchable(),
                                Select::make('cuenta_fin')
                                    ->label('Cuenta Final')
                                    ->options(
                                        DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                            ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->orderBy('codigo')->pluck('mostrar','codigo')
                                    )
                                    ->searchable()
                            ])
                            ->action(function (){
                                $empresa = Filament::getTenant()->id;
                                $periodo = Filament::getTenant()->periodo;
                                $ejercicio = Filament::getTenant()->ejercicio;
                                $state = $this->form->getState();
                                $cuentaIni = $state['cuenta_ini'] ?? null;
                                $cuentaFin = $state['cuenta_fin'] ?? null;
                                return (new AuxiliaresExport($empresa,$periodo,$ejercicio,$cuentaIni,$cuentaFin))->download('Auxiliares.xlsx');
                            })
                    ])
                ])
            ]);
    }
    protected function getActions(): array
    {
        return [
            Html2MediaAction::make('Balance General')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Balance General')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                    view('BGralNew',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('Balanza General')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Balanza de Comprobacion')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('AGralNew',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('Balanza Nueva')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Balanza de Comprobacion (Nuevo)')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('BalanzaNew',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('Estado de Resultados')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Estado de Resultados')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('EdoreNew',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('Auxiliares del Periodo')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Auxiliares del Periodo')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('AuxiliaresPeriodo',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio,'cuenta_ini'=>$this->cuenta_ini_g,'cuenta_fin'=>$this->cuenta_fin_g])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('Polizas Descuadradas')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Polizas Descuadradas')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('PolizasDescuadradas',[ 'empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('DIOT G')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Diot General')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('ReporteDiotGeneral',[ 'id_empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('DIOT D')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Diot Detalle')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('ReporteDiotDetalle',[ 'id_empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl'),
            Html2MediaAction::make('AfectaIVAIETU')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Análisis de Afectaciones de IVA y de IETU')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('ReporteAfectaciones',[ 'id_empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl')
        ];
    }
}
