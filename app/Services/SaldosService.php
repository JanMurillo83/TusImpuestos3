<?php

namespace App\Services;

use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\Saldoscuentas;
use App\Models\SaldosReportes;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para actualización incremental de saldos contables
 *
 * FASE 2: Event-Driven Architecture
 * Implementa lógica de actualización incremental en lugar de regeneración completa
 */
class SaldosService
{
    /**
     * Actualizar saldos de una cuenta específica de manera incremental
     *
     * Este método actualiza:
     * 1. La cuenta específica en saldoscuentas y saldos_reportes
     * 2. Sus cuentas padre en la jerarquía (n1, n2, n3)
     * 3. Solo para el periodo afectado
     *
     * @param int $team_id ID del equipo/empresa
     * @param string $codigo Código de cuenta contable
     * @param int $ejercicio Año fiscal
     * @param int $periodo Periodo (1-12)
     * @return bool
     */
    public function actualizarCuentaIncremental(int $team_id, string $codigo, int $ejercicio, int $periodo): bool
    {
        DB::beginTransaction();

        try {
            // 1. Obtener información de la cuenta
            $cuenta = CatCuentas::where('team_id', $team_id)
                ->where('codigo', $codigo)
                ->first();

            if (!$cuenta) {
                \Log::warning("Cuenta no encontrada: {$codigo} para team {$team_id}");
                DB::rollBack();
                return false;
            }

            // 2. Actualizar saldoscuentas para esta cuenta
            $this->actualizarSaldoscuentas($team_id, $codigo, $ejercicio, $periodo);

            // 3. Actualizar saldos_reportes para esta cuenta
            $this->actualizarSaldosReportes($team_id, $cuenta, $ejercicio, $periodo);

            // 4. Actualizar jerarquía de cuentas padre
            $this->actualizarJerarquiaPadre($team_id, $cuenta, $ejercicio, $periodo);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error actualizando saldos incrementales', [
                'team_id' => $team_id,
                'codigo' => $codigo,
                'ejercicio' => $ejercicio,
                'periodo' => $periodo,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Actualizar saldoscuentas para una cuenta específica
     *
     * Calcula cargos y abonos del periodo desde auxiliares
     * y actualiza solo las columnas c{periodo} y a{periodo}
     */
    protected function actualizarSaldoscuentas(int $team_id, string $codigo, int $ejercicio, int $periodo): void
    {
        // Obtener cargos y abonos del periodo desde auxiliares
        $montos = Auxiliares::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->where('a_ejercicio', $ejercicio)
            ->where('a_periodo', $periodo)
            ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos'))
            ->first();

        if (!$montos) {
            return;
        }

        // Actualizar saldoscuentas
        $periodoC = 'c' . $periodo;
        $periodoA = 'a' . $periodo;

        Saldoscuentas::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->where('ejercicio', $ejercicio)
            ->update([
                $periodoC => $montos->cargos,
                $periodoA => $montos->abonos,
                'updated_at' => now()
            ]);
    }

    /**
     * Actualizar saldos_reportes para una cuenta específica
     *
     * Implementa la lógica corregida:
     * - Cuentas de Balance (1,2,3): Acumulado histórico total
     * - Cuentas de Resultados (4,5,6,7): Solo del ejercicio actual
     */
    protected function actualizarSaldosReportes(int $team_id, CatCuentas $cuenta, int $ejercicio, int $periodo): void
    {
        // Determinar nivel de la cuenta
        $nivel = 1;
        $n1 = substr($cuenta->codigo, 0, 3);
        $n2 = substr($cuenta->codigo, 3, 2);
        $n3 = substr($cuenta->codigo, 5, 3);
        $n2 = intval($n2);
        $n3 = intval($n3);
        if ($n2 > 0) $nivel++;
        if ($n3 > 0) $nivel++;

        // Determinar si es cuenta de Balance o Resultados
        $es_cuenta_balance = in_array(substr($cuenta->codigo, 0, 1), ['1', '2', '3']);

        // Calcular montos del periodo actual
        $montos = Auxiliares::where('codigo', $cuenta->codigo)
            ->where('a_ejercicio', $ejercicio)
            ->where('a_periodo', $periodo)
            ->where('team_id', $team_id)
            ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos'))
            ->first();

        // Calcular saldo inicial (anterior)
        if ($es_cuenta_balance) {
            // Balance: Todo el histórico antes del periodo actual
            $montos_anteriores = Auxiliares::where('codigo', $cuenta->codigo)
                ->where('team_id', $team_id)
                ->where(function($query) use ($ejercicio, $periodo) {
                    $query->where('a_ejercicio', '<', $ejercicio)
                        ->orWhere(function($q) use ($ejercicio, $periodo) {
                            $q->where('a_ejercicio', '=', $ejercicio)
                                ->where('a_periodo', '<', $periodo);
                        });
                })
                ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos'))
                ->first();
        } else {
            // Resultados: Solo periodos anteriores del mismo ejercicio
            $montos_anteriores = Auxiliares::where('codigo', $cuenta->codigo)
                ->where('a_ejercicio', $ejercicio)
                ->where('a_periodo', '<', $periodo)
                ->where('team_id', $team_id)
                ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos'))
                ->first();
        }

        // Calcular saldo inicial y final según naturaleza
        $inicial = 0;
        $final = 0;

        if ($cuenta->naturaleza == 'D') {
            $inicial = ($montos_anteriores->cargos ?? 0) - ($montos_anteriores->abonos ?? 0);
            $final = $inicial + ($montos->cargos ?? 0) - ($montos->abonos ?? 0);
        } else {
            $inicial = ($montos_anteriores->abonos ?? 0) - ($montos_anteriores->cargos ?? 0);
            $final = $inicial + ($montos->abonos ?? 0) - ($montos->cargos ?? 0);
        }

        // Actualizar o crear registro en saldos_reportes
        SaldosReportes::updateOrCreate(
            [
                'team_id' => $team_id,
                'codigo' => $cuenta->codigo,
            ],
            [
                'team_id' => $team_id,
                'codigo' => $cuenta->codigo,
                'cuenta' => $cuenta->nombre,
                'acumula' => $cuenta->acumula ?? 0,
                'naturaleza' => $cuenta->naturaleza,
                'anterior' => $inicial,
                'cargos' => $montos->cargos ?? 0,
                'abonos' => $montos->abonos ?? 0,
                'final' => $final,
                'nivel' => $nivel,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Actualizar jerarquía de cuentas padre
     *
     * Actualiza las cuentas n1, n2, n3 (acumulación)
     * para reflejar los cambios en la cuenta hija
     */
    protected function actualizarJerarquiaPadre(int $team_id, CatCuentas $cuenta, int $ejercicio, int $periodo): void
    {
        $periodoC = 'c' . $periodo;
        $periodoA = 'a' . $periodo;

        // Actualizar N3 (si existe)
        if ($cuenta->acumula && $cuenta->acumula != 0 && $cuenta->acumula != -1) {
            $this->actualizarCuentaPadre($team_id, $cuenta->acumula, $ejercicio, $periodo);
        }

        // Actualizar niveles superiores desde saldoscuentas
        // N3 -> N2
        $enes3 = DB::table('saldoscuentas')
            ->select('n3', DB::raw("SUM($periodoC) cargos, SUM($periodoA) abonos"))
            ->where('team_id', $team_id)
            ->where('ejercicio', $ejercicio)
            ->whereNotIn('n3', [0, '', -1])
            ->groupBy('n3')
            ->get();

        foreach ($enes3 as $ene3) {
            Saldoscuentas::where('codigo', $ene3->n3)
                ->where('team_id', $team_id)
                ->where('ejercicio', $ejercicio)
                ->update([
                    $periodoC => $ene3->cargos,
                    $periodoA => $ene3->abonos,
                    'updated_at' => now()
                ]);

            // Actualizar también en saldos_reportes
            $cuentaPadre = CatCuentas::where('codigo', $ene3->n3)
                ->where('team_id', $team_id)
                ->first();
            if ($cuentaPadre) {
                $this->actualizarSaldosReportes($team_id, $cuentaPadre, $ejercicio, $periodo);
            }
        }

        // N2 -> N1
        $enes2 = DB::table('saldoscuentas')
            ->select('n2', DB::raw("SUM($periodoC) cargos, SUM($periodoA) abonos"))
            ->where('team_id', $team_id)
            ->where('ejercicio', $ejercicio)
            ->whereNotIn('n2', [0, '', -1])
            ->groupBy('n2')
            ->get();

        foreach ($enes2 as $ene2) {
            Saldoscuentas::where('codigo', $ene2->n2)
                ->where('team_id', $team_id)
                ->where('ejercicio', $ejercicio)
                ->update([
                    $periodoC => $ene2->cargos,
                    $periodoA => $ene2->abonos,
                    'updated_at' => now()
                ]);

            // Actualizar también en saldos_reportes
            $cuentaPadre = CatCuentas::where('codigo', $ene2->n2)
                ->where('team_id', $team_id)
                ->first();
            if ($cuentaPadre) {
                $this->actualizarSaldosReportes($team_id, $cuentaPadre, $ejercicio, $periodo);
            }
        }

        // N1 (nivel más alto)
        $enes1 = DB::table('saldoscuentas')
            ->select('n1', DB::raw("SUM($periodoC) cargos, SUM($periodoA) abonos"))
            ->where('team_id', $team_id)
            ->where('ejercicio', $ejercicio)
            ->whereNotIn('n1', [0, '', -1])
            ->groupBy('n1')
            ->get();

        foreach ($enes1 as $ene1) {
            Saldoscuentas::where('codigo', $ene1->n1)
                ->where('team_id', $team_id)
                ->where('ejercicio', $ejercicio)
                ->update([
                    $periodoC => $ene1->cargos,
                    $periodoA => $ene1->abonos,
                    'updated_at' => now()
                ]);

            // Actualizar también en saldos_reportes
            $cuentaPadre = CatCuentas::where('codigo', $ene1->n1)
                ->where('team_id', $team_id)
                ->first();
            if ($cuentaPadre) {
                $this->actualizarSaldosReportes($team_id, $cuentaPadre, $ejercicio, $periodo);
            }
        }
    }

    /**
     * Actualizar una cuenta padre específica
     */
    protected function actualizarCuentaPadre(int $team_id, string $codigo, int $ejercicio, int $periodo): void
    {
        $cuenta = CatCuentas::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->first();

        if ($cuenta) {
            $this->actualizarSaldoscuentas($team_id, $codigo, $ejercicio, $periodo);
            $this->actualizarSaldosReportes($team_id, $cuenta, $ejercicio, $periodo);
        }
    }

    /**
     * Recalcular todos los saldos de un team (fallback para casos especiales)
     *
     * Usado cuando se necesita regeneración completa (ej: migración, corrección masiva)
     */
    public function recalcularTodosSaldos(int $team_id, int $ejercicio, int $periodo): void
    {
        // Este método mantiene la lógica del ContabilizaReporte original
        // como fallback para casos especiales
        app(\App\Http\Controllers\ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);
    }
}
