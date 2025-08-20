<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EstadisticasGrales;
use App\Livewire\GraficasWidget;
use App\Livewire\IndicadoresWidget;
use Filament\Pages\Dashboard as DefaultDashboard;

class DashBoard extends DefaultDashboard
{
    protected static ?string $navigationIcon = 'fas-home';
    protected static ?string $title = 'Datos del Periodo';
    protected static string $view = 'filament.pages.dash-board';

    protected ?string $maxContentWidth = 'full';

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
