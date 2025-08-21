<?php

namespace App\Livewire;

use App\Http\Controllers\ReportesController;
use App\Models\Auxiliares;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Indicadores2Widget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 1;
    }

    public ?float $saldo_iva;
    public ?float $saldo_isr;
    public ?float $saldo_iva_p;

    public function mount(): void
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        $aux =Auxiliares::where('team_id',Filament::getTenant()->id)->where('a_ejercicio',$ejercicio)->where('a_periodo',$periodo)->get();
        if(count($aux)>0)(new ReportesController())->ContabilizaReporte($ejercicio, $periodo, $team_id);
        $this->saldo_iva = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','11801000')->first()->final ?? 0);
        $this->saldo_iva_p = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','20801000')->first()->final ?? 0);
        $this->saldo_isr = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','21600000')->first()->final ?? 0);
    }
    protected function getStats(): array
    {
        return [
            Stat::make('IVA acreditable pagado', '$'.number_format($this->saldo_iva,2)),
            Stat::make('IVA trasladado cobrado', '$'.number_format($this->saldo_iva_p,2)),
            Stat::make('Impuestos retenidos', '$'.number_format($this->saldo_isr,2)),
        ];
    }
}
