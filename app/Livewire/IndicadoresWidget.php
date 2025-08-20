<?php

namespace App\Livewire;

use App\Http\Controllers\ReportesController;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IndicadoresWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 1;
    }

    public ?float $saldo_banco;
    public ?float $saldo_clientes;
    public ?float $saldo_proveedores;

    public function mount(): void
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        (new ReportesController())->ContabilizaReporte($ejercicio, $periodo, $team_id);
        $this->saldo_banco = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','10200000')->first()->final ?? 0);
        $this->saldo_clientes = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','10500000')->first()->final ?? 0);
        $this->saldo_proveedores = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','20100000')->first()->final ?? 0);
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Saldo en Bancos', '$'.number_format($this->saldo_banco,2)),
            Stat::make('Clientes', '$'.number_format($this->saldo_clientes,2)),
            Stat::make('Proveedores', '$'.number_format($this->saldo_proveedores,2)),
        ];
    }
}
