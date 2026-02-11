<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticarBalanceGeneral extends Command
{
    protected $signature = 'balance:diagnosticar {--team-id= : ID del team a diagnosticar} {--periodo= : Periodo a diagnosticar} {--ejercicio= : Ejercicio a diagnosticar}';

    protected $description = 'Diagnostica las causas de descuadre en el Balance General';

    public function handle()
    {
        $this->info('=== DIAGNÓSTICO DE BALANCE GENERAL ===');
        $this->newLine();

        $teamId = $this->option('team-id');
        $periodo = $this->option('periodo');
        $ejercicio = $this->option('ejercicio');

        if (!$teamId) {
            $this->error('❌ Debe proporcionar --team-id');
            return 1;
        }

        // Obtener periodo y ejercicio del team si no se proporcionan
        if (!$periodo || !$ejercicio) {
            $team = DB::table('teams')->where('id', $teamId)->first();
            if (!$team) {
                $this->error("❌ Team {$teamId} no encontrado");
                return 1;
            }
            $periodo = $periodo ?: $team->periodo;
            $ejercicio = $ejercicio ?: $team->ejercicio;
        }

        return $this->diagnosticar($teamId, $periodo, $ejercicio);
    }

    protected function diagnosticar($teamId, $periodo, $ejercicio)
    {
        $team = DB::table('teams')->where('id', $teamId)->first();

        $this->line("Team: {$team->name} (ID: {$teamId})");
        $this->line("Periodo: {$periodo} / Ejercicio: {$ejercicio}");
        $this->newLine();

        $problemas = [];
        $advertencias = [];

        // 1. Verificar pólizas descuadradas
        $this->info('[1/5] Verificando pólizas descuadradas...');
        $polizasDescuadradas = $this->verificarPolizasDescuadradas($teamId, $periodo, $ejercicio);
        if ($polizasDescuadradas['count'] > 0) {
            $problemas[] = "❌ {$polizasDescuadradas['count']} pólizas descuadradas (cargos ≠ abonos)";
            $advertencias[] = [
                'tipo' => 'Pólizas Descuadradas',
                'cantidad' => $polizasDescuadradas['count'],
                'diferencia' => '$' . number_format($polizasDescuadradas['diferencia_total'], 2),
                'detalle' => $polizasDescuadradas['detalle']
            ];
        } else {
            $this->line("  ✓ Todas las pólizas están cuadradas");
        }

        // 2. Verificar saldos negativos incorrectos
        $this->info('[2/5] Verificando saldos negativos incorrectos...');
        $saldosNegativos = $this->verificarSaldosNegativos($teamId);
        if ($saldosNegativos['count'] > 0) {
            $advertencias[] = [
                'tipo' => 'Saldos Negativos Sospechosos',
                'cantidad' => $saldosNegativos['count'],
                'diferencia' => 'Revisar',
                'detalle' => $saldosNegativos['detalle']
            ];
            $this->warn("  ⚠️  {$saldosNegativos['count']} cuentas con saldos negativos sospechosos");
        } else {
            $this->line("  ✓ No se detectaron saldos negativos sospechosos");
        }

        // 3. Verificar auxiliares sin póliza
        $this->info('[3/5] Verificando auxiliares sin póliza...');
        $auxSinPoliza = $this->verificarAuxiliaresSinPoliza($teamId, $periodo, $ejercicio);
        if ($auxSinPoliza['count'] > 0) {
            $problemas[] = "⚠️  {$auxSinPoliza['count']} auxiliares sin póliza válida";
            $advertencias[] = [
                'tipo' => 'Auxiliares sin Póliza',
                'cantidad' => $auxSinPoliza['count'],
                'diferencia' => '$' . number_format($auxSinPoliza['total'], 2),
                'detalle' => 'Revisar cat_polizas_id'
            ];
        } else {
            $this->line("  ✓ Todos los auxiliares tienen póliza válida");
        }

        // 4. Verificar cuentas mal clasificadas
        $this->info('[4/5] Verificando clasificación de cuentas...');
        $cuentasMalClasificadas = $this->verificarClasificacionCuentas($teamId);
        if ($cuentasMalClasificadas['count'] > 0) {
            $advertencias[] = [
                'tipo' => 'Cuentas Mal Clasificadas',
                'cantidad' => $cuentasMalClasificadas['count'],
                'diferencia' => 'Revisar',
                'detalle' => $cuentasMalClasificadas['detalle']
            ];
            $this->warn("  ⚠️  {$cuentasMalClasificadas['count']} cuentas posiblemente mal clasificadas");
        } else {
            $this->line("  ✓ Clasificación de cuentas parece correcta");
        }

        // 5. Verificar integridad de saldos_reportes
        $this->info('[5/5] Verificando integridad de saldos_reportes...');
        $integridadSaldos = $this->verificarIntegridadSaldos($teamId);
        if (!$integridadSaldos['integro']) {
            $problemas[] = "❌ Inconsistencias en tabla saldos_reportes";
            $advertencias[] = [
                'tipo' => 'Integridad Saldos',
                'cantidad' => $integridadSaldos['inconsistencias'],
                'diferencia' => 'Actualizar',
                'detalle' => 'Ejecutar ContabilizaReporte()'
            ];
        } else {
            $this->line("  ✓ Integridad de saldos_reportes correcta");
        }

        $this->newLine();

        // Mostrar resumen de problemas
        if (count($problemas) > 0) {
            $this->error('=== PROBLEMAS DETECTADOS ===');
            foreach ($problemas as $problema) {
                $this->line("  $problema");
            }
            $this->newLine();
        }

        // Mostrar tabla de advertencias
        if (count($advertencias) > 0) {
            $this->warn('=== DETALLE DE PROBLEMAS ===');
            $this->table(
                ['Tipo de Problema', 'Cantidad', 'Impacto', 'Acción Recomendada'],
                array_map(function($adv) {
                    // Convertir detalle a string si es array
                    $detalle = $adv['detalle'];
                    if (is_array($detalle)) {
                        $detalle = 'Ver IDs: ' . implode(', ', array_slice($detalle, 0, 5));
                        if (count($adv['detalle']) > 5) {
                            $detalle .= '... (total: ' . count($adv['detalle']) . ')';
                        }
                    }

                    return [
                        $adv['tipo'],
                        $adv['cantidad'],
                        $adv['diferencia'],
                        $detalle
                    ];
                }, $advertencias)
            );
            $this->newLine();
        }

        // Recomendaciones
        $this->info('=== RECOMENDACIONES ===');

        if (count($problemas) === 0 && count($advertencias) === 0) {
            $this->line('✅ No se detectaron problemas evidentes.');
            $this->line('Si el balance sigue descuadrado, considere:');
            $this->line('  - Revisar saldos iniciales del ejercicio');
            $this->line('  - Verificar movimientos de ejercicios anteriores');
            $this->line('  - Revisar manualmente cuentas de resultados');
        } else {
            if (isset($advertencias[0]) && $advertencias[0]['tipo'] == 'Pólizas Descuadradas') {
                $this->line('1. Corregir pólizas descuadradas (cargos deben = abonos)');
                $this->line('   Revisar pólizas IDs: ' . implode(', ', array_slice($advertencias[0]['detalle'], 0, 10)));
            }
            if (count($advertencias) > 1) {
                $this->line('2. Actualizar saldos:');
                $this->line('   php artisan tinker');
                $this->line("   (new \\App\\Http\\Controllers\\ReportesController)->ContabilizaReporte($ejercicio, $periodo, $teamId)");
            }
            $this->line('3. Verificar nuevamente:');
            $this->line("   php artisan balance:verificar --team-id=$teamId");
        }

        return count($problemas) > 0 ? 1 : 0;
    }

    protected function verificarPolizasDescuadradas($teamId, $periodo, $ejercicio)
    {
        $polizasDescuadradas = DB::table('auxiliares')
            ->select('cat_polizas_id', DB::raw('SUM(cargo) as total_cargos'), DB::raw('SUM(abono) as total_abonos'))
            ->where('team_id', $teamId)
            ->groupBy('cat_polizas_id')
            ->havingRaw('ABS(SUM(cargo) - SUM(abono)) > 0.01')
            ->get();

        $diferencia_total = 0;
        $ids = [];

        foreach ($polizasDescuadradas as $poliza) {
            $diferencia_total += abs($poliza->total_cargos - $poliza->total_abonos);
            $ids[] = $poliza->cat_polizas_id;
        }

        return [
            'count' => $polizasDescuadradas->count(),
            'diferencia_total' => $diferencia_total,
            'detalle' => $ids
        ];
    }

    protected function verificarSaldosNegativos($teamId)
    {
        // Detectar cuentas de activo con saldo negativo (naturaleza deudora)
        $saldosNegativos = DB::table('saldos_reportes')
            ->where('team_id', $teamId)
            ->where('naturaleza', 'D')
            ->whereRaw('(anterior + cargos - abonos) < -0.01')
            ->get();

        $cuentas = [];
        foreach ($saldosNegativos as $cuenta) {
            if (intval(substr($cuenta->codigo, 0, 1)) < 2) { // Solo activos
                $cuentas[] = $cuenta->codigo . ' - ' . $cuenta->cuenta;
            }
        }

        return [
            'count' => count($cuentas),
            'detalle' => $cuentas
        ];
    }

    protected function verificarAuxiliaresSinPoliza($teamId, $periodo, $ejercicio)
    {
        $auxSinPoliza = DB::table('auxiliares')
            ->leftJoin('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
            ->where('auxiliares.team_id', $teamId)
            ->whereNull('cat_polizas.id')
            ->orWhere('auxiliares.cat_polizas_id', 0)
            ->orWhere('auxiliares.cat_polizas_id', null)
            ->get();

        $total = 0;
        foreach ($auxSinPoliza as $aux) {
            $total += $aux->cargo + $aux->abono;
        }

        return [
            'count' => $auxSinPoliza->count(),
            'total' => $total
        ];
    }

    protected function verificarClasificacionCuentas($teamId)
    {
        // Verificar cuentas con naturaleza incorrecta según su código
        $cuentasMalClasificadas = DB::table('cat_cuentas')
            ->where('team_id', $teamId)
            ->where(function($query) {
                // Activos (1xx) deben ser Deudoras
                $query->whereRaw("LEFT(codigo, 1) = '1' AND naturaleza != 'D'")
                    // Pasivos (2xx) deben ser Acreedoras
                    ->orWhereRaw("LEFT(codigo, 1) = '2' AND naturaleza != 'A'")
                    // Capital (3xx) debe ser Acreedor
                    ->orWhereRaw("LEFT(codigo, 1) = '3' AND naturaleza != 'A'");
            })
            ->get();

        $cuentas = [];
        foreach ($cuentasMalClasificadas as $cuenta) {
            $cuentas[] = $cuenta->codigo . ' - ' . $cuenta->nombre . ' (Naturaleza: ' . $cuenta->naturaleza . ')';
        }

        return [
            'count' => $cuentasMalClasificadas->count(),
            'detalle' => $cuentas
        ];
    }

    protected function verificarIntegridadSaldos($teamId)
    {
        $cuentasSaldos = DB::table('saldos_reportes')->where('team_id', $teamId)->count();
        $cuentasCatalogo = DB::table('cat_cuentas')->where('team_id', $teamId)->where('tipo', 'D')->count();

        $inconsistencias = abs($cuentasSaldos - $cuentasCatalogo);

        return [
            'integro' => $inconsistencias < 10, // Tolerancia de 10 cuentas
            'inconsistencias' => $inconsistencias
        ];
    }
}
