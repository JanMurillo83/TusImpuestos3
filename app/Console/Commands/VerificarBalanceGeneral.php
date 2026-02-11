<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarBalanceGeneral extends Command
{
    protected $signature = 'balance:verificar {--team-id= : ID del team a verificar} {--periodo= : Periodo a verificar} {--ejercicio= : Ejercicio a verificar} {--all : Verificar todos los teams}';

    protected $description = 'Verifica si el Balance General está cuadrado (Activo = Pasivo + Capital)';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE BALANCE GENERAL ===');
        $this->newLine();

        $teamId = $this->option('team-id');
        $periodo = $this->option('periodo');
        $ejercicio = $this->option('ejercicio');
        $verifyAll = $this->option('all');

        if ($verifyAll) {
            return $this->verificarTodosLosTeams($periodo, $ejercicio);
        }

        if (!$teamId) {
            $this->error('❌ Debe proporcionar --team-id o usar --all');
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

        return $this->verificarBalance($teamId, $periodo, $ejercicio);
    }

    protected function verificarTodosLosTeams($periodo = null, $ejercicio = null)
    {
        $teams = DB::table('teams')->get();

        $this->info("Verificando {$teams->count()} teams...");
        $this->newLine();

        $cuadrados = 0;
        $descuadrados = 0;
        $problemas = [];

        foreach ($teams as $team) {
            $per = $periodo ?: $team->periodo;
            $eje = $ejercicio ?: $team->ejercicio;

            $resultado = $this->calcularBalance($team->id, $per, $eje, false);

            if ($resultado['cuadrado']) {
                $cuadrados++;
                $this->line("✅ Team {$team->id} ({$team->name}): CUADRADO");
            } else {
                $descuadrados++;
                $this->error("❌ Team {$team->id} ({$team->name}): DESCUADRADO por \${$resultado['diferencia_formatted']}");
                $problemas[] = [
                    'team_id' => $team->id,
                    'nombre' => $team->name,
                    'diferencia' => $resultado['diferencia'],
                    'activo' => $resultado['total_activo'],
                    'pasivo_capital' => $resultado['total_pasivo_capital'],
                ];
            }
        }

        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line("✅ Cuadrados: {$cuadrados}");
        $this->line("❌ Descuadrados: {$descuadrados}");

        if ($descuadrados > 0) {
            $this->newLine();
            $this->warn('Teams con problemas:');
            $this->table(
                ['Team ID', 'Nombre', 'Diferencia', 'Activo', 'Pasivo+Capital'],
                array_map(function($p) {
                    return [
                        $p['team_id'],
                        strlen($p['nombre']) > 30 ? substr($p['nombre'], 0, 27) . '...' : $p['nombre'],
                        '$' . number_format($p['diferencia'], 2),
                        '$' . number_format($p['activo'], 2),
                        '$' . number_format($p['pasivo_capital'], 2),
                    ];
                }, $problemas)
            );
        }

        return $descuadrados > 0 ? 1 : 0;
    }

    protected function verificarBalance($teamId, $periodo, $ejercicio)
    {
        $team = DB::table('teams')->where('id', $teamId)->first();

        $this->line("Team: {$team->name} (ID: {$teamId})");
        $this->line("Periodo: {$periodo} / Ejercicio: {$ejercicio}");
        $this->newLine();

        $resultado = $this->calcularBalance($teamId, $periodo, $ejercicio, true);

        if ($resultado['cuadrado']) {
            $this->info('✅ ¡BALANCE CUADRADO!');
            $this->newLine();
            $this->line('La ecuación contable se cumple:');
            $this->line("ACTIVO = PASIVO + CAPITAL");
            $this->line("\${$resultado['total_activo_formatted']} = \${$resultado['total_pasivo_capital_formatted']}");
        } else {
            $this->error('❌ BALANCE DESCUADRADO');
            $this->newLine();
            $this->line('Totales:');
            $this->line("  Total ACTIVO: \${$resultado['total_activo_formatted']}");
            $this->line("  Total PASIVO: \${$resultado['total_pasivo_formatted']}");
            $this->line("  Total CAPITAL: \${$resultado['total_capital_formatted']}");
            $this->line("  Total PASIVO + CAPITAL: \${$resultado['total_pasivo_capital_formatted']}");
            $this->newLine();
            $this->warn("  DIFERENCIA: \${$resultado['diferencia_formatted']} ⚠️");
            $this->newLine();
            $this->line('Para diagnosticar el problema, ejecute:');
            $this->line("  php artisan balance:diagnosticar --team-id={$teamId}");
        }

        if ($resultado['detalle']) {
            $this->newLine();
            $this->line('Detalle por sección:');
            $this->table(
                ['Sección', 'Código', 'Total'],
                [
                    ['Activo Corto Plazo', '100-149', '$' . number_format($resultado['activo_corto'], 2)],
                    ['Activo Largo Plazo', '150-199', '$' . number_format($resultado['activo_largo'], 2)],
                    ['Pasivo Corto Plazo', '200-249', '$' . number_format($resultado['pasivo_corto'], 2)],
                    ['Pasivo Largo Plazo', '250-299', '$' . number_format($resultado['pasivo_largo'], 2)],
                    ['Capital', '300-399', '$' . number_format($resultado['capital'], 2)],
                ]
            );
        }

        return $resultado['cuadrado'] ? 0 : 1;
    }

    protected function calcularBalance($teamId, $periodo, $ejercicio, $detalle = false)
    {
        // Obtener cuentas desde saldos_reportes (misma fuente que el reporte)
        $cuentas = DB::table('saldos_reportes')
            ->where('nivel', 1)
            ->where('team_id', $teamId)
            ->whereRaw('(anterior + cargos + abonos) != 0')
            ->get();

        $activo_corto = 0;
        $activo_largo = 0;
        $pasivo_corto = 0;
        $pasivo_largo = 0;
        $capital = 0;

        foreach ($cuentas as $cuenta) {
            $cod = intval(substr($cuenta->codigo, 0, 3));

            // Calcular saldo según naturaleza
            if ($cuenta->naturaleza == 'D') {
                $saldo = $cuenta->cargos - $cuenta->abonos;
            } else {
                $saldo = $cuenta->abonos - $cuenta->cargos;
            }
            $saldo += $cuenta->anterior;

            // Clasificar por rango de código
            if ($cod < 150) {
                $activo_corto += $saldo;
            } elseif ($cod >= 150 && $cod < 200) {
                $activo_largo += $saldo;
            } elseif ($cod >= 200 && $cod < 250) {
                $pasivo_corto += $saldo;
            } elseif ($cod >= 250 && $cod < 300) {
                $pasivo_largo += $saldo;
            } elseif ($cod >= 300 && $cod < 400) {
                $capital += $saldo;
            }
        }

        $total_activo = $activo_corto + $activo_largo;
        $total_pasivo = $pasivo_corto + $pasivo_largo;
        $total_capital = $capital;
        $total_pasivo_capital = $total_pasivo + $total_capital;

        $diferencia = abs($total_activo - $total_pasivo_capital);
        $cuadrado = $diferencia < 0.01; // Tolerancia de 1 centavo por redondeos

        return [
            'cuadrado' => $cuadrado,
            'total_activo' => $total_activo,
            'total_pasivo' => $total_pasivo,
            'total_capital' => $total_capital,
            'total_pasivo_capital' => $total_pasivo_capital,
            'diferencia' => $diferencia,
            'total_activo_formatted' => number_format($total_activo, 2),
            'total_pasivo_formatted' => number_format($total_pasivo, 2),
            'total_capital_formatted' => number_format($total_capital, 2),
            'total_pasivo_capital_formatted' => number_format($total_pasivo_capital, 2),
            'diferencia_formatted' => number_format($diferencia, 2),
            'detalle' => $detalle,
            'activo_corto' => $activo_corto,
            'activo_largo' => $activo_largo,
            'pasivo_corto' => $pasivo_corto,
            'pasivo_largo' => $pasivo_largo,
            'capital' => $capital,
        ];
    }
}
