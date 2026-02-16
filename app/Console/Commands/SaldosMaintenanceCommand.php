<?php

namespace App\Console\Commands;

use App\Services\SaldosAutoCorrection;
use App\Services\SaldosIntelligence;
use App\Services\SaldosQueryOptimizer;
use App\Services\SaldosMetrics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * FASE 4: Comando de Mantenimiento Automático
 *
 * Ejecuta tareas de mantenimiento, optimización y auto-corrección
 */
class SaldosMaintenanceCommand extends Command
{
    protected $signature = 'saldos:maintenance
                            {action=all : Acción a ejecutar (all, cache-warm, auto-correct, optimize, clean, report)}
                            {--team= : ID del team específico}
                            {--dry-run : Ejecutar en modo simulación sin aplicar cambios}
                            {--report-email= : Email para enviar reporte}';

    protected $description = 'FASE 4: Ejecuta mantenimiento automático del sistema de saldos';

    public function handle(): int
    {
        $action = $this->argument('action');
        $teamId = $this->option('team');
        $dryRun = $this->option('dry-run');

        $this->info("🔧 Iniciando mantenimiento del sistema de saldos");
        $this->info("Acción: {$action}");
        if ($teamId) {
            $this->info("Team: {$teamId}");
        }
        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: No se aplicarán cambios");
        }
        $this->newLine();

        $startTime = microtime(true);
        $results = [];

        try {
            switch ($action) {
                case 'cache-warm':
                    $results = $this->executeCacheWarming($teamId);
                    break;

                case 'auto-correct':
                    $results = $this->executeAutoCorrection($teamId, $dryRun);
                    break;

                case 'optimize':
                    $results = $this->executeOptimization($dryRun);
                    break;

                case 'clean':
                    $results = $this->executeCleanup($teamId, $dryRun);
                    break;

                case 'report':
                    $results = $this->generateReport($teamId);
                    break;

                case 'all':
                default:
                    $results = $this->executeFullMaintenance($teamId, $dryRun);
                    break;
            }

            $duration = round((microtime(true) - $startTime), 2);

            $this->newLine();
            $this->info("✅ Mantenimiento completado en {$duration} segundos");

            // Mostrar resumen
            $this->displayResults($results);

            // Enviar reporte por email si se solicitó
            if ($email = $this->option('report-email')) {
                $this->sendEmailReport($email, $results);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error durante el mantenimiento: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function executeCacheWarming(?int $teamId): array
    {
        $this->info("🔥 Precalentando cache basado en patrones de uso...");

        $results = SaldosIntelligence::warmCacheFromPatterns($teamId, 24);

        $this->line("  • Patrones analizados: {$results['patterns_analyzed']}");
        $this->line("  • Recursos precargados: {$results['resources_preloaded']}");
        $this->line("  • Recursos omitidos: {$results['resources_skipped']}");
        if ($results['errors'] > 0) {
            $this->warn("  • Errores: {$results['errors']}");
        }

        return ['cache_warming' => $results];
    }

    protected function executeAutoCorrection(?int $teamId, bool $dryRun): array
    {
        $this->info("🔧 Ejecutando auto-corrección de inconsistencias...");

        $results = SaldosAutoCorrection::runFullCorrection($teamId, $dryRun);

        foreach ($results['corrections'] as $type => $correction) {
            $detected = $correction['detected'] ?? 0;
            $fixed = $correction['fixed'] ?? 0;

            if ($detected > 0) {
                $status = $dryRun ? '🔍' : '✅';
                $this->line("  {$status} {$type}: Detectados {$detected}, Corregidos {$fixed}");
            }
        }

        return ['auto_correction' => $results];
    }

    protected function executeOptimization(bool $dryRun): array
    {
        $this->info("⚡ Optimizando base de datos...");

        $results = SaldosQueryOptimizer::applyAutomaticOptimizations($dryRun);

        // Índices
        if (!empty($results['indexes']['created'])) {
            $this->line("  • Índices creados: " . count($results['indexes']['created']));
            foreach ($results['indexes']['created'] as $index) {
                $this->line("    - {$index}");
            }
        }

        // Estadísticas
        if (!empty($results['statistics']['tables_analyzed'])) {
            $this->line("  • Tablas analizadas: " . count($results['statistics']['tables_analyzed']));
        }

        // Desfragmentación
        if (!empty($results['defragmentation']['tables_optimized'])) {
            $this->line("  • Tablas optimizadas: " . count($results['defragmentation']['tables_optimized']));
        }

        return ['optimization' => $results];
    }

    protected function executeCleanup(?int $teamId, bool $dryRun): array
    {
        $this->info("🧹 Limpiando datos obsoletos...");

        $results = [];

        // Limpiar patrones antiguos
        if (!$dryRun) {
            $deleted = SaldosIntelligence::cleanOldPatterns(30);
            $this->line("  • Patrones de uso eliminados: {$deleted}");
            $results['patterns_cleaned'] = $deleted;
        }

        // Limpiar métricas antiguas (> 90 días)
        if (!$dryRun) {
            $deleted = DB::table('saldos_metrics')
                ->where('recorded_at', '<', now()->subDays(90))
                ->delete();
            $this->line("  • Métricas antiguas eliminadas: {$deleted}");
            $results['metrics_cleaned'] = $deleted;
        }

        // Limpiar health checks antiguos (> 30 días)
        if (!$dryRun) {
            $deleted = DB::table('saldos_health_checks')
                ->where('checked_at', '<', now()->subDays(30))
                ->delete();
            $this->line("  • Health checks antiguos eliminados: {$deleted}");
            $results['health_checks_cleaned'] = $deleted;
        }

        // Limpiar jobs completados antiguos (> 7 días)
        if (!$dryRun) {
            $deleted = DB::table('saldos_job_history')
                ->where('status', 'completed')
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
            $this->line("  • Jobs completados antiguos eliminados: {$deleted}");
            $results['jobs_cleaned'] = $deleted;
        }

        if ($dryRun) {
            $this->warn("  ⚠️  Dry run: No se eliminaron registros");
        }

        return ['cleanup' => $results];
    }

    protected function generateReport(?int $teamId): array
    {
        $this->info("📊 Generando reporte del sistema...");

        $report = [];

        // Estado general
        $report['health_summary'] = SaldosAutoCorrection::detectIssues($teamId);

        // Estadísticas de cache
        $report['cache_analysis'] = SaldosQueryOptimizer::analyzeCacheUsage(24);

        // Estadísticas de tablas
        $report['table_statistics'] = SaldosQueryOptimizer::getTableStatistics();

        // Tendencias de uso
        $report['usage_trends'] = SaldosIntelligence::analyzeTrends($teamId, 7);

        // Mostrar resumen
        $this->displayReport($report);

        return ['report' => $report];
    }

    protected function executeFullMaintenance(?int $teamId, bool $dryRun): array
    {
        $this->info("🚀 Ejecutando mantenimiento completo...");
        $this->newLine();

        $results = [];

        // 1. Cache warming
        $results = array_merge($results, $this->executeCacheWarming($teamId));
        $this->newLine();

        // 2. Auto-corrección
        $results = array_merge($results, $this->executeAutoCorrection($teamId, $dryRun));
        $this->newLine();

        // 3. Optimización
        $results = array_merge($results, $this->executeOptimization($dryRun));
        $this->newLine();

        // 4. Limpieza
        $results = array_merge($results, $this->executeCleanup($teamId, $dryRun));
        $this->newLine();

        // 5. Reporte
        $results = array_merge($results, $this->generateReport($teamId));

        return $results;
    }

    protected function displayReport(array $report): void
    {
        $this->newLine();
        $this->line("═══════════════════════════════════════");
        $this->info("          REPORTE DEL SISTEMA");
        $this->line("═══════════════════════════════════════");
        $this->newLine();

        // Problemas detectados
        if (isset($report['health_summary'])) {
            $this->line("🏥 Salud del Sistema:");
            foreach ($report['health_summary'] as $key => $value) {
                $icon = $value > 0 ? '⚠️ ' : '✅';
                $this->line("  {$icon} {$key}: {$value}");
            }
            $this->newLine();
        }

        // Cache
        if (isset($report['cache_analysis'])) {
            $cache = $report['cache_analysis'];
            $this->line("💾 Análisis de Cache:");
            $this->line("  • Hit Rate: {$cache['hit_rate_percent']}%");
            $this->line("  • Total Requests: {$cache['total_requests']}");
            $this->line("  • Hits: {$cache['total_hits']} | Misses: {$cache['total_misses']}");
            $this->newLine();
        }

        // Tablas
        if (isset($report['table_statistics'])) {
            $this->line("📊 Tamaño de Tablas:");
            foreach ($report['table_statistics'] as $stat) {
                $this->line("  • {$stat['table_name']}: {$stat['size_mb']} MB ({$stat['table_rows']} rows)");
            }
            $this->newLine();
        }
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->line("═══════════════════════════════════════");
        $this->info("          RESUMEN DE RESULTADOS");
        $this->line("═══════════════════════════════════════");

        foreach ($results as $key => $value) {
            if (is_array($value)) {
                $this->line("✓ {$key}");
            }
        }
    }

    protected function sendEmailReport(string $email, array $results): void
    {
        $this->info("📧 Enviando reporte a {$email}...");
        // TODO: Implementar envío de email
        $this->warn("  ⚠️  Envío de email no implementado aún");
    }
}
