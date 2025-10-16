<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EstadisticasGrales;
use App\Http\Controllers\ReportesController;
use App\Livewire\GraficasWidget;
use App\Livewire\IndicadoresWidget;
use App\Models\Auxiliares;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Pages\Dashboard as DefaultDashboard;
use Livewire\Form;

class DashBoard extends DefaultDashboard
{
    protected static ?string $navigationIcon = 'fas-home';
    protected static ?string $title = 'Datos Periodo';
    protected static string $view = 'filament.pages.dash-board';

    protected ?string $maxContentWidth = 'full';
    public function mount(): void
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        $aux =Auxiliares::where('team_id',Filament::getTenant()->id)->where('a_ejercicio',$ejercicio)->where('a_periodo',$periodo)->get();
        if(count($aux)>0) (new ReportesController())->ContabilizaReporte($ejercicio, $periodo, $team_id);
    }

    public function form(\Filament\Forms\Form $form) :\Filament\Forms\Form
    {
        return $form
            ->schema([
                Actions::make([
                    Actions\Action::make('Actualizar')
                    ->action(function (){
                        $ejercicio = Filament::getTenant()->ejercicio;
                        $periodo = Filament::getTenant()->periodo;
                        $team_id = Filament::getTenant()->id;
                        $aux =Auxiliares::where('team_id',Filament::getTenant()->id)->where('a_ejercicio',$ejercicio)->where('a_periodo',$periodo)->get();
                        if(count($aux)>0) (new ReportesController())->ContabilizaReporte($ejercicio, $periodo, $team_id);
                    })
                ])
            ]);
    }
    /*public function getHeaderWidgetsColumns(): int | string | array
    {
        return 10;
    }

    public function getHeaderWidgets(): array
    {
        return [
            GraficasWidget::class,
            IndicadoresWidget::class,
            EstadisticasGrales::class,

        ];
    }*/
}
