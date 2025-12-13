<?php

namespace App\Livewire;

use App\Http\Controllers\MainChartsController;
use App\Models\SaldosReportes;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;
use JetBrains\PhpStorm\NoReturn;

class AdvVentasWidget extends BaseWidget implements HasActions,HasForms
{
    use InteractsWithActions,InteractsWithForms;
    public function redirigeAction():Action
    {
        return Action::make('Redirige')
            ->url('/dashboard');
    }

    public function getColumns(): int
    {
        return 4;
    }
    protected function getStats(): array
    {
        $team_id = Filament::getTenant()->id;
        $datos = SaldosReportes::where('team_id',Filament::getTenant()->id)
            ->where('codigo','40100000')->first();
        $importe = ($datos?->abonos ?? 0) - ($datos?->cargos ?? 0);
        $importe_a = ($datos?->anterior ?? 0) + ($datos?->abonos ?? 0) - ($datos?->cargos ?? 0);
        $periodo = Filament::getTenant()->periodo;
        $ejercicio = Filament::getTenant()->ejercicio;
        $label = '$'.number_format($importe,2);
        $label_a = '$'.number_format($importe_a,2);
        $mes_letras = app(MainChartsController::class)->mes_letras($periodo);
        $leyenda = new HtmlString("<label style='color: whitesmoke !important;font-weight: bold !important;'>Ventas del Periodo</label>");
        $leyend =new HtmlString("<label style='color: whitesmoke !important;font-weight: bold !important; font-style: italic'>$label</label>");
        //------------------------------------------------------------------------------------------------------------------------------------------
        $but1 = new HtmlString(\Blade::render(<<<BLADE
            <x-filament::link :href="url('/$team_id/ventasperiododetalle')" color="info" icon="fas-circle-info">
                Ver Detalles $mes_letras - $ejercicio
            </x-filament::link>
        BLADE
         ));
        //------------------------------------------------------------------------------------------------------------------------------------------
        $leyenda_a = new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important;'>Ventas del Ejercicio</label>");
        $leyend_a =new HtmlString("<label style='color: whitesmoke !important; font-weight: bold !important; font-style: italic'>$label_a</label>");
        $but2 = new HtmlString(\Blade::render(<<<BLADE
            <x-filament::link :href="url('/$team_id/ventasejerciciodetalle')" color="info" icon="fas-circle-info">
                Ver Detalles Enero - $mes_letras $ejercicio
            </x-filament::link>
        BLADE
        ));
        //------------------------------------------------------------------------------------------------------------------------------------------
        return [
            Stat::make($leyenda, $leyend)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(50)
                ->progressBarColor('success')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($but1)
                ->iconColor('success'),
            Stat::make($leyenda_a, $leyend_a)
                ->icon('fas-dollar-sign')
                ->backgroundColor('primary')
                ->progress(100)
                ->progressBarColor('warning')
                ->iconBackgroundColor('success')
                ->chartColor('success')
                ->iconPosition('start')
                ->description($but2)
                ->iconColor('success'),
        ];
    }

    public function Utilidad_Periodo()
    {
        $Dventas = SaldosReportes::where('team_id',Filament::getTenant()->id)->where('codigo','40100000')->first();
        $Ventas = ($Dventas?->anterior ?? 0) + ($Dventas?->abonos ?? 0) - ($Dventas?->cargos ?? 0);
        $DCostos = SaldosReportes::where('team_id',Filament::getTenant()->id)->where('codigo','10100000')->first();
        $Costos = ($DCostos?->anterior ?? 0) + ($DCostos?->abonos ?? 0) - ($DCostos?->cargos ?? 0);
        $Utilidad = $Ventas - $Costos;
        return $Utilidad;
    }
    #[NoReturn]
    public function setStatusFilter($filter) :void
    {
        dd($filter);
    }

}
