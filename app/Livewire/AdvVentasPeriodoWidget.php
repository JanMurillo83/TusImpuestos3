<?php

namespace App\Livewire;

use App\Http\Controllers\MainChartsController;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;

class AdvVentasPeriodoWidget extends BaseWidget
{
    public function getColumns(): int
    {
        return 3;
    }
    protected function getStats(): array
    {
        $team_id = Filament::getTenant()->id;
        $periodo = Filament::getTenant()->periodo;
        $periodo_ant = Filament::getTenant()->periodo - 1;
        $ejercicio = Filament::getTenant()->ejercicio;
        $mes_letras = app(MainChartsController::class)->mes_letras($periodo);
        $mes_letras_ant = app(MainChartsController::class)->mes_letras($periodo_ant);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $importe1 = floatval(app(MainChartsController::class)->GeneraAbonos($team_id,'40100000',$periodo,$ejercicio));
        $importe_mes = '$'.number_format(app(MainChartsController::class)->GeneraAbonos($team_id,'40100000',$periodo,$ejercicio),2);
        $label_mes = new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important;'>Ventas $mes_letras</label>");
        $leyenda_mes =new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important; font-style: italic'>$importe_mes</label>");
        //------------------------------------------------------------------------------------------------------------------------------------------
        $importe2 = floatval(app(MainChartsController::class)->GeneraAbonos($team_id,'40100000',$periodo_ant,$ejercicio));
        $importe_mes_ant = '$'.number_format(app(MainChartsController::class)->GeneraAbonos($team_id,'40100000',$periodo_ant,$ejercicio),2);
        $label_mes_ant = new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important;'>Ventas $mes_letras_ant</label>");
        $leyenda_mes_ant =new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important; font-style: italic'>$importe_mes_ant</label>");
        //------------------------------------------------------------------------------------------------------------------------------------------
        $importe_dife = '$'.number_format($importe1-$importe2,2);
        $label_dife = new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important;'>Diferencia Vs. Periodo anterior</label>");
        $leyenda_dife =new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important; font-style: italic'>$importe_dife</label>");
        //------------------------------------------------------------------------------------------------------------------------------------------
        $color = 'success';
        if($importe1-$importe2 < 0) $color = 'danger';
        return [
            Stat::make($label_mes, $leyenda_mes)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(50)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->iconColor('success'),
            Stat::make($label_mes_ant, $leyenda_mes_ant)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(50)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->iconColor('success'),
            Stat::make($label_dife, $leyenda_dife)
                ->icon('fas-dollar-sign')
                ->backgroundColor($color)
                ->progress(50)
                ->progressBarColor('primary')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->iconColor('success'),
        ];
    }
}
