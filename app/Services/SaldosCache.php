<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Servicio centralizado para gestión de caché de saldos contables
 *
 * Implementa estrategia de caché con TTL de 5 minutos para optimizar
 * performance sin sacrificar confiabilidad de datos contables.
 */
class SaldosCache
{
    /**
     * TTL del caché en segundos (5 minutos)
     */
    const CACHE_TTL = 300;

    /**
     * Obtener datos de Cuentas por Cobrar desde caché
     *
     * @param int $team_id ID del equipo/empresa
     * @param int $ejercicio Año fiscal
     * @param int $periodo Periodo (1-12)
     * @return mixed Datos cacheados o recalculados
     */
    public static function getCXC($team_id, $ejercicio, $periodo)
    {
        $key = "cxc:{$team_id}:{$ejercicio}:{$periodo}";

        return Cache::remember($key, self::CACHE_TTL, function() {
            // Los datos se calcularán por el modelo EstadCXC
            // Este método solo provee la estructura de caché
            return null;
        });
    }

    /**
     * Obtener datos de Cuentas por Pagar desde caché
     *
     * @param int $team_id ID del equipo/empresa
     * @param int $ejercicio Año fiscal
     * @param int $periodo Periodo (1-12)
     * @return mixed Datos cacheados o recalculados
     */
    public static function getCXP($team_id, $ejercicio, $periodo)
    {
        $key = "cxp:{$team_id}:{$ejercicio}:{$periodo}";

        return Cache::remember($key, self::CACHE_TTL, function() {
            // Los datos se calcularán por el modelo EstadCXP
            // Este método solo provee la estructura de caché
            return null;
        });
    }

    /**
     * Obtener datos de indicadores del dashboard desde caché
     *
     * @param int $team_id ID del equipo/empresa
     * @param int $ejercicio Año fiscal
     * @param int $periodo Periodo (1-12)
     * @return mixed Datos cacheados o recalculados
     */
    public static function getIndicadores($team_id, $ejercicio, $periodo)
    {
        $key = "indicadores:{$team_id}:{$ejercicio}:{$periodo}";

        return Cache::remember($key, self::CACHE_TTL, function() {
            // Los datos se calcularán por los widgets de indicadores
            return null;
        });
    }

    /**
     * Obtener datos de saldos_reportes desde caché
     *
     * @param int $team_id ID del equipo/empresa
     * @param int $ejercicio Año fiscal
     * @param int $periodo Periodo (1-12)
     * @return mixed Datos cacheados o recalculados
     */
    public static function getSaldosReportes($team_id, $ejercicio, $periodo)
    {
        $key = "saldos_reportes:{$team_id}:{$ejercicio}:{$periodo}";

        return Cache::remember($key, self::CACHE_TTL, function() {
            return null;
        });
    }

    /**
     * Invalidar todo el caché de un equipo específico
     *
     * Se llama después de:
     * - Contabilizar reportes (ContabilizaReporte)
     * - Crear/modificar pólizas
     * - Actualizar saldoscuentas
     *
     * @param int $team_id ID del equipo/empresa
     * @return void
     */
    public static function invalidate($team_id)
    {
        // Invalidar todos los ejercicios y periodos de este team
        // Usar pattern matching de Redis si está disponible
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            // Redis permite invalidación por patrón
            Cache::store('redis')->tags(["team:{$team_id}"])->flush();
        } else {
            // Para otros drivers, invalidar keys conocidas
            // Asumiendo ejercicios 2020-2030 y periodos 1-12
            for ($ejercicio = 2020; $ejercicio <= 2030; $ejercicio++) {
                for ($periodo = 1; $periodo <= 12; $periodo++) {
                    Cache::forget("cxc:{$team_id}:{$ejercicio}:{$periodo}");
                    Cache::forget("cxp:{$team_id}:{$ejercicio}:{$periodo}");
                    Cache::forget("indicadores:{$team_id}:{$ejercicio}:{$periodo}");
                    Cache::forget("saldos_reportes:{$team_id}:{$ejercicio}:{$periodo}");
                }
            }
        }
    }

    /**
     * Invalidar caché de un ejercicio específico
     *
     * @param int $team_id ID del equipo/empresa
     * @param int $ejercicio Año fiscal a invalidar
     * @return void
     */
    public static function invalidateEjercicio($team_id, $ejercicio)
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            Cache::store('redis')->tags(["team:{$team_id}", "ejercicio:{$ejercicio}"])->flush();
        } else {
            for ($periodo = 1; $periodo <= 12; $periodo++) {
                Cache::forget("cxc:{$team_id}:{$ejercicio}:{$periodo}");
                Cache::forget("cxp:{$team_id}:{$ejercicio}:{$periodo}");
                Cache::forget("indicadores:{$team_id}:{$ejercicio}:{$periodo}");
                Cache::forget("saldos_reportes:{$team_id}:{$ejercicio}:{$periodo}");
            }
        }
    }

    /**
     * Invalidar caché de un periodo específico
     *
     * @param int $team_id ID del equipo/empresa
     * @param int $ejercicio Año fiscal
     * @param int $periodo Periodo (1-12)
     * @return void
     */
    public static function invalidatePeriodo($team_id, $ejercicio, $periodo)
    {
        Cache::forget("cxc:{$team_id}:{$ejercicio}:{$periodo}");
        Cache::forget("cxp:{$team_id}:{$ejercicio}:{$periodo}");
        Cache::forget("indicadores:{$team_id}:{$ejercicio}:{$periodo}");
        Cache::forget("saldos_reportes:{$team_id}:{$ejercicio}:{$periodo}");
    }

    /**
     * Limpiar todo el caché de saldos (útil para mantenimiento)
     *
     * @return void
     */
    public static function flush()
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            Cache::store('redis')->tags(['saldos'])->flush();
        } else {
            Cache::flush();
        }
    }

    /**
     * Verificar si existe caché para un team/ejercicio/periodo
     *
     * @param int $team_id
     * @param int $ejercicio
     * @param int $periodo
     * @return bool
     */
    public static function has($team_id, $ejercicio, $periodo)
    {
        return Cache::has("cxc:{$team_id}:{$ejercicio}:{$periodo}") ||
               Cache::has("cxp:{$team_id}:{$ejercicio}:{$periodo}") ||
               Cache::has("indicadores:{$team_id}:{$ejercicio}:{$periodo}");
    }
}
