<?php

namespace App\Filament\Pages;

use App\Services\SaldosHealthCheck;
use App\Services\SaldosMetrics;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SaldosMonitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.saldos-monitoring';

    protected static ?string $navigationLabel = 'Monitor de Saldos';

    protected static ?string $title = 'Monitoreo del Sistema de Saldos';

    protected static ?string $navigationGroup = 'Herramientas';

    protected static ?int $navigationSort = 99;

    public array $healthChecks = [];
    public array $dashboardSummary = [];
    public array $recentAlerts = [];
    public array $recentAuditLog = [];
    public array $jobHistory = [];

    public function mount(): void
    {
        $team_id = Filament::getTenant()->id;

        // Load health checks
        $this->healthChecks = SaldosHealthCheck::runAllChecks($team_id);

        // Load dashboard summary
        $this->dashboardSummary = SaldosMetrics::getDashboardSummary($team_id);

        // Load recent alerts (last 10)
        $this->recentAlerts = DB::table('saldos_alerts')
            ->when($team_id, fn($q) => $q->where('team_id', $team_id))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Load recent audit log (last 20)
        $this->recentAuditLog = DB::table('saldos_audit_log')
            ->where('team_id', $team_id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        // Load recent job history (last 50)
        $this->jobHistory = DB::table('saldos_job_history')
            ->where('team_id', $team_id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function refreshData(): void
    {
        $this->mount();

        Notification::make()
            ->success()
            ->title('Datos actualizados')
            ->body('Los datos del dashboard se han actualizado correctamente.')
            ->send();
    }

    public function runHealthCheck(): void
    {
        $team_id = Filament::getTenant()->id;
        $this->healthChecks = SaldosHealthCheck::runAllChecks($team_id);

        Notification::make()
            ->success()
            ->title('Health Check Completado')
            ->body('El health check se ejecutó correctamente.')
            ->send();
    }

    public function resolveAlert(int $alertId): void
    {
        DB::table('saldos_alerts')
            ->where('id', $alertId)
            ->update(['resolved_at' => now()]);

        $this->mount();

        Notification::make()
            ->success()
            ->title('Alerta Resuelta')
            ->body('La alerta ha sido marcada como resuelta.')
            ->send();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SaldosHealthWidget::class,
            \App\Filament\Widgets\SaldosPerformanceWidget::class,
            \App\Filament\Widgets\SaldosCacheStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\SaldosJobPerformanceChart::class,
            \App\Filament\Widgets\SaldosCacheHitRateChart::class,
        ];
    }

    public function getHealthStatusColor(string $status): string
    {
        return match ($status) {
            'pass' => 'success',
            'warning' => 'warning',
            'fail' => 'danger',
            default => 'gray',
        };
    }

    public function getHealthStatusIcon(string $status): string
    {
        return match ($status) {
            'pass' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'fail' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getAlertSeverityColor(string $severity): string
    {
        return match ($severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'gray',
        };
    }
}
