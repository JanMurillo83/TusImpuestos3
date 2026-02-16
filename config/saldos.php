<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Saldos Contables
    |--------------------------------------------------------------------------
    |
    | FASE 2: Event-Driven Architecture
    |
    | Esta configuración controla el comportamiento de actualización de saldos
    | contables en el sistema.
    |
    */

    /**
     * Habilitar actualización automática de saldos (FASE 2)
     *
     * Cuando está en TRUE:
     * - Los saldos se actualizan automáticamente al crear/modificar/eliminar auxiliares
     * - Usa jobs async en queue 'saldos'
     * - Actualización incremental (solo cuentas afectadas)
     * - Mayor eficiencia pero requiere queue worker activo
     *
     * Cuando está en FALSE (por defecto):
     * - Los saldos se regeneran manualmente con ContabilizaReporte()
     * - Compatible con el flujo actual del sistema
     * - Recomendado durante período de testing
     *
     * Para habilitar FASE 2:
     * 1. Asegurar que queue worker esté corriendo: php artisan queue:work --queue=saldos
     * 2. Cambiar esta variable a true
     * 3. Monitorear logs: tail -f storage/logs/laravel.log
     * 4. Validar que saldos se actualizan correctamente
     */
    'auto_update_enabled' => env('SALDOS_AUTO_UPDATE', false),

    /**
     * TTL de caché en segundos (FASE 1)
     *
     * Controla cuánto tiempo se cachean los saldos antes de recalcular
     * Por defecto: 300 segundos (5 minutos)
     */
    'cache_ttl' => env('SALDOS_CACHE_TTL', 300),

    /**
     * Queue para jobs de actualización de saldos
     *
     * Queue dedicada para procesamiento de saldos
     * Permite priorización y monitoreo independiente
     */
    'queue_name' => env('SALDOS_QUEUE', 'saldos'),

    /**
     * Timeout para jobs de actualización (segundos)
     *
     * Tiempo máximo para procesar una actualización de saldo
     */
    'job_timeout' => env('SALDOS_JOB_TIMEOUT', 120),

    /**
     * Número de reintentos para jobs fallidos
     */
    'job_tries' => env('SALDOS_JOB_TRIES', 3),

    /**
     * Logging detallado de actualizaciones de saldos
     *
     * Útil para debugging durante implementación de FASE 2
     */
    'detailed_logging' => env('SALDOS_DETAILED_LOGGING', false),
];
