<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FASE 4: Optimizador de Queries Lentos
 *
 * Detecta queries lentos y sugiere/aplica optimizaciones
 */
class SaldosQueryOptimizer
{
    /**
     * Analiza queries lentos del sistema
     *
     * @param int $thresholdMs Umbral en milisegundos
     * @param int $hours Ventana de análisis
     * @return array Queries lentos detectados
     */
    public static function analyzeSlowQueries(int $thresholdMs = 1000, int $hours = 24): array
    {
        // Obtener métricas de queries lentos
        $slowQueries = DB::table('saldos_metrics')
            ->select('metric_name',
                     DB::raw('AVG(value) as avg_duration'),
                     DB::raw('MAX(value) as max_duration'),
                     DB::raw('COUNT(*) as occurrences'))
            ->where('metric_type', 'query_time')
            ->where('value', '>=', $thresholdMs)
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->groupBy('metric_name')
            ->orderBy('avg_duration', 'desc')
            ->get();

        $analysis = [];

        foreach ($slowQueries as $query) {
            $optimization = self::suggestOptimization($query->metric_name);

            $analysis[] = [
                'query' => $query->metric_name,
                'avg_duration_ms' => round($query->avg_duration, 2),
                'max_duration_ms' => round($query->max_duration, 2),
                'occurrences' => $query->occurrences,
                'optimization' => $optimization,
            ];
        }

        return $analysis;
    }

    /**
     * Sugiere optimización para un query específico
     */
    protected static function suggestOptimization(string $queryName): array
    {
        $suggestions = [];

        // Análisis basado en el nombre del query
        if (str_contains($queryName, 'auxiliares')) {
            $suggestions[] = [
                'type' => 'index',
                'suggestion' => 'Agregar índice compuesto en (team_id, codigo, a_ejercicio, a_periodo)',
                'priority' => 'high',
            ];
        }

        if (str_contains($queryName, 'saldos_reportes')) {
            $suggestions[] = [
                'type' => 'index',
                'suggestion' => 'Agregar índice compuesto en (team_id, ejercicio, periodo)',
                'priority' => 'medium',
            ];
        }

        if (str_contains($queryName, 'JOIN')) {
            $suggestions[] = [
                'type' => 'query_rewrite',
                'suggestion' => 'Considerar sub-queries o WITH clauses para mejorar performance',
                'priority' => 'medium',
            ];
        }

        return $suggestions;
    }

    /**
     * Aplica optimizaciones automáticas
     *
     * @param bool $dryRun Si true, solo simula sin aplicar
     * @return array Optimizaciones aplicadas
     */
    public static function applyAutomaticOptimizations(bool $dryRun = false): array
    {
        $optimizations = [];

        // 1. Verificar y crear índices faltantes
        $optimizations['indexes'] = self::optimizeIndexes($dryRun);

        // 2. Actualizar estadísticas de tablas
        $optimizations['statistics'] = self::updateTableStatistics($dryRun);

        // 3. Optimizar tablas fragmentadas
        $optimizations['defragmentation'] = self::defragmentTables($dryRun);

        return $optimizations;
    }

    /**
     * Optimiza índices en tablas críticas
     */
    protected static function optimizeIndexes(bool $dryRun = false): array
    {
        $indexes = [
            'auxiliares' => [
                ['columns' => ['team_id', 'codigo', 'a_ejercicio', 'a_periodo'], 'name' => 'idx_aux_saldos'],
                ['columns' => ['team_id', 'cat_polizas_id'], 'name' => 'idx_aux_polizas'],
            ],
            'saldos_reportes' => [
                ['columns' => ['team_id', 'ejercicio', 'periodo'], 'name' => 'idx_saldos_periodo'],
                ['columns' => ['team_id', 'codigo'], 'name' => 'idx_saldos_codigo'],
            ],
            'saldoscuentas' => [
                ['columns' => ['team_id', 'ejercicio', 'periodo'], 'name' => 'idx_sc_periodo'],
            ],
        ];

        $created = [];
        $existing = [];

        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $index) {
                $indexExists = self::checkIndexExists($table, $index['name']);

                if (!$indexExists) {
                    if (!$dryRun) {
                        try {
                            $columns = implode(', ', $index['columns']);
                            DB::statement("CREATE INDEX {$index['name']} ON {$table} ({$columns})");
                            $created[] = "{$table}.{$index['name']}";
                        } catch (\Exception $e) {
                            Log::warning("Error creating index", [
                                'table' => $table,
                                'index' => $index['name'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        $created[] = "{$table}.{$index['name']} (dry run)";
                    }
                } else {
                    $existing[] = "{$table}.{$index['name']}";
                }
            }
        }

        return [
            'created' => $created,
            'existing' => $existing,
        ];
    }

    /**
     * Verifica si un índice existe
     */
    protected static function checkIndexExists(string $table, string $indexName): bool
    {
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($result);
    }

    /**
     * Actualiza estadísticas de tablas para optimizar el query planner
     */
    protected static function updateTableStatistics(bool $dryRun = false): array
    {
        $tables = ['auxiliares', 'saldos_reportes', 'saldoscuentas', 'cat_cuentas'];
        $updated = [];

        if (!$dryRun) {
            foreach ($tables as $table) {
                try {
                    DB::statement("ANALYZE TABLE {$table}");
                    $updated[] = $table;
                } catch (\Exception $e) {
                    Log::warning("Error analyzing table", [
                        'table' => $table,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return [
            'tables_analyzed' => $dryRun ? [] : $updated,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Desfragmenta tablas para mejorar performance
     */
    protected static function defragmentTables(bool $dryRun = false): array
    {
        $tables = ['saldos_reportes', 'saldoscuentas', 'auxiliares'];
        $optimized = [];

        if (!$dryRun) {
            foreach ($tables as $table) {
                try {
                    DB::statement("OPTIMIZE TABLE {$table}");
                    $optimized[] = $table;
                } catch (\Exception $e) {
                    Log::warning("Error optimizing table", [
                        'table' => $table,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return [
            'tables_optimized' => $dryRun ? [] : $optimized,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Obtiene estadísticas de tamaño de tablas
     */
    public static function getTableStatistics(): array
    {
        $stats = DB::select("
            SELECT
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows,
                ROUND((data_free / 1024 / 1024), 2) AS fragmentation_mb
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            AND table_name IN ('auxiliares', 'saldos_reportes', 'saldoscuentas', 'cat_cuentas',
                               'saldos_metrics', 'saldos_job_history', 'saldos_audit_log')
            ORDER BY (data_length + index_length) DESC
        ");

        return array_map(function($row) {
            $arr = (array)$row;
            // Normalizar claves a minúsculas
            return [
                'table_name' => $arr['table_name'] ?? $arr['TABLE_NAME'] ?? 'unknown',
                'size_mb' => $arr['size_mb'] ?? $arr['SIZE_MB'] ?? 0,
                'table_rows' => $arr['table_rows'] ?? $arr['TABLE_ROWS'] ?? 0,
                'fragmentation_mb' => $arr['fragmentation_mb'] ?? $arr['FRAGMENTATION_MB'] ?? 0,
            ];
        }, $stats);
    }

    /**
     * Analiza el uso de cache y sugiere mejoras
     */
    public static function analyzeCacheUsage(int $hours = 24): array
    {
        $cacheStats = SaldosMetrics::getCacheStats(null, $hours);

        $totalHits = array_sum(array_column($cacheStats, 'hits'));
        $totalMisses = array_sum(array_column($cacheStats, 'misses'));
        $totalRequests = $totalHits + $totalMisses;
        $hitRate = $totalRequests > 0 ? ($totalHits / $totalRequests) * 100 : 0;

        $recommendations = [];

        if ($hitRate < 60) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => 'Cache hit rate muy bajo. Considerar aumentar TTL o precarga inteligente.',
                'action' => 'Habilitar precarga inteligente en Fase 4',
            ];
        }

        if ($hitRate < 80 && $hitRate >= 60) {
            $recommendations[] = [
                'severity' => 'medium',
                'message' => 'Cache hit rate mejorable. Analizar patrones de acceso.',
                'action' => 'Ejecutar SaldosIntelligence::analyzeTrends()',
            ];
        }

        if ($totalMisses > $totalHits * 2) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => 'Demasiados cache misses. Revisar estrategia de cache.',
                'action' => 'Aumentar TTL o implementar cache warming',
            ];
        }

        return [
            'period_hours' => $hours,
            'total_requests' => $totalRequests,
            'total_hits' => $totalHits,
            'total_misses' => $totalMisses,
            'hit_rate_percent' => round($hitRate, 2),
            'recommendations' => $recommendations,
        ];
    }
}
