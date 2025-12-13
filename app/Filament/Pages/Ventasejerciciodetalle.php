<?php

namespace App\Filament\Pages;

use App\Http\Controllers\MainChartsController;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class Ventasejerciciodetalle extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.ventasejerciciodetalle';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Ventas Acumuladas por Ejercicio';

    public $anterior_mes = [];
    public $este_mes = [];
    public function mount(): void
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $periodo_ant = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        $anterior_mes = app(MainChartsController::class)->GeneraAbonos_Aux($team_id,'40100000',$periodo_ant,$ejercicio);
        $este_mes = app(MainChartsController::class)->GeneraAbonos_Aux($team_id,'40100000',$periodo,$ejercicio);
        $this->anterior_mes = $anterior_mes;
    }
}
