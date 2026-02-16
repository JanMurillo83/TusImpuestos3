<?php

namespace App\Filament\Widgets;

use App\Services\SaldosMetrics;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class SaldosCacheHitRateChart extends ChartWidget
{
    protected static ?string $heading = 'Tasa de Acierto de Cache (Últimas 24h)';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $team_id = Filament::getTenant()->id;
        $stats = SaldosMetrics::getCacheStats($team_id, 24);

        // Si no hay datos, retornar array vacío con estructura válida
        if (empty($stats)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Hit Rate (%)',
                        'data' => [0],
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'fill' => true,
                    ],
                ],
                'labels' => ['Sin datos'],
            ];
        }

        $labels = array_map(function($stat) {
            return \Carbon\Carbon::parse($stat['hour'])->format('H:i');
        }, $stats);

        $hitRates = array_column($stats, 'hit_rate');
        $hits = array_column($stats, 'hits');
        $misses = array_column($stats, 'misses');

        return [
            'datasets' => [
                [
                    'label' => 'Hit Rate (%)',
                    'data' => $hitRates,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Cache Hits',
                    'data' => $hits,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'type' => 'bar',
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'Cache Misses',
                    'data' => $misses,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'type' => 'bar',
                    'yAxisID' => 'y1',
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
                    'type' => 'linear',
                    'position' => 'left',
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Hit Rate (%)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Cantidad',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
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
