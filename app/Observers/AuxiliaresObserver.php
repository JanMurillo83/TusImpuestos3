<?php

namespace App\Observers;

use App\Jobs\ActualizarSaldosCuentaJob;
use App\Models\Auxiliares;
use App\Services\SaldosCache;

/**
 * Observer para detectar cambios en Auxiliares y disparar actualización de saldos
 *
 * FASE 2: Event-Driven Architecture
 * En lugar de regenerar todos los saldos, solo actualiza las cuentas afectadas
 */
class AuxiliaresObserver
{
    /**
     * Handle the Auxiliares "created" event.
     *
     * Cuando se crea un nuevo auxiliar (movimiento contable):
     * 1. Disparar job para actualizar saldos de la cuenta afectada
     * 2. Invalidar caché del team afectado
     */
    public function created(Auxiliares $auxiliares): void
    {
        // FASE 2: Feature flag para habilitar actualización automática
        // Por defecto está DESHABILITADO para permitir testing gradual
        if (config('saldos.auto_update_enabled', false)) {
            // Disparar job async para actualizar saldos incrementalmente
            ActualizarSaldosCuentaJob::dispatch(
                $auxiliares->team_id,
                $auxiliares->codigo,
                $auxiliares->a_ejercicio,
                $auxiliares->a_periodo
            )->onQueue('saldos');

            // Invalidar caché inmediatamente para este periodo
            SaldosCache::invalidatePeriodo(
                $auxiliares->team_id,
                $auxiliares->a_ejercicio,
                $auxiliares->a_periodo
            );
        }
    }

    /**
     * Handle the Auxiliares "updated" event.
     *
     * Cuando se modifica un auxiliar existente:
     * 1. Si cambió el monto (cargo/abono), recalcular saldos
     * 2. Si cambió de cuenta (codigo), actualizar ambas cuentas (origen y destino)
     */
    public function updated(Auxiliares $auxiliares): void
    {
        if (config('saldos.auto_update_enabled', false)) {
            // Detectar si cambió información relevante para saldos
            if ($auxiliares->isDirty(['cargo', 'abono', 'codigo', 'a_ejercicio', 'a_periodo'])) {
                // Si cambió de cuenta, actualizar la cuenta anterior también
                if ($auxiliares->isDirty('codigo')) {
                    $codigo_anterior = $auxiliares->getOriginal('codigo');
                    ActualizarSaldosCuentaJob::dispatch(
                        $auxiliares->team_id,
                        $codigo_anterior,
                        $auxiliares->getOriginal('a_ejercicio'),
                        $auxiliares->getOriginal('a_periodo')
                    )->onQueue('saldos');
                }

                // Actualizar cuenta actual
                ActualizarSaldosCuentaJob::dispatch(
                    $auxiliares->team_id,
                    $auxiliares->codigo,
                    $auxiliares->a_ejercicio,
                    $auxiliares->a_periodo
                )->onQueue('saldos');

                // Invalidar caché
                SaldosCache::invalidatePeriodo(
                    $auxiliares->team_id,
                    $auxiliares->a_ejercicio,
                    $auxiliares->a_periodo
                );
            }
        }
    }

    /**
     * Handle the Auxiliares "deleted" event.
     *
     * Cuando se elimina un auxiliar:
     * 1. Recalcular saldos de la cuenta afectada
     * 2. Invalidar caché
     */
    public function deleted(Auxiliares $auxiliares): void
    {
        if (config('saldos.auto_update_enabled', false)) {
            // Recalcular saldos al eliminar movimiento
            ActualizarSaldosCuentaJob::dispatch(
                $auxiliares->team_id,
                $auxiliares->codigo,
                $auxiliares->a_ejercicio,
                $auxiliares->a_periodo
            )->onQueue('saldos');

            // Invalidar caché
            SaldosCache::invalidatePeriodo(
                $auxiliares->team_id,
                $auxiliares->a_ejercicio,
                $auxiliares->a_periodo
            );
        }
    }

    /**
     * Handle the Auxiliares "restored" event.
     */
    public function restored(Auxiliares $auxiliares): void
    {
        // Tratar restore como creación
        $this->created($auxiliares);
    }

    /**
     * Handle the Auxiliares "force deleted" event.
     */
    public function forceDeleted(Auxiliares $auxiliares): void
    {
        // Tratar force delete como eliminación normal
        $this->deleted($auxiliares);
    }
}
