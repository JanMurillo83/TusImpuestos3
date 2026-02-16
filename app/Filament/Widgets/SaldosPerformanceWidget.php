<?php

namespace App\Filament\Widgets;

use App\Services\SaldosMetrics;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaldosPerformanceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $team_id = Filament::getTenant()->id;
        $summary = SaldosMetrics::getDashboardSummary($team_id);

        $avgJobTime = $summary['job_performance']['avg_duration_ms'] ?? 0;
        $jobSuccessRate = $summary['job_performance']['success_rate'] ?? 0;
        $totalJobs = $summary['job_performance']['total_jobs_24h'] ?? 0;

        // Determine performance color
        $performanceColor = $avgJobTime < 100 ? Color::Green :
                           ($avgJobTime < 500 ? Color::Yellow : Color::Red);

        $successColor = $jobSuccessRate >= 95 ? Color::Green :
                       ($jobSuccessRate >= 80 ? Color::Yellow : Color::Red);

        return [
            Stat::make('Tiempo Promedio de Job', number_format($avgJobTime, 0) . ' ms')
                ->description('Últimas 24 horas')
                ->descriptionIcon('heroicon-o-clock')
                ->color($performanceColor)
                ->chart($this->generatePerformanceChart($team_id)),

            Stat::make('Tasa de Éxito', number_format($jobSuccessRate, 1) . '%')
                ->description($totalJobs . ' jobs ejecutados')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color($successColor),
        ];
    }

    protected function generatePerformanceChart(int $team_id): array
    {
        $stats = SaldosMetrics::getJobPerformance($team_id, 24);
        return array_column($stats, 'avg_duration_ms') ?: [0];
    }
}
