<?php

namespace App\Livewire;

use App\Http\Controllers\ReportesController;
use App\Models\Auxiliares;
use App\Models\CuentasPagar;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class IndicadoresWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 1;
    }

    public ?float $ventas_abonos;
    public ?float $ventas_cargos;
    public ?float $ventas_final;
    public ?float $cuentas_pagar;
    public ?float $utilidad;
    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;
        $this->ventas_abonos = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first()->abonos ?? 0);
        $this->ventas_cargos = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first()->cargos ?? 0);
        $this->ventas_final = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first()->final ?? 0);
        $this->cuentas_pagar = floatval(CuentasPagar::where('team_id',$team_id)->sum('saldo') ?? 0);
        $cuentas = DB::select("SELECT * FROM saldos_reportes WHERE nivel = 1 AND team_id = $team_id AND (COALESCE(anterior,0)+COALESCE(cargos,0)+COALESCE(abonos,0)) != 0 ");
        $saldo_v = 0;
        $saldo_g = 0;
        foreach ($cuentas as $cuenta) {
            $cod = intval(substr($cuenta->codigo,0,3));
            if($cod > 399&&$cod < 500)
            {
                if($cuenta->naturaleza == 'D') {
                    $saldo_v += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
                }else{
                    $saldo_v += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
                }
            }
            if($cod > 500)
            {
                if($cuenta->naturaleza == 'D') {
                    $saldo_g += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
                }else{
                    $saldo_g += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
                }
            }
        }
        $this->utilidad = floatval($saldo_v) - floatval($saldo_g);
    }
    protected function getStats(): array
    {

        return [
            Stat::make('Ventas del Mes', '$'.number_format(($this->ventas_abonos-$this->ventas_cargos),2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Cuentas por Pagar', '$'.number_format($this->cuentas_pagar,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Utilidad del Ejercicio', '$'.number_format($this->utilidad,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),

        ];
    }
}
