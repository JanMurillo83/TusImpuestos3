<?php

namespace App\Livewire;

use App\Http\Controllers\MainChartsController;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;

class AdvSegundaFilaWidget extends BaseWidget
{
    public function getColumns(): int
    {
        return 3;
    }
    protected function getStats(): array
    {
        $team_id = Filament::getTenant()->id;
        $periodo = Filament::getTenant()->periodo;
        $ejercicio = Filament::getTenant()->ejercicio;
        $mes_letras = app(MainChartsController::class)->mes_letras($periodo);
        //------------------------------------------------------------------------------------------------------------------------------------------
        $cobrar_importe = '$'.number_format(app(MainChartsController::class)->GetCobrar($team_id),2);
        $cobrar = new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important;'>Cuentas por Cobrar</label>");
        $ley_cobrar =new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important; font-style: italic'>$cobrar_importe</label>");
        $bot_cobrar = new HtmlString(\Blade::render(<<<BLADE
            <x-filament::link :href="url('/$team_id/ventasejerciciodetalle')" color="info" icon="fas-circle-info">
                Ver Detalle Enero - $mes_letras $ejercicio
            </x-filament::link>
        BLADE
        ));
        //------------------------------------------------------------------------------------------------------------------------------------------
        $pagar_importe = '$'.number_format(app(MainChartsController::class)->GetPagar($team_id),2);
        $pagar = new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important;'>Cuentas por Pagar</label>");
        $ley_pagar =new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important; font-style: italic'>$pagar_importe</label>");
        $bot_pagar = new HtmlString(\Blade::render(<<<BLADE
            <x-filament::link :href="url('/$team_id/ventasejerciciodetalle')" color="info" icon="fas-circle-info">
                Ver Detalle Enero - $mes_letras $ejercicio
            </x-filament::link>
        BLADE
        ));
        //------------------------------------------------------------------------------------------------------------------------------------------
        $impuesto_importe_an = '$'.number_format(app(MainChartsController::class)->GetUtiPerEjer($team_id) * 0.30,2);
        $impuesto_an = new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important;'>Impuesto Anual Estimado (30%)</label>");
        $ley_impuesto_an =new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important; font-style: italic'>$impuesto_importe_an</label>");
        $bot_impuesto_an = new HtmlString(\Blade::render(<<<BLADE
            <x-filament::link :href="url('/$team_id/ventasejerciciodetalle')" color="info" icon="fas-circle-info">
                Ver Detalle de Impuestos
            </x-filament::link>
        BLADE
        ));
        //------------------------------------------------------------------------------------------------------------------------------------------

        return [
            Stat::make($cobrar, $ley_cobrar)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(50)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($bot_cobrar)
                ->iconColor('success'),
            Stat::make($pagar, $ley_pagar)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(50)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($bot_pagar)
                ->iconColor('success'),
            Stat::make($impuesto_an, $ley_impuesto_an)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(50)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($bot_impuesto_an)
                ->iconColor('success'),
        ];
    }
}
