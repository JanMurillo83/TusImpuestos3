<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FASE 4: Servicio de Auto-corrección de Inconsistencias
 *
 * Detecta y corrige automáticamente inconsistencias en saldos contables
 */
class SaldosAutoCorrection
{
    /**
     * Ejecuta auto-corrección completa del sistema
     *
     * @param int|null $team_id Team específico o todos
     * @param bool $dryRun Si true, solo reporta sin corregir
     * @return array Resultados de la corrección
     */
    public static function runFullCorrection(?int $team_id = null, bool $dryRun = false): array
    {
        $results = [
            'started_at' => now()->toDateTimeString(),
            'dry_run' => $dryRun,
            'team_id' => $team_id,
            'corrections' => [],
            'errors' => [],
        ];

        // 1. Corregir saldos inconsistentes
        $results['corrections']['saldos_inconsistentes'] = self::fixInconsistentBalances($team_id, $dryRun);

        // 2. Corregir cuentas sin movimientos pero con saldo
        $results['corrections']['cuentas_sin_movimientos'] = self::fixAccountsWithoutMovements($team_id, $dryRun);

        // 3. Corregir jerarquías desactualizadas
        $results['corrections']['jerarquias'] = self::fixHierarchyTotals($team_id, $dryRun);

        // 4. Corregir timestamps faltantes
        $results['corrections']['timestamps'] = self::fixMissingTimestamps($team_id, $dryRun);

        // 5. Limpiar registros huérfanos
        $results['corrections']['huerfanos'] = self::cleanOrphanedRecords($team_id, $dryRun);

        $results['finished_at'] = now()->toDateTimeString();
        $results['duration_seconds'] = now()->diffInSeconds($results['started_at']);

        return $results;
    }

    /**
     * Corrige saldos inconsistentes entre saldos_reportes y auxiliares
     */
    public static function fixInconsistentBalances(?int $team_id = null, bool $dryRun = false): array
    {
        $inconsistencies = self::detectInconsistentBalances($team_id);

        $fixed = 0;
        $errors = 0;

        if (!$dryRun) {
            foreach ($inconsistencies as $issue) {
                try {
                    $saldosService = new SaldosService();
                    $success = $saldosService->actualizarCuentaIncremental(
                        $issue->team_id,
                        $issue->codigo,
                        $issue->ejercicio,
                        $issue->periodo
                    );

                    if ($success) {
                        $fixed++;

                        // Registrar en audit log
                        DB::table('saldos_audit_log')->insert([
                            'team_id' => $issue->team_id,
                            'codigo' => $issue->codigo,
                            'field_changed' => 'final',
                            'action' => 'auto_corrected',
                            'old_value' => $issue->saldo_actual,
                            'new_value' => $issue->saldo_correcto,
                            'difference' => $issue->diferencia,
                            'triggered_by' => 'auto_correction',
                            'metadata' => json_encode([
                                'ejercicio' => $issue->ejercicio,
                                'periodo' => $issue->periodo,
                                'auto_correction' => true
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Error auto-corrigiendo saldo', [
                        'issue' => $issue,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return [
            'detected' => count($inconsistencies),
            'fixed' => $fixed,
            'errors' => $errors,
            'details' => $dryRun ? $inconsistencies : [],
        ];
    }

    /**
     * Detecta saldos inconsistentes
     */
    protected static function detectInconsistentBalances(?int $team_id = null): array
    {
        // Obtener teams con sus ejercicio/periodo actuales
        $teams = DB::table('teams')
            ->select('id', 'ejercicio', 'periodo')
            ->when($team_id, fn($q) => $q->where('id', $team_id))
            ->get();

        $inconsistencies = [];

        foreach ($teams as $team) {
            $query = "
                SELECT
                    sr.team_id,
                    sr.codigo,
                    ? as ejercicio,
                    ? as periodo,
                    sr.final as saldo_actual,
                    COALESCE(SUM(a.cargo - a.abono), 0) as saldo_correcto,
                    ABS(sr.final - COALESCE(SUM(a.cargo - a.abono), 0)) as diferencia
                FROM saldos_reportes sr
                LEFT JOIN auxiliares a ON
                    a.team_id = sr.team_id AND
                    a.codigo = sr.codigo AND
                    a.a_ejercicio = ? AND
                    a.a_periodo = ?
                WHERE sr.team_id = ?
                GROUP BY sr.team_id, sr.codigo, sr.final
                HAVING ABS(diferencia) > 0.01
                ORDER BY diferencia DESC
                LIMIT 20
            ";

            $results = DB::select($query, [
                $team->ejercicio,
                $team->periodo,
                $team->ejercicio,
                $team->periodo,
                $team->id
            ]);

            $inconsistencies = array_merge($inconsistencies, $results);
        }

        return $inconsistencies;
    }

    /**
     * Corrige cuentas que tienen saldo pero no tienen movimientos
     */
    public static function fixAccountsWithoutMovements(?int $team_id = null, bool $dryRun = false): array
    {
        // Obtener teams con sus ejercicio/periodo actuales
        $teams = DB::table('teams')
            ->select('id', 'ejercicio', 'periodo')
            ->when($team_id, fn($q) => $q->where('id', $team_id))
            ->get();

        $totalAffected = 0;

        foreach ($teams as $team) {
            $query = DB::table('saldos_reportes as sr')
                ->select('sr.id', 'sr.team_id', 'sr.codigo', 'sr.final')
                ->leftJoin('auxiliares as a', function($join) use ($team) {
                    $join->on('a.team_id', '=', 'sr.team_id')
                         ->on('a.codigo', '=', 'sr.codigo')
                         ->where('a.a_ejercicio', '=', $team->ejercicio)
                         ->where('a.a_periodo', '=', $team->periodo);
                })
                ->whereNull('a.id')
                ->where('sr.final', '!=', 0)
                ->where('sr.team_id', $team->id);

            $affected = $query->count();
            $totalAffected += $affected;

            if (!$dryRun && $affected > 0) {
                // Poner en cero las cuentas sin movimientos
                DB::table('saldos_reportes')
                    ->whereIn('id', $query->pluck('id'))
                    ->update([
                        'anterior' => 0,
                        'cargos' => 0,
                        'abonos' => 0,
                        'final' => 0,
                        'updated_at' => now(),
                    ]);
            }
        }

        return [
            'detected' => $totalAffected,
            'fixed' => $dryRun ? 0 : $totalAffected,
        ];
    }

    /**
     * Corrige totales de jerarquía (cuentas padre)
     */
    public static function fixHierarchyTotals(?int $team_id = null, bool $dryRun = false): array
    {
        $parentAccounts = DB::table('cat_cuentas')
            ->select('team_id', 'codigo')
            ->when($team_id, fn($q) => $q->where('team_id', $team_id))
            ->where('tipo', 'Acumulativa')
            ->get();

        $fixed = 0;

        if (!$dryRun) {
            $saldosService = new SaldosService();

            foreach ($parentAccounts as $account) {
                try {
                    // Obtener ejercicio/periodo del team
                    $team = DB::table('teams')
                        ->select('ejercicio', 'periodo')
                        ->where('id', $account->team_id)
                        ->first();

                    if ($team) {
                        $saldosService->actualizarJerarquiaPadre(
                            $account->team_id,
                            (object)['codigo' => $account->codigo],
                            $team->ejercicio,
                            $team->periodo
                        );
                        $fixed++;
                    }
                } catch (\Exception $e) {
                    Log::error('Error corrigiendo jerarquía', [
                        'account' => $account,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return [
            'parent_accounts' => count($parentAccounts),
            'fixed' => $fixed,
        ];
    }

    /**
     * Corrige timestamps faltantes
     */
    public static function fixMissingTimestamps(?int $team_id = null, bool $dryRun = false): array
    {
        $tables = ['saldoscuentas', 'saldos_reportes'];
        $results = [];

        foreach ($tables as $table) {
            $count = DB::table($table)
                ->when($team_id, fn($q) => $q->where('team_id', $team_id))
                ->whereNull('updated_at')
                ->count();

            if (!$dryRun && $count > 0) {
                DB::table($table)
                    ->when($team_id, fn($q) => $q->where('team_id', $team_id))
                    ->whereNull('updated_at')
                    ->update(['updated_at' => now()]);
            }

            $results[$table] = [
                'detected' => $count,
                'fixed' => $dryRun ? 0 : $count,
            ];
        }

        return $results;
    }

    /**
     * Limpia registros huérfanos (sin team válido o cuenta no existente)
     */
    public static function cleanOrphanedRecords(?int $team_id = null, bool $dryRun = false): array
    {
        $results = [];

        // Saldos_reportes con cuentas inexistentes
        $orphanedQuery = DB::table('saldos_reportes as sr')
            ->leftJoin('cat_cuentas as cc', function($join) {
                $join->on('cc.team_id', '=', 'sr.team_id')
                     ->on('cc.codigo', '=', 'sr.codigo');
            })
            ->whereNull('cc.id')
            ->when($team_id, fn($q) => $q->where('sr.team_id', $team_id));

        $orphanedCount = $orphanedQuery->count();

        if (!$dryRun && $orphanedCount > 0) {
            DB::table('saldos_reportes')
                ->whereIn('id', $orphanedQuery->pluck('sr.id'))
                ->delete();
        }

        $results['saldos_reportes'] = [
            'detected' => $orphanedCount,
            'cleaned' => $dryRun ? 0 : $orphanedCount,
        ];

        return $results;
    }

    /**
     * Detecta problemas potenciales sin corregirlos
     */
    public static function detectIssues(?int $team_id = null): array
    {
        // Para accounts_without_movements, iterar por teams
        $teams = DB::table('teams')
            ->select('id', 'ejercicio', 'periodo')
            ->when($team_id, fn($q) => $q->where('id', $team_id))
            ->get();

        $accountsWithoutMovements = 0;
        foreach ($teams as $team) {
            $count = DB::table('saldos_reportes as sr')
                ->leftJoin('auxiliares as a', function($join) use ($team) {
                    $join->on('a.team_id', '=', 'sr.team_id')
                         ->on('a.codigo', '=', 'sr.codigo')
                         ->where('a.a_ejercicio', '=', $team->ejercicio)
                         ->where('a.a_periodo', '=', $team->periodo);
                })
                ->whereNull('a.id')
                ->where('sr.final', '!=', 0)
                ->where('sr.team_id', $team->id)
                ->count();

            $accountsWithoutMovements += $count;
        }

        return [
            'inconsistent_balances' => count(self::detectInconsistentBalances($team_id)),
            'accounts_without_movements' => $accountsWithoutMovements,
            'missing_timestamps' => DB::table('saldos_reportes')
                ->when($team_id, fn($q) => $q->where('team_id', $team_id))
                ->whereNull('updated_at')
                ->count(),
            'orphaned_records' => DB::table('saldos_reportes as sr')
                ->leftJoin('cat_cuentas as cc', function($join) {
                    $join->on('cc.team_id', '=', 'sr.team_id')
                         ->on('cc.codigo', '=', 'sr.codigo');
                })
                ->whereNull('cc.id')
                ->when($team_id, fn($q) => $q->where('sr.team_id', $team_id))
                ->count(),
        ];
    }
}
