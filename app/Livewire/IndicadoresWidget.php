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
use Illuminate\Support\Facades\Cache;
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

    public ?float $utilidad;
    public ?float $utilidad_a;
    public ?float $gastos, $gastos_a;
    public $color,$color_a;
    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;

        // FASE 1: Cachear cálculos de indicadores
        $cache_key = "indicadores_widget:{$team_id}:{$ejercicio}:{$periodo}";
        $datos = Cache::remember($cache_key, 300, function() use ($team_id) {
            $ventas_abonos = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first()->abonos ?? 0);
            $ventas_cargos = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first()->cargos ?? 0);
            $ventas_final = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40100000')->first()->final ?? 0);

            $cuentas = DB::select("SELECT * FROM saldos_reportes WHERE nivel = 1 AND team_id = $team_id AND (COALESCE(anterior,0)+COALESCE(cargos,0)+COALESCE(abonos,0)) != 0 ");

            return compact('ventas_abonos', 'ventas_cargos', 'ventas_final', 'cuentas');
        });

        $this->ventas_abonos = $datos['ventas_abonos'];
        $this->ventas_cargos = $datos['ventas_cargos'];
        $this->ventas_final = $datos['ventas_final'];
        $cuentas = $datos['cuentas'];
        $saldo_v = 0;
        $saldo_g = 0;
        $saldo_v_a = 0;
        $saldo_g_a = 0;
        $gastos = 0;
        $gastos_a = 0;
        foreach ($cuentas as $cuenta) {
            $cod = intval(substr($cuenta->codigo,0,3));
            if($cod > 399&&$cod < 500)
            {
                if($cuenta->naturaleza == 'D') {
                    $saldo_v += ($cuenta->cargos - $cuenta->abonos);
                    $saldo_v_a += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
                }else{
                    $saldo_v += ($cuenta->abonos - $cuenta->cargos);
                    $saldo_v_a += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
                }
            }
            if($cod > 500)
            {
                if($cuenta->naturaleza == 'D') {
                    $saldo_g += ($cuenta->cargos - $cuenta->abonos);
                    $saldo_g_a += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
                }else{
                    $saldo_g += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
                    $saldo_g_a += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
                }
            }
            if($cod > 599&&$cod < 699)
            {
                if($cuenta->naturaleza == 'D') {
                    $gastos += ($cuenta->cargos - $cuenta->abonos);
                    $gastos_a += $cuenta->anterior + ($cuenta->cargos - $cuenta->abonos);
                }else{
                    $gastos += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
                    $gastos_a += $cuenta->anterior + ($cuenta->abonos - $cuenta->cargos);
                }
            }
        }
        $this->gastos = floatval($gastos);
        $this->utilidad = floatval($saldo_v) - floatval($saldo_g);
        $this->utilidad_a = floatval($saldo_v_a) - floatval($saldo_g_a);
        $this->color_a = Color::Green;
        if($this->utilidad < 0) $this->color = Color::Red;
        if($this->utilidad_a < 0) $this->color_a = Color::Red;
    }
    protected function getStats(): array
    {

        return [
            Stat::make('Ventas del Mes', '$'.number_format(($this->ventas_abonos-$this->ventas_cargos),2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Gastos del Mes', '$'.number_format($this->gastos,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Utilidad del Periodo', '$'.number_format($this->utilidad,2))
                ->chartColor($this->color)->chart([1,2,3,4,5]),


        ];
    }
}
