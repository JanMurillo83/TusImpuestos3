<?php

namespace App\Filament\Widgets;

use App\Services\SaldosMetrics;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaldosCacheStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $team_id = Filament::getTenant()->id;
        $summary = SaldosMetrics::getDashboardSummary($team_id);

        $hitRate = $summary['cache_stats']['hit_rate'] ?? 0;
        $totalHits = $summary['cache_stats']['total_hits'] ?? 0;
        $totalMisses = $summary['cache_stats']['total_misses'] ?? 0;
        $totalRequests = $totalHits + $totalMisses;

        // Determine cache performance color
        $cacheColor = $hitRate >= 80 ? Color::Green :
                     ($hitRate >= 50 ? Color::Yellow : Color::Red);

        return [
            Stat::make('Cache Hit Rate', number_format($hitRate, 1) . '%')
                ->description($totalRequests . ' peticiones totales')
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color($cacheColor)
                ->chart($this->generateCacheChart($team_id)),

            Stat::make('Cache Hits vs Misses', $totalHits . ' / ' . $totalMisses)
                ->description('Hits / Misses (24h)')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($cacheColor),
        ];
    }

    protected function generateCacheChart(int $team_id): array
    {
        $stats = SaldosMetrics::getCacheStats($team_id, 24);
        return array_column($stats, 'hit_rate') ?: [0];
    }
}
