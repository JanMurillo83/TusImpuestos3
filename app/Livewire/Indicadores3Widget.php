<?php

namespace App\Livewire;

use App\Models\Admincuentaspagar;
use App\Models\CuentasCobrar;
use App\Models\CuentasPagar;
use App\Models\Inventario;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Indicadores3Widget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 1;
    }
    public ?float $anterior_cxc;
    public ?float $cargos_cxc;
    public ?float $abonos_cxc;
    public ?float $saldo_cxc;
    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;

        // FASE 1: Cachear cálculos de indicadores3 (CxC)
        $cache_key = "indicadores3_widget:{$team_id}:{$ejercicio}:{$periodo}";
        $datos = Cache::remember($cache_key, 300, function() use ($team_id) {
            $ctas_cxc = SaldosReportes::where('team_id',$team_id)->where('codigo','10500000')->first();
            $inicia_cxc = floatval($ctas_cxc->anterior ?? 0);
            $cargos_cxc = floatval($ctas_cxc->cargos ?? 0);
            $abonos_cxc = floatval($ctas_cxc->abonos ?? 0);

            return compact('inicia_cxc', 'cargos_cxc', 'abonos_cxc');
        });

        $this->anterior_cxc = $datos['inicia_cxc'];
        $this->cargos_cxc = $datos['cargos_cxc'];
        $this->abonos_cxc = $datos['abonos_cxc'];
        $this->saldo_cxc = $datos['inicia_cxc'] + $datos['cargos_cxc'] - $datos['abonos_cxc'];
        //--------------------------------------------------------------------------------------------

    }
    protected function getStats(): array
    {
        return [
            Stat::make('Saldo Anterior Clientes', '$'.number_format($this->anterior_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cargos a Clientes', '$'.number_format($this->cargos_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Pagos de Clientes', '$'.number_format($this->abonos_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cuentas por Cobrar', '$'.number_format(($this->saldo_cxc),2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),

        ];
    }
}
