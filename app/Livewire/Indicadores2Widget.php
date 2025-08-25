<?php

namespace App\Livewire;

use App\Http\Controllers\ReportesController;
use App\Models\Auxiliares;
use App\Models\CuentasPagar;
use App\Models\SaldosReportes;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Indicadores2Widget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 1;
    }

    public ?float $ventas_abonos;
    public ?float $ventas_cargos;
    public ?float $ventas_final;
    public ?float $saldo_cxp;
    public ?float $impuesto;
    public ?string $impuesto_lab;
    public $impuesto_color;
    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;
        $this->ventas_final = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first()->final ?? 0);
        $this->saldo_cxp = floatval(CuentasPagar::where('team_id',$team_id)->where('vencimiento','<',Carbon::now())->sum('saldo') ?? 0);
        $imp_favor = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','11801000')->first()->final ?? 0);
        $imp_contra = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','20801000')->first()->final ?? 0);
        $impuesto = $imp_favor - $imp_contra;
        if($impuesto < 0) {
            $this->impuesto = $impuesto * -1;
            $this->impuesto_lab = 'Impuesto a Pagar';
            $this->impuesto_color = Color::Red;
        }else{
            $this->impuesto = $impuesto;
            $this->impuesto_lab = 'Impuesto a Favor';
            $this->impuesto_color = Color::Green;
        }
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Ventas del Año', '$'.number_format($this->ventas_final,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cuentas por pagar Vencidas', '$'.number_format($this->saldo_cxp,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make($this->impuesto_lab, '$'.number_format($this->impuesto,2))
                ->chartColor($this->impuesto_color)->chart([1,2,3,4,5]),
        ];
    }
}
