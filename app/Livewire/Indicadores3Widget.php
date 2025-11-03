<?php

namespace App\Livewire;

use App\Models\CuentasCobrar;
use App\Models\CuentasPagar;
use App\Models\Inventario;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class Indicadores3Widget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 1;
    }
    public ?float $saldo_cxc;
    public ?float $inve_exis;
    public ?float $inve_cost;
    public ?float $utilidad;
    public ?float $cuentas_pagar;

    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;
        $this->saldo_cxc = floatval(CuentasCobrar::where('team_id',$team_id)->sum('saldo') ?? 0);
        $this->inve_exis = floatval(Inventario::where('team_id',$team_id)->sum('exist') ?? 0);
        $this->inve_cost = floatval(Inventario::where('team_id',$team_id)->sum('u_costo') ?? 0);
        $this->cuentas_pagar = floatval(CuentasPagar::where('team_id',$team_id)->sum('saldo') ?? 0);
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Cuentas por Cobrar', '$'.number_format($this->saldo_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cuentas por Pagar', '$'.number_format($this->cuentas_pagar,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Inventario', '$'.number_format(($this->inve_exis*$this->inve_cost),2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),

        ];
    }
}
