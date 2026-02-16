<?php

namespace App\Filament\Widgets;

use App\Services\SaldosHealthCheck;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaldosHealthWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $team_id = Filament::getTenant()->id;
        $healthChecks = SaldosHealthCheck::runAllChecks($team_id);

        $passed = collect($healthChecks)->where('status', 'pass')->count();
        $warnings = collect($healthChecks)->where('status', 'warning')->count();
        $failed = collect($healthChecks)->where('status', 'fail')->count();
        $total = count($healthChecks);

        $overallStatus = $failed > 0 ? 'fail' : ($warnings > 0 ? 'warning' : 'pass');
        $statusColor = match($overallStatus) {
            'pass' => Color::Green,
            'warning' => Color::Yellow,
            'fail' => Color::Red,
            default => Color::Gray,
        };

        $statusIcon = match($overallStatus) {
            'pass' => '✅',
            'warning' => '⚠️',
            'fail' => '❌',
            default => '❓',
        };

        return [
            Stat::make('Estado General del Sistema', $statusIcon . ' ' . ucfirst($overallStatus))
                ->description("$passed/$total checks pasaron")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($statusColor),

            Stat::make('Checks Exitosos', $passed)
                ->description('Sin problemas detectados')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color(Color::Green),

            Stat::make('Advertencias', $warnings)
                ->description('Requieren atención')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($warnings > 0 ? Color::Yellow : Color::Gray),

            Stat::make('Fallos Críticos', $failed)
                ->description('Requieren acción inmediata')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($failed > 0 ? Color::Red : Color::Gray),
        ];
    }
}
