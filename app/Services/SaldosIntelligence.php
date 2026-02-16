<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FASE 4: Servicio de Inteligencia y Optimización Predictiva
 *
 * Funcionalidades:
 * - Precarga inteligente de cache basada en patrones de uso
 * - Predicción de recursos necesarios
 * - Análisis de tendencias
 */
class SaldosIntelligence
{
    /**
     * Analiza patrones de uso y precarga cache para recursos frecuentes
     *
     * @param int|null $team_id Team específico o todos
     * @param int $hours Ventana de análisis en horas
     * @return array Estadísticas de precarga
     */
    public static function warmCacheFromPatterns(?int $team_id = null, int $hours = 24): array
    {
        $now = now();
        $currentHour = $now->hour;
        $currentWeekday = $now->dayOfWeek;

        // Obtener patrones de uso frecuentes
        $patterns = DB::table('saldos_usage_patterns')
            ->select('team_id', 'resource_type', 'resource_id', 'ejercicio', 'periodo',
                     DB::raw('SUM(access_count) as total_accesses'),
                     DB::raw('AVG(HOUR(typical_access_time)) as avg_hour'))
            ->when($team_id, fn($q) => $q->where('team_id', $team_id))
            ->where('last_accessed_at', '>=', now()->subHours($hours))
            ->groupBy('team_id', 'resource_type', 'resource_id', 'ejercicio', 'periodo')
            ->having('total_accesses', '>=', 3) // Al menos 3 accesos
            ->orderBy('total_accesses', 'desc')
            ->limit(50) // Top 50 recursos más usados
            ->get();

        $preloaded = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($patterns as $pattern) {
            try {
                // Verificar si es hora probable de acceso
                $avgHour = $pattern->avg_hour ?? $currentHour;
                $hourDiff = abs($currentHour - $avgHour);

                // Si estamos dentro de 2 horas del horario típico, precarga
                if ($hourDiff <= 2 || $hourDiff >= 22) {
                    $preloaded += self::preloadResource($pattern);
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::warning('Error precargando recurso', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'patterns_analyzed' => count($patterns),
            'resources_preloaded' => $preloaded,
            'resources_skipped' => $skipped,
            'errors' => $errors,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Precarga un recurso específico en cache
     */
    protected static function preloadResource(object $pattern): int
    {
        $cacheKey = self::buildCacheKey($pattern);

        // Si ya está en cache, no hacer nada
        if (Cache::has($cacheKey)) {
            return 0;
        }

        // Precarga según tipo de recurso
        switch ($pattern->resource_type) {
            case 'dashboard':
            case 'indicadores':
                return self::preloadDashboardData($pattern);

            case 'report':
            case 'reporte':
                return self::preloadReportData($pattern);

            case 'account':
            case 'cuenta':
                return self::preloadAccountData($pattern);

            default:
                return 0;
        }
    }

    /**
     * Precarga datos de dashboard/indicadores
     *
     * Nota: saldos_reportes no tiene ejercicio/periodo, usa el del team actual
     */
    protected static function preloadDashboardData(object $pattern): int
    {
        $cacheKey = self::buildCacheKey($pattern);

        // Verificar que el team tenga el ejercicio/periodo del patrón
        $team = DB::table('teams')
            ->where('id', $pattern->team_id)
            ->where('ejercicio', $pattern->ejercicio)
            ->where('periodo', $pattern->periodo)
            ->first();

        if (!$team) {
            return 0; // Team no está en ese ejercicio/periodo
        }

        // Obtener indicadores principales
        $data = DB::table('saldos_reportes')
            ->where('team_id', $pattern->team_id)
            ->whereIn('codigo', ['10510100', '20510100', '40100000', '50100000']) // Cuentas principales
            ->get();

        if ($data->isNotEmpty()) {
            Cache::put($cacheKey, $data, 300); // 5 minutos
            return 1;
        }

        return 0;
    }

    /**
     * Precarga datos de reporte
     */
    protected static function preloadReportData(object $pattern): int
    {
        $cacheKey = self::buildCacheKey($pattern);

        // Verificar que el team tenga el ejercicio/periodo del patrón
        $team = DB::table('teams')
            ->where('id', $pattern->team_id)
            ->where('ejercicio', $pattern->ejercicio)
            ->where('periodo', $pattern->periodo)
            ->first();

        if (!$team) {
            return 0;
        }

        $data = DB::table('saldos_reportes')
            ->where('team_id', $pattern->team_id)
            ->get();

        if ($data->isNotEmpty()) {
            Cache::put($cacheKey, $data, 300);
            return 1;
        }

        return 0;
    }

    /**
     * Precarga datos de cuenta específica
     */
    protected static function preloadAccountData(object $pattern): int
    {
        $cacheKey = self::buildCacheKey($pattern);

        // Verificar que el team tenga el ejercicio/periodo del patrón
        $team = DB::table('teams')
            ->where('id', $pattern->team_id)
            ->where('ejercicio', $pattern->ejercicio)
            ->where('periodo', $pattern->periodo)
            ->first();

        if (!$team) {
            return 0;
        }

        $data = DB::table('saldos_reportes')
            ->where('team_id', $pattern->team_id)
            ->where('codigo', $pattern->resource_id)
            ->first();

        if ($data) {
            Cache::put($cacheKey, $data, 300);
            return 1;
        }

        return 0;
    }

    /**
     * Construye clave de cache consistente
     */
    protected static function buildCacheKey(object $pattern): string
    {
        return sprintf(
            'preload:%s:%s:%d:%d:%d',
            $pattern->resource_type,
            $pattern->resource_id,
            $pattern->team_id,
            $pattern->ejercicio,
            $pattern->periodo
        );
    }

    /**
     * Predice recursos que serán necesarios pronto
     *
     * @param int $team_id
     * @param int $lookaheadHours Horas hacia adelante
     * @return array Recursos predichos
     */
    public static function predictNeededResources(int $team_id, int $lookaheadHours = 1): array
    {
        $currentHour = now()->hour;
        $targetHour = ($currentHour + $lookaheadHours) % 24;

        // Buscar patrones de acceso en el horario objetivo
        $predictions = DB::table('saldos_usage_patterns')
            ->select('resource_type', 'resource_id', 'ejercicio', 'periodo',
                     DB::raw('SUM(access_count) as predicted_accesses'))
            ->where('team_id', $team_id)
            ->whereRaw('HOUR(typical_access_time) BETWEEN ? AND ?',
                       [$targetHour - 1, $targetHour + 1])
            ->where('last_accessed_at', '>=', now()->subDays(7))
            ->groupBy('resource_type', 'resource_id', 'ejercicio', 'periodo')
            ->having('predicted_accesses', '>=', 2)
            ->orderBy('predicted_accesses', 'desc')
            ->limit(20)
            ->get();

        return $predictions->toArray();
    }

    /**
     * Analiza tendencias de uso para optimización
     *
     * @param int|null $team_id
     * @param int $days Días de histórico
     * @return array Análisis de tendencias
     */
    public static function analyzeTrends(?int $team_id = null, int $days = 7): array
    {
        $startDate = now()->subDays($days);

        // Tendencias de acceso por tipo de recurso
        $resourceTrends = DB::table('saldos_usage_patterns')
            ->select('resource_type',
                     DB::raw('SUM(access_count) as total_accesses'),
                     DB::raw('COUNT(DISTINCT team_id) as teams_using'),
                     DB::raw('AVG(access_count) as avg_accesses'))
            ->when($team_id, fn($q) => $q->where('team_id', $team_id))
            ->where('last_accessed_at', '>=', $startDate)
            ->groupBy('resource_type')
            ->get();

        // Horarios pico de uso
        $peakHours = DB::table('saldos_usage_patterns')
            ->select(DB::raw('HOUR(typical_access_time) as hour'),
                     DB::raw('SUM(access_count) as accesses'))
            ->when($team_id, fn($q) => $q->where('team_id', $team_id))
            ->where('last_accessed_at', '>=', $startDate)
            ->groupBy('hour')
            ->orderBy('accesses', 'desc')
            ->limit(5)
            ->get();

        // Recursos más populares
        $topResources = DB::table('saldos_usage_patterns')
            ->select('resource_type', 'resource_id',
                     DB::raw('SUM(access_count) as total_accesses'))
            ->when($team_id, fn($q) => $q->where('team_id', $team_id))
            ->where('last_accessed_at', '>=', $startDate)
            ->groupBy('resource_type', 'resource_id')
            ->orderBy('total_accesses', 'desc')
            ->limit(10)
            ->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => now()->toDateString(),
                'days' => $days,
            ],
            'resource_trends' => $resourceTrends->toArray(),
            'peak_hours' => $peakHours->toArray(),
            'top_resources' => $topResources->toArray(),
            'total_patterns' => DB::table('saldos_usage_patterns')
                ->when($team_id, fn($q) => $q->where('team_id', $team_id))
                ->where('last_accessed_at', '>=', $startDate)
                ->count(),
        ];
    }

    /**
     * Limpia patrones de uso obsoletos
     *
     * @param int $days Días de retención
     * @return int Registros eliminados
     */
    public static function cleanOldPatterns(int $days = 30): int
    {
        return DB::table('saldos_usage_patterns')
            ->where('last_accessed_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Optimiza tabla de patrones de uso consolidando entradas
     *
     * @return array Estadísticas de optimización
     */
    public static function optimizePatterns(): array
    {
        $beforeCount = DB::table('saldos_usage_patterns')->count();

        // Consolidar patrones duplicados
        DB::statement("
            INSERT INTO saldos_usage_patterns
                (team_id, user_id, resource_type, resource_id, access_count,
                 last_accessed_at, ejercicio, periodo, typical_access_time, created_at, updated_at)
            SELECT
                team_id, user_id, resource_type, resource_id,
                SUM(access_count) as access_count,
                MAX(last_accessed_at) as last_accessed_at,
                ejercicio, periodo,
                AVG(TIME_TO_SEC(typical_access_time)) as typical_access_time,
                MIN(created_at) as created_at,
                NOW() as updated_at
            FROM saldos_usage_patterns
            WHERE last_accessed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY team_id, user_id, resource_type, resource_id, ejercicio, periodo
            HAVING COUNT(*) > 1
            ON DUPLICATE KEY UPDATE
                access_count = VALUES(access_count),
                last_accessed_at = VALUES(last_accessed_at),
                typical_access_time = SEC_TO_TIME(VALUES(typical_access_time)),
                updated_at = NOW()
        ");

        $afterCount = DB::table('saldos_usage_patterns')->count();

        return [
            'before_count' => $beforeCount,
            'after_count' => $afterCount,
            'optimized' => $beforeCount - $afterCount,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
