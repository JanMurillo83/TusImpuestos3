<?php

namespace App\Livewire;

use App\Http\Controllers\MainChartsController;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MainVentasWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $datos = SaldosReportes::where('team_id',Filament::getTenant()->id)
        ->where('codigo','40100000')->first();
        $importe = ($datos?->abonos ?? 0) - ($datos?->cargos ?? 0);
        $periodo = Filament::getTenant()->periodo;
        $ejercicio = Filament::getTenant()->ejercicio;
        $label = '$'.number_format($importe,2);
        $leyenda = 'Ventas del Periodo '.$periodo.'/'.$ejercicio;
        return [
            Stat::make('Ventas del Periodo', $label)
                ->description($leyenda)
                ->descriptionIcon('fas-coins')
                ->color('success'),
        ];
    }
}
