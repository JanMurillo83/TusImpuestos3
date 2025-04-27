<?php

namespace App\Filament\Pages;

use App\Http\Controllers\ReportesController;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ReportesConta extends Page
{
    protected static ?string $navigationIcon = 'fas-print';
    //protected static ?string $cluster = ContReportes::class;
    //protected static bool $isScopedToTenant = false;
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $title = 'Reportes';
    protected static ?string $pluralLabel = 'Reportes';
    protected static string $view = 'filament.pages.reportes-conta';
    protected function getActions(): array
    {
        return [
            Action::make('Balance_General')
            ->action(function (){
                (new \App\Http\Controllers\ReportesController)->actualiza_saldos(Filament::getTenant()->periodo,Filament::getTenant()->ejercicio);
                $this->getAction('Balance General')->visible(true);
                $this->replaceMountedAction('Balance General');
                $this->getAction('Balance General')->visible(false);
            }),
            Action::make('Balanza_General')
                ->label('Balanza de Comprobacion')
                ->action(function (){
                    (new \App\Http\Controllers\ReportesController)->actualiza_saldos(Filament::getTenant()->periodo,Filament::getTenant()->ejercicio);
                    $this->getAction('Balanza General')->visible(true);
                    $this->replaceMountedAction('Balanza General');
                    $this->getAction('Balanza General')->visible(false);
                }),
            Action::make('EdoRes')
                ->label('Estado de Resultados')
                ->action(function (){
                    (new \App\Http\Controllers\ReportesController)->actualiza_saldos(Filament::getTenant()->periodo,Filament::getTenant()->ejercicio);
                    $this->getAction('Estado de Resultados')->visible(true);
                    $this->replaceMountedAction('Estado de Resultados');
                    $this->getAction('Estado de Resultados')->visible(false);
                }),
            Html2MediaAction::make('Balance General')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Balance General')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                    view('BalanceGral',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false),
            Html2MediaAction::make('Balanza General')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Balanza de Comprobacion')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('BalanzaCompro',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false),
            Html2MediaAction::make('Estado de Resultados')
                ->preview()
                ->print(false)
                ->savePdf()
                ->filename('Estado de Resultados')
                ->margin([10, 10, 10, 10])
                ->content(fn()=>
                view('EdoRes',['empresa'=>Filament::getTenant()->id,'periodo'=>Filament::getTenant()->periodo,'ejercicio'=>Filament::getTenant()->ejercicio])
                )->visible(false)
        ];
    }
}
