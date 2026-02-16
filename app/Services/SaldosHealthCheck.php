<?php

namespace App\Services;

use App\Models\Auxiliares;
use App\Models\SaldosReportes;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para health checks automáticos del sistema de saldos
 *
 * FASE 3: Verificación automática de consistencia y salud del sistema
 */
class SaldosHealthCheck
{
    /**
     * Ejecutar todos los health checks
     */
    public static function runAllChecks(int $team_id = null): array
    {
        $results = [];

        $results[] = self::checkDataConsistency($team_id);
        $results[] = self::checkPerformance($team_id);
        $results[] = self::checkQueueHealth();
        $results[] = self::checkCacheHealth();
        $results[] = self::checkDatabaseHealth();

        return $results;
    }

    /**
     * Verificar consistencia de datos entre auxiliares y saldos_reportes
     */
    public static function checkDataConsistency(int $team_id = null): array
    {
        $start = microtime(true);
        $inconsistencies = [];

        try {
            $query = DB::table('saldos_reportes as sr')
                ->join('cat_cuentas as cc', 'sr.codigo', '=', 'cc.codigo')
                ->select(
                    'sr.codigo',
                    'sr.cargos as sr_cargos',
                    'sr.abonos as sr_abonos',
                    DB::raw('COALESCE((SELECT SUM(cargo) FROM auxiliares WHERE codigo = sr.codigo AND team_id = sr.team_id), 0) as aux_cargos'),
                    DB::raw('COALESCE((SELECT SUM(abono) FROM auxiliares WHERE codigo = sr.codigo AND team_id = sr.team_id), 0) as aux_abonos')
                )
                ->where('sr.team_id', $team_id)
                ->whereRaw('ABS(sr.cargos - COALESCE((SELECT SUM(cargo) FROM auxiliares WHERE codigo = sr.codigo AND team_id = sr.team_id), 0)) > 0.01');

            if ($team_id) {
                $inconsistencies = $query->limit(10)->get()->toArray();
            }

            $count = count($inconsistencies);
            $status = $count === 0 ? 'pass' : ($count < 5 ? 'warning' : 'fail');
            $message = $count === 0
                ? 'Todos los saldos son consistentes con auxiliares'
                : "Se encontraron {$count} inconsistencias en los saldos";

            self::recordCheck(
                $team_id,
                'consistency',
                $status,
                $message,
                [
                    'inconsistencies_found' => $count,
                    'sample' => array_slice($inconsistencies, 0, 3),
                    'duration_ms' => round((microtime(true) - $start) * 1000, 2)
                ]
            );

            // Crear alerta si hay muchas inconsistencias
            if ($count >= 5) {
                SaldosMetrics::createAlert(
                    $team_id,
                    'data_inconsistency',
                    'error',
                    'Inconsistencias detectadas en saldos',
                    "Se encontraron {$count} cuentas con diferencias entre saldos_reportes y auxiliares",
                    ['inconsistencies_count' => $count]
                );
            }

            return [
                'check' => 'data_consistency',
                'status' => $status,
                'message' => $message,
                'details' => compact('count', 'inconsistencies')
            ];

        } catch (\Exception $e) {
            self::recordCheck($team_id, 'consistency', 'fail', 'Error al verificar consistencia: ' . $e->getMessage());

            return [
                'check' => 'data_consistency',
                'status' => 'fail',
                'message' => 'Error al verificar consistencia',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar performance del sistema
     */
    public static function checkPerformance(int $team_id = null): array
    {
        try {
            // Verificar jobs recientes
            $recentJobs = DB::table('saldos_job_history')
                ->where('created_at', '>=', now()->subHour())
                ->where('team_id', $team_id)
                ->get();

            $avgDuration = $recentJobs->where('status', 'completed')->avg('duration_ms') ?? 0;
            $failureRate = $recentJobs->count() > 0
                ? ($recentJobs->where('status', 'failed')->count() / $recentJobs->count()) * 100
                : 0;

            $status = 'pass';
            $message = 'Performance normal';

            if ($avgDuration > 1000) {
                $status = 'warning';
                $message = "Tiempo promedio de jobs alto: {$avgDuration}ms";
            }

            if ($failureRate > 10) {
                $status = 'fail';
                $message = "Tasa de fallos alta: {$failureRate}%";

                SaldosMetrics::createAlert(
                    $team_id,
                    'performance_degradation',
                    'warning',
                    'Degradación de performance detectada',
                    $message,
                    ['avg_duration_ms' => $avgDuration, 'failure_rate' => $failureRate]
                );
            }

            self::recordCheck($team_id, 'performance', $status, $message, [
                'avg_duration_ms' => round($avgDuration, 2),
                'failure_rate' => round($failureRate, 2),
                'total_jobs' => $recentJobs->count()
            ]);

            return [
                'check' => 'performance',
                'status' => $status,
                'message' => $message,
                'details' => [
                    'avg_duration_ms' => round($avgDuration, 2),
                    'failure_rate' => round($failureRate, 2)
                ]
            ];

        } catch (\Exception $e) {
            self::recordCheck($team_id, 'performance', 'fail', 'Error al verificar performance: ' . $e->getMessage());

            return [
                'check' => 'performance',
                'status' => 'fail',
                'message' => 'Error al verificar performance',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar salud de la queue
     */
    public static function checkQueueHealth(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->where('queue', 'saldos')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'pass';
            $message = 'Queue funcionando correctamente';

            if ($pendingJobs > 100) {
                $status = 'warning';
                $message = "Queue con {$pendingJobs} jobs pendientes";
            }

            if ($failedJobs > 50) {
                $status = 'fail';
                $message = "Muchos jobs fallidos: {$failedJobs}";

                SaldosMetrics::createAlert(
                    null,
                    'queue_overload',
                    'warning',
                    'Queue con carga alta',
                    $message,
                    ['pending_jobs' => $pendingJobs, 'failed_jobs' => $failedJobs]
                );
            }

            self::recordCheck(null, 'queue_health', $status, $message, [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs
            ]);

            return [
                'check' => 'queue_health',
                'status' => $status,
                'message' => $message,
                'details' => compact('pendingJobs', 'failedJobs')
            ];

        } catch (\Exception $e) {
            return [
                'check' => 'queue_health',
                'status' => 'fail',
                'message' => 'Error al verificar queue',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar salud del caché
     */
    public static function checkCacheHealth(): array
    {
        try {
            $cacheStats = SaldosMetrics::getCacheStats(null, 1);

            $status = 'pass';
            $message = "Cache hit rate: {$cacheStats['hit_rate']}%";

            if ($cacheStats['hit_rate'] < 50) {
                $status = 'warning';
                $message = "Cache hit rate bajo: {$cacheStats['hit_rate']}%";
            }

            self::recordCheck(null, 'cache_health', $status, $message, $cacheStats);

            return [
                'check' => 'cache_health',
                'status' => $status,
                'message' => $message,
                'details' => $cacheStats
            ];

        } catch (\Exception $e) {
            return [
                'check' => 'cache_health',
                'status' => 'fail',
                'message' => 'Error al verificar caché',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar salud de la base de datos
     */
    public static function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;

            $status = $duration < 100 ? 'pass' : ($duration < 500 ? 'warning' : 'fail');
            $message = "Tiempo de respuesta DB: " . round($duration, 2) . "ms";

            self::recordCheck(null, 'database_health', $status, $message, [
                'response_time_ms' => round($duration, 2)
            ]);

            return [
                'check' => 'database_health',
                'status' => $status,
                'message' => $message,
                'details' => ['response_time_ms' => round($duration, 2)]
            ];

        } catch (\Exception $e) {
            self::recordCheck(null, 'database_health', 'fail', 'Error de conexión a DB');

            return [
                'check' => 'database_health',
                'status' => 'fail',
                'message' => 'Error de conexión a base de datos',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Registrar resultado de health check
     */
    protected static function recordCheck(
        ?int $team_id,
        string $check_type,
        string $status,
        string $message,
        array $details = []
    ): void {
        try {
            DB::table('saldos_health_checks')->insert([
                'team_id' => $team_id,
                'check_type' => $check_type,
                'status' => $status,
                'message' => $message,
                'details' => !empty($details) ? json_encode($details) : null,
                'checked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to record health check', [
                'check_type' => $check_type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Auto-corrección de inconsistencias simples
     */
    public static function autoFixInconsistencies(int $team_id, int $ejercicio, int $periodo): array
    {
        try {
            // Forzar recalculo de saldos
            app(\App\Services\SaldosService::class)->recalcularTodosSaldos($team_id, $ejercicio, $periodo);

            // Verificar nuevamente
            $result = self::checkDataConsistency($team_id);

            return [
                'success' => true,
                'message' => 'Recalculo completo ejecutado',
                'result' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al intentar corregir inconsistencias',
                'error' => $e->getMessage()
            ];
        }
    }
}
