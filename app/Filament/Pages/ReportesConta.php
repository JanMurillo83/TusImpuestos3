<?php

namespace App\Filament\Pages;

use App\Exports\BalanceExport;
use App\Exports\BalanzaExport;
use App\Exports\EdoreExport;
use App\Http\Controllers\ReportesController;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
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

    public function form(Form $form): Form{
        return $form
            ->schema([
                Fieldset::make('Reportes PDF')
                ->schema([
                Actions::make([
                  Actions\Action::make('Balance_General')
                      ->action(function (){
                          (new \App\Http\Controllers\ReportesController)->actualiza_saldos(Filament::getTenant()->periodo,Filament::getTenant()->ejercicio);
                          $this->getAction('Balance General')->visible(true);
                          $this->replaceMountedAction('Balance General');
                          $this->getAction('Balance General')->visible(false);
                      }),
                    Actions\Action::make('Balanza_General')
                        ->label('Balanza de Comprobacion')
                        ->action(function (){
                            (new \App\Http\Controllers\ReportesController)->actualiza_saldos(Filament::getTenant()->periodo,Filament::getTenant()->ejercicio);
                            $this->getAction('Balanza General')->visible(true);
                            $this->replaceMountedAction('Balanza General');
                            $this->getAction('Balanza General')->visible(false);
                        }),
                    Actions\Action::make('EdoRes')
                        ->label('Estado de Resultados')
                        ->action(function (){
                            (new \App\Http\Controllers\ReportesController)->actualiza_saldos(Filament::getTenant()->periodo,Filament::getTenant()->ejercicio);
                            $this->getAction('Estado de Resultados')->visible(true);
                            $this->replaceMountedAction('Estado de Resultados');
                            $this->getAction('Estado de Resultados')->visible(false);
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
            Html2MediaAction::make('Estado de Resultados')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Estado de Resultados')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('EdoreNew',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
                ->modalWidth('7xl')
        ];
    }
}
