<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EstadisticasGrales;
use App\Http\Controllers\ReportesController;
use App\Livewire\GraficasWidget;
use App\Livewire\Indicadores2Widget;
use App\Livewire\Indicadores3Widget;
use App\Livewire\Indicadores4Widget;
use App\Livewire\IndicadoresWidget;
use App\Models\Auxiliares;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Split;
use Filament\Pages\Dashboard as DefaultDashboard;
use Livewire\Form;

class DashBoard extends DefaultDashboard
{
    protected static ?string $navigationIcon = 'fas-home';
    protected static ?string $title = 'Inicio';
    protected static string $view = 'filament.pages.dash-board';
}
