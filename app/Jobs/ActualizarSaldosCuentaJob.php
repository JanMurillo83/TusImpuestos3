<?php

namespace App\Jobs;

use App\Services\SaldosService;
use App\Services\SaldosMetrics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Job para actualización incremental de saldos contables
 *
 * FASE 2: Event-Driven Architecture
 * En lugar de regenerar TODOS los saldos, actualiza solo la cuenta afectada
 * y sus cuentas padre (jerarquía de acumulación)
 */
class ActualizarSaldosCuentaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Timeout en segundos
     */
    public $timeout = 120;

    /**
     * Team ID
     */
    protected int $team_id;

    /**
     * Código de cuenta a actualizar
     */
    protected string $codigo;

    /**
     * Ejercicio fiscal
     */
    protected int $ejercicio;

    /**
     * Periodo (1-12)
     */
    protected int $periodo;

    /**
     * Create a new job instance.
     *
     * @param int $team_id ID del equipo/empresa
     * @param string $codigo Código de cuenta contable
     * @param int $ejercicio Año fiscal
     * @param int $periodo Periodo (1-12)
     */
    public function __construct(int $team_id, string $codigo, int $ejercicio, int $periodo)
    {
        $this->team_id = $team_id;
        $this->codigo = $codigo;
        $this->ejercicio = $ejercicio;
        $this->periodo = $periodo;
    }

    /**
     * Execute the job.
     *
     * Actualiza saldos de manera incremental:
     * 1. Actualiza la cuenta específica
     * 2. Actualiza sus cuentas padre (acumula)
     * 3. Actualiza saldos_reportes
     * 4. NO recalcula todo el catálogo
     *
     * FASE 3: Incluye métricas, auditoría y tracking
     */
    public function handle(): void
    {
        $job_id = uniqid('job_', true);
        $startTime = microtime(true);

        // FASE 3: Registrar inicio del job
        DB::table('saldos_job_history')->insert([
            'job_id' => $job_id,
            'team_id' => $this->team_id,
            'codigo' => $this->codigo,
            'ejercicio' => $this->ejercicio,
            'periodo' => $this->periodo,
            'status' => 'processing',
            'queued_at' => now()->subSeconds(5), // Aproximado
            'started_at' => now(),
            'attempts' => $this->attempts(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $saldosService = new SaldosService();

            // Obtener valor anterior para auditoría
            $previousValue = DB::table('saldos_reportes')
                ->where('team_id', $this->team_id)
                ->where('codigo', $this->codigo)
                ->value('final');

            // Actualizar saldos de la cuenta afectada y su jerarquía
            $success = $saldosService->actualizarCuentaIncremental(
                $this->team_id,
                $this->codigo,
                $this->ejercicio,
                $this->periodo
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Obtener nuevo valor
            $newValue = DB::table('saldos_reportes')
                ->where('team_id', $this->team_id)
                ->where('codigo', $this->codigo)
                ->value('final');

            // FASE 3: Registrar métrica de performance
            SaldosMetrics::recordJobExecution(
                $this->team_id,
                $this->codigo,
                $duration,
                $success
            );

            // FASE 3: Auditoría de cambios
            if ($previousValue !== $newValue) {
                DB::table('saldos_audit_log')->insert([
                    'team_id' => $this->team_id,
                    'codigo' => $this->codigo,
                    'field_changed' => 'final',
                    'action' => 'updated',
                    'old_value' => $previousValue,
                    'new_value' => $newValue,
                    'difference' => $newValue - ($previousValue ?? 0),
                    'triggered_by' => 'job',
                    'metadata' => json_encode([
                        'job_id' => $job_id,
                        'ejercicio' => $this->ejercicio,
                        'periodo' => $this->periodo,
                        'duration_ms' => $duration
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // FASE 3: Actualizar job history
            DB::table('saldos_job_history')
                ->where('job_id', $job_id)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'duration_ms' => $duration,
                    'updated_at' => now(),
                ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // FASE 3: Registrar fallo
            DB::table('saldos_job_history')
                ->where('job_id', $job_id)
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'duration_ms' => $duration,
                    'error_message' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            SaldosMetrics::recordJobExecution(
                $this->team_id,
                $this->codigo,
                $duration,
                false
            );

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('ActualizarSaldosCuentaJob failed', [
            'team_id' => $this->team_id,
            'codigo' => $this->codigo,
            'ejercicio' => $this->ejercicio,
            'periodo' => $this->periodo,
            'error' => $exception->getMessage()
        ]);
    }
}
