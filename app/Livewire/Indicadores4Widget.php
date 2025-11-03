<?php

namespace App\Livewire;

use App\Models\CuentasCobrar;
use App\Models\CuentasPagar;
use App\Models\Facturas;
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
    public ?float $saldo_cxc;
    public ?float $saldo_cxp;
    public ?float $utilidad;
    public $color;
    public ?float $facturacion;
    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $this->saldo_cxc = floatval(CuentasCobrar::where('team_id',$team_id)->where('vencimiento','<',Carbon::now())->sum('saldo') ?? 0);
        $this->saldo_cxp = floatval(CuentasPagar::where('team_id',$team_id)->where('vencimiento','<',Carbon::now())->sum('saldo') ?? 0);
        $this->facturacion = floatval(Facturas::select(DB::raw("SUM(total*tcambio) Importe"))
            ->where(DB::raw("EXTRACT(MONTH FROM fecha)"),$periodo)
            ->where(DB::raw("EXTRACT(YEAR FROM fecha)"),$ejercicio)
            ->where('team_id',$team_id)->first()->Importe ?? 0);


    }
    protected function getStats(): array
    {
        return [
            Stat::make('Cartera Vencida', '$'.number_format($this->saldo_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cuentas por pagar Vencidas', '$'.number_format($this->saldo_cxp,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Facturación del Periodo', '$'.number_format($this->facturacion,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),

        ];
    }
}
