<?php

namespace App\Livewire;

use App\Models\CuentasCobrar;
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
    public ?float $utilidad;
    public $color;

    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;
        $this->saldo_cxc = floatval(CuentasCobrar::where('team_id',$team_id)->where('vencimiento','<',Carbon::now())->sum('saldo') ?? 0);
        $cuentas = DB::select("SELECT * FROM saldos_reportes WHERE nivel = 1 AND team_id = $team_id AND (COALESCE(anterior,0)+COALESCE(cargos,0)+COALESCE(abonos,0)) != 0 ");
        $saldo_v = 0;
        $saldo_g = 0;
        foreach ($cuentas as $cuenta) {
            $cod = intval(substr($cuenta->codigo,0,3));
            if($cod > 399&&$cod < 500)
            {
                if($cuenta->naturaleza == 'D') {
                    $saldo_v += $cuenta->cargos - $cuenta->abonos;
                }else{
                    $saldo_v += $cuenta->abonos - $cuenta->cargos;
                }
            }
            if($cod > 500)
            {
                if($cuenta->naturaleza == 'D') {
                    $saldo_g += $cuenta->cargos - $cuenta->abonos;
                }else{
                    $saldo_g += $cuenta->abonos - $cuenta->cargos;
                }
            }
        }
        $this->utilidad = floatval($saldo_v) - floatval($saldo_g);
        $this->color = Color::Green;
        if($this->utilidad < 0) $this->color = Color::Red;
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Cartera Vencida', '$'.number_format($this->saldo_cxc,2))
                ->chartColor(Color::Green)->chart([1,2,3,4,5]),
            Stat::make('Utilidad del Periodo', '$'.number_format($this->utilidad,2))
                ->chartColor($this->color)->chart([1,2,3,4,5]),
        ];
    }
}
