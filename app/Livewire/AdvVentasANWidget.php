<?php

namespace App\Livewire;

use App\Http\Controllers\MainChartsController;
use App\Models\SaldosReportes;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;

class AdvVentasANWidget extends BaseWidget
{
    public function getColumns(): int
    {
        return 3;
    }
    protected function getStats(): array
    {
        $datos = SaldosReportes::where('team_id',Filament::getTenant()->id)
            ->where('codigo','40100000')->first();
        $importe = ($datos?->anterior ?? 0) + ($datos?->abonos ?? 0) - ($datos?->cargos ?? 0);
        $periodo = Filament::getTenant()->periodo;
        $ejercicio = Filament::getTenant()->ejercicio;
        $label = '$'.number_format($importe,2);
        $mes_letras = app(MainChartsController::class)->mes_letras($periodo);
        $leyenda = new HtmlString("<label style='color: whitesmoke !important;'>Ventas del Ejercicio</label>");
        $leyend =new HtmlString("<label style='color: whitesmoke !important;'>$label</label>");
        $periodo =new HtmlString("<label style='color: whitesmoke !important;'>Enero - $mes_letras $ejercicio</label>");
        return [
            Stat::make($leyenda, $leyend)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(69)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($periodo)
                ->descriptionIcon('fas-calendar', 'before')
                ->iconColor('success'),
            Stat::make($leyenda, $leyend)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(69)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($periodo)
                ->descriptionIcon('fas-calendar', 'before')
                ->iconColor('success'),
            Stat::make($leyenda, $leyend)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(69)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($periodo)
                ->descriptionIcon('fas-calendar', 'before')
                ->iconColor('success'),
            Stat::make($leyenda, $leyend)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(69)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($periodo)
                ->descriptionIcon('fas-calendar', 'before')
                ->iconColor('success')
        ];
    }
}
