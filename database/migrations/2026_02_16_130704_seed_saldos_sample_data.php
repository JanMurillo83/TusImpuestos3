<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FASE 3: Crear datos de muestra para visualización del dashboard
     */
    public function up(): void
    {
        $now = now();

        // Obtener un team_id existente (el primero disponible)
        $team_id = DB::table('teams')->value('id');

        if (!$team_id) {
            return; // No hay teams, no crear datos de muestra
        }

        // 1. Métricas de cache (últimas 24 horas)
        $metricsData = [];
        for ($i = 24; $i >= 0; $i--) {
            $timestamp = $now->copy()->subHours($i);

            // Cache hits
            $metricsData[] = [
                'team_id' => $team_id,
                'metric_type' => 'cache',
                'metric_name' => 'cache_hit',
                'value' => rand(50, 150),
                'unit' => 'count',
                'recorded_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            // Cache misses
            $metricsData[] = [
                'team_id' => $team_id,
                'metric_type' => 'cache',
                'metric_name' => 'cache_miss',
                'value' => rand(10, 40),
                'unit' => 'count',
                'recorded_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            // Job execution time
            $metricsData[] = [
                'team_id' => $team_id,
                'metric_type' => 'job_execution',
                'metric_name' => 'duration',
                'value' => rand(50, 200),
                'unit' => 'ms',
                'recorded_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('saldos_metrics')->insert($metricsData);

        // 2. Job history (últimos 50 jobs simulados)
        $jobHistoryData = [];
        for ($i = 0; $i < 50; $i++) {
            $timestamp = $now->copy()->subMinutes($i * 5);
            $duration = rand(50, 300);
            $status = rand(1, 100) > 5 ? 'completed' : 'failed'; // 95% success rate

            $jobHistoryData[] = [
                'job_id' => 'job_' . uniqid(),
                'team_id' => $team_id,
                'codigo' => ['10510100', '40100000', '50100000', '20510100'][rand(0, 3)],
                'ejercicio' => 2026,
                'periodo' => 2,
                'status' => $status,
                'queued_at' => $timestamp->copy()->subSeconds(2),
                'started_at' => $timestamp->copy()->subSeconds(1),
                'completed_at' => $timestamp,
                'duration_ms' => $duration,
                'attempts' => $status === 'failed' ? rand(2, 3) : 1,
                'error_message' => $status === 'failed' ? 'Timeout al actualizar saldos' : null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('saldos_job_history')->insert($jobHistoryData);

        // 3. Audit log (últimas 30 entradas)
        $auditLogData = [];
        for ($i = 0; $i < 30; $i++) {
            $timestamp = $now->copy()->subMinutes($i * 10);
            $oldValue = rand(10000, 100000) / 100;
            $newValue = $oldValue + rand(-5000, 5000) / 100;

            $auditLogData[] = [
                'team_id' => $team_id,
                'codigo' => ['10510100', '40100000', '50100000', '20510100'][rand(0, 3)],
                'field_changed' => 'final',
                'action' => 'updated',
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'difference' => $newValue - $oldValue,
                'triggered_by' => 'job',
                'metadata' => json_encode(['ejercicio' => 2026, 'periodo' => 2]),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('saldos_audit_log')->insert($auditLogData);

        // 4. Alertas (algunas resueltas, otras pendientes)
        $alertsData = [
            [
                'team_id' => $team_id,
                'alert_type' => 'performance_degradation',
                'severity' => 'warning',
                'title' => 'Degradación de Performance Detectada',
                'message' => 'El tiempo promedio de ejecución de jobs ha aumentado un 30% en la última hora',
                'details' => json_encode(['avg_duration_before' => 85, 'avg_duration_now' => 120]),
                'acknowledged' => true,
                'acknowledged_at' => $now->copy()->subHours(2),
                'resolved_at' => $now->copy()->subHours(1),
                'created_at' => $now->copy()->subHours(3),
                'updated_at' => $now->copy()->subHours(1),
            ],
            [
                'team_id' => $team_id,
                'alert_type' => 'cache_performance',
                'severity' => 'info',
                'title' => 'Cache Hit Rate Bajo',
                'message' => 'El cache hit rate está por debajo del 70% en las últimas 4 horas',
                'details' => json_encode(['hit_rate' => 65, 'threshold' => 70]),
                'acknowledged' => false,
                'acknowledged_at' => null,
                'resolved_at' => null,
                'created_at' => $now->copy()->subHours(2),
                'updated_at' => $now->copy()->subHours(2),
            ],
            [
                'team_id' => $team_id,
                'alert_type' => 'data_inconsistency',
                'severity' => 'critical',
                'title' => 'Inconsistencia en Saldos Detectada',
                'message' => 'Se encontraron 3 cuentas con discrepancias entre saldos_reportes y auxiliares',
                'details' => json_encode(['affected_accounts' => ['10510100', '20510100', '40100000']]),
                'acknowledged' => false,
                'acknowledged_at' => null,
                'resolved_at' => null,
                'created_at' => $now->copy()->subMinutes(30),
                'updated_at' => $now->copy()->subMinutes(30),
            ],
            [
                'team_id' => $team_id,
                'alert_type' => 'queue_health',
                'severity' => 'warning',
                'title' => 'Jobs Pendientes Acumulándose',
                'message' => 'Hay 25 jobs pendientes en la cola de saldos',
                'details' => json_encode(['pending_jobs' => 25, 'threshold' => 20]),
                'acknowledged' => true,
                'acknowledged_at' => $now->copy()->subMinutes(45),
                'resolved_at' => null,
                'created_at' => $now->copy()->subHour(),
                'updated_at' => $now->copy()->subMinutes(45),
            ],
        ];

        DB::table('saldos_alerts')->insert($alertsData);

        // 5. Health checks (resultados históricos)
        $healthChecksData = [
            [
                'team_id' => $team_id,
                'check_type' => 'data_consistency',
                'status' => 'pass',
                'message' => 'Todos los saldos son consistentes',
                'details' => json_encode(['checked_accounts' => 500, 'inconsistencies' => 0]),
                'checked_at' => $now->copy()->subMinutes(10),
                'created_at' => $now->copy()->subMinutes(10),
                'updated_at' => $now->copy()->subMinutes(10),
            ],
            [
                'team_id' => $team_id,
                'check_type' => 'performance',
                'status' => 'warning',
                'message' => 'Performance por debajo del óptimo',
                'details' => json_encode(['avg_duration_ms' => 150, 'threshold' => 100]),
                'checked_at' => $now->copy()->subMinutes(10),
                'created_at' => $now->copy()->subMinutes(10),
                'updated_at' => $now->copy()->subMinutes(10),
            ],
            [
                'team_id' => $team_id,
                'check_type' => 'cache_health',
                'status' => 'pass',
                'message' => 'Cache funcionando correctamente',
                'details' => json_encode(['hit_rate' => 85, 'threshold' => 70]),
                'checked_at' => $now->copy()->subMinutes(10),
                'created_at' => $now->copy()->subMinutes(10),
                'updated_at' => $now->copy()->subMinutes(10),
            ],
        ];

        DB::table('saldos_health_checks')->insert($healthChecksData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar solo los datos de muestra (por team_id o todos)
        DB::table('saldos_metrics')->truncate();
        DB::table('saldos_job_history')->truncate();
        DB::table('saldos_audit_log')->truncate();
        DB::table('saldos_alerts')->truncate();
        DB::table('saldos_health_checks')->truncate();
    }
};
