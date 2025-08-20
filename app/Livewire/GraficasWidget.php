<?php

namespace App\Livewire;

use App\Http\Controllers\ReportesController;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class GraficasWidget extends ChartWidget
{
    protected static ?string $heading = 'Ingresos Vs Costos';
    protected static ?string $maxHeight = '225px';
    public ?float $imp_ingresos;
    public ?float $imp_egresos;
    public function mount(): void
    {
        $ejercicio = Filament::getTenant()->ejercicio;
        $periodo = Filament::getTenant()->periodo;
        $team_id = Filament::getTenant()->id;
        (new ReportesController())->ContabilizaReporte($ejercicio, $periodo, $team_id);
        $this->imp_egresos = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','50100000')->first()->final ?? 0);
        $this->imp_ingresos = floatval(SaldosReportes::where('team_id',$team_id)->where('codigo','40101000')->first()->final ?? 0);
    }
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Ingresos Vs Costos',
                    'data' => [$this->imp_ingresos, $this->imp_egresos],
                    'backgroundColor' => ['#36A2EB','#FF6384'],
                ],
            ],
            'labels' => ['Ingreso', 'Costo'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected static ?array $options = [
        'scales' => [
            'x' => [
                'display' => false,
            ],
            'y' => [
                'display' => false,
            ],
        ],
    ];
}

