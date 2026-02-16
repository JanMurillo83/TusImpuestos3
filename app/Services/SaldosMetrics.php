<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio para registro y monitoreo de métricas del sistema de saldos
 *
 * FASE 3: Monitoreo avanzado y métricas de performance
 */
class SaldosMetrics
{
    /**
     * Registrar métrica de performance
     */
    public static function recordMetric(
        int $team_id,
        string $metric_type,
        string $metric_name,
        float $value,
        string $unit = null,
        array $metadata = []
    ): void {
        try {
            DB::table('saldos_metrics')->insert([
                'team_id' => $team_id,
                'metric_type' => $metric_type,
                'metric_name' => $metric_name,
                'value' => $value,
                'unit' => $unit,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to record metric', [
                'metric' => $metric_name,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Registrar tiempo de ejecución de job
     */
    public static function recordJobExecution(
        int $team_id,
        string $codigo,
        int $duration_ms,
        bool $success = true
    ): void {
        self::recordMetric(
            $team_id,
            'job_execution',
            'actualizar_saldo',
            $duration_ms,
            'ms',
            [
                'codigo' => $codigo,
                'success' => $success
            ]
        );
    }

    /**
     * Registrar cache hit/miss
     */
    public static function recordCacheHit(int $team_id, string $cache_key, bool $hit): void
    {
        self::recordMetric(
            $team_id,
            'cache_performance',
            $hit ? 'cache_hit' : 'cache_miss',
            1,
            'count',
            ['cache_key' => $cache_key]
        );
    }

    /**
     * Registrar tiempo de query
     */
    public static function recordQueryTime(int $team_id, string $query_type, float $duration_ms): void
    {
        self::recordMetric(
            $team_id,
            'query_performance',
            $query_type,
            $duration_ms,
            'ms'
        );
    }

    /**
     * Obtener métricas agregadas
     */
    public static function getAggregatedMetrics(
        int $team_id = null,
        string $metric_type = null,
        int $hours = 24
    ): array {
        $query = DB::table('saldos_metrics')
            ->where('recorded_at', '>=', now()->subHours($hours));

        if ($team_id) {
            $query->where('team_id', $team_id);
        }

        if ($metric_type) {
            $query->where('metric_type', $metric_type);
        }

        $metrics = $query->get();

        return [
            'total_metrics' => $metrics->count(),
            'avg_value' => $metrics->avg('value'),
            'min_value' => $metrics->min('value'),
            'max_value' => $metrics->max('value'),
            'by_type' => $metrics->groupBy('metric_type')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'avg' => $group->avg('value'),
                    'min' => $group->min('value'),
                    'max' => $group->max('value'),
                ];
            })->toArray()
        ];
    }

    /**
     * Obtener estadísticas de cache
     */
    public static function getCacheStats(int $team_id = null, int $hours = 24): array
    {
        $query = DB::table('saldos_metrics')
            ->where('metric_type', 'cache_performance')
            ->where('recorded_at', '>=', now()->subHours($hours));

        if ($team_id) {
            $query->where('team_id', $team_id);
        }

        $hits = $query->where('metric_name', 'cache_hit')->count();
        $misses = $query->where('metric_name', 'cache_miss')->count();
        $total = $hits + $misses;

        return [
            'hits' => $hits,
            'misses' => $misses,
            'total' => $total,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Obtener performance de jobs
     */
    public static function getJobPerformance(int $team_id = null, int $hours = 24): array
    {
        $query = DB::table('saldos_job_history')
            ->where('created_at', '>=', now()->subHours($hours));

        if ($team_id) {
            $query->where('team_id', $team_id);
        }

        $jobs = $query->get();

        $completed = $jobs->where('status', 'completed');
        $failed = $jobs->where('status', 'failed');

        return [
            'total_jobs' => $jobs->count(),
            'completed' => $completed->count(),
            'failed' => $failed->count(),
            'success_rate' => $jobs->count() > 0 ? round(($completed->count() / $jobs->count()) * 100, 2) : 0,
            'avg_duration_ms' => $completed->avg('duration_ms'),
            'min_duration_ms' => $completed->min('duration_ms'),
            'max_duration_ms' => $completed->max('duration_ms'),
        ];
    }

    /**
     * Obtener dashboard summary
     */
    public static function getDashboardSummary(int $team_id = null): array
    {
        return [
            'cache_stats' => self::getCacheStats($team_id, 24),
            'job_performance' => self::getJobPerformance($team_id, 24),
            'recent_metrics' => self::getAggregatedMetrics($team_id, null, 24),
            'health_status' => self::getHealthStatus($team_id),
            'active_alerts' => self::getActiveAlerts($team_id),
        ];
    }

    /**
     * Obtener estado de salud
     */
    public static function getHealthStatus(int $team_id = null): array
    {
        $query = DB::table('saldos_health_checks')
            ->where('checked_at', '>=', now()->subHour());

        if ($team_id) {
            $query->where('team_id', $team_id);
        }

        $checks = $query->orderBy('checked_at', 'desc')->get();

        $total = $checks->count();
        $passed = $checks->where('status', 'pass')->count();
        $failed = $checks->where('status', 'fail')->count();
        $warnings = $checks->where('status', 'warning')->count();

        return [
            'overall_status' => $failed > 0 ? 'fail' : ($warnings > 0 ? 'warning' : 'pass'),
            'total_checks' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'last_check' => $checks->first()?->checked_at ?? null,
        ];
    }

    /**
     * Obtener alertas activas
     */
    public static function getActiveAlerts(int $team_id = null): array
    {
        $query = DB::table('saldos_alerts')
            ->where('acknowledged', false);

        if ($team_id) {
            $query->where('team_id', $team_id);
        }

        $alerts = $query->orderBy('created_at', 'desc')->limit(10)->get();

        return $alerts->map(function($alert) {
            return [
                'id' => $alert->id,
                'type' => $alert->alert_type,
                'severity' => $alert->severity,
                'title' => $alert->title,
                'message' => $alert->message,
                'created_at' => $alert->created_at,
            ];
        })->toArray();
    }

    /**
     * Crear alerta
     */
    public static function createAlert(
        int $team_id = null,
        string $alert_type,
        string $severity,
        string $title,
        string $message,
        array $details = []
    ): void {
        DB::table('saldos_alerts')->insert([
            'team_id' => $team_id,
            'alert_type' => $alert_type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'details' => !empty($details) ? json_encode($details) : null,
            'acknowledged' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Registrar patrón de uso
     */
    public static function recordUsagePattern(
        int $team_id,
        int $user_id = null,
        string $resource_type,
        string $resource_id,
        int $ejercicio,
        int $periodo
    ): void {
        DB::table('saldos_usage_patterns')
            ->updateOrInsert(
                [
                    'team_id' => $team_id,
                    'user_id' => $user_id,
                    'resource_type' => $resource_type,
                    'resource_id' => $resource_id,
                    'ejercicio' => $ejercicio,
                    'periodo' => $periodo,
                ],
                [
                    'access_count' => DB::raw('access_count + 1'),
                    'last_accessed_at' => now(),
                    'typical_access_time' => now()->format('H:i:s'),
                    'updated_at' => now(),
                ]
            );
    }

    /**
     * Obtener recursos más accedidos (para precarga)
     */
    public static function getTopAccessedResources(
        int $team_id,
        string $resource_type = null,
        int $limit = 10
    ): array {
        $query = DB::table('saldos_usage_patterns')
            ->where('team_id', $team_id);

        if ($resource_type) {
            $query->where('resource_type', $resource_type);
        }

        return $query
            ->orderBy('access_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Limpiar métricas antiguas
     */
    public static function cleanOldMetrics(int $days = 30): int
    {
        $date = now()->subDays($days);

        $deleted = DB::table('saldos_metrics')
            ->where('recorded_at', '<', $date)
            ->delete();

        DB::table('saldos_health_checks')
            ->where('checked_at', '<', $date)
            ->delete();

        DB::table('saldos_job_history')
            ->where('completed_at', '<', $date)
            ->where('status', 'completed')
            ->delete();

        return $deleted;
    }
}
