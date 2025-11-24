<?php

namespace App\Livewire;

use App\Models\Admincuentascobrar;
use App\Models\Admincuentaspagar;
use App\Models\CuentasCobrar;
use App\Models\CuentasPagar;
use App\Models\Facturas;
use App\Models\SaldosReportes;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class Indicadores4Widget extends BaseWidget
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
        $ctas_cxc = SaldosReportes::where('team_id',$team_id)->where('codigo','20100000')->first();
        $inicia_cxc = floatval($ctas_cxc->anterior ?? 0);
        $cargos_cxc = floatval($ctas_cxc->cargos ?? 0);
        $abonos_cxc = floatval($ctas_cxc->abonos ?? 0);
        $this->anterior_cxc = $inicia_cxc;
        $this->cargos_cxc = $abonos_cxc;
        $this->abonos_cxc = $cargos_cxc;
        $this->saldo_cxc = $inicia_cxc - $cargos_cxc + $abonos_cxc;
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Saldo Anterior Proveedores', '$'.number_format($this->anterior_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cargos de Proveedores', '$'.number_format($this->cargos_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Pagos de Proveedores', '$'.number_format($this->abonos_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cuentas por Pagar', '$'.number_format(($this->saldo_cxc),2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
        ];
    }
}
