<?php

namespace App\Filament\Widgets;

use App\Services\SaldosMetrics;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class SaldosJobPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Rendimiento de Jobs (Últimas 24h)';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $team_id = Filament::getTenant()->id;
        $stats = SaldosMetrics::getJobPerformance($team_id, 24);

        // Si no hay datos, retornar array vacío con estructura válida
        if (empty($stats)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Tiempo de Ejecución (ms)',
                        'data' => [0],
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'fill' => true,
                    ],
                ],
                'labels' => ['Sin datos'],
            ];
        }

        $labels = array_map(function($stat) {
            return \Carbon\Carbon::parse($stat['hour'])->format('H:i');
        }, $stats);

        $durations = array_column($stats, 'avg_duration_ms');

        return [
            'datasets' => [
                [
                    'label' => 'Tiempo Promedio (ms)',
                    'data' => $durations,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Milisegundos (ms)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Hora',
                    ],
                ],
            ],
        ];
    }
}
