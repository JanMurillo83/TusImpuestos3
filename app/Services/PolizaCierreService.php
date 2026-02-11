<?php

namespace App\Services;

use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\ContaPeriodos;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PolizaCierreService
{
    /**
     * Genera la póliza de cierre para un ejercicio específico
     *
     * @param int $team_id ID de la empresa
     * @param int $ejercicio Año del ejercicio
     * @param int $periodo Periodo donde generar (12 o 13)
     * @param string $cuenta_resultado Código de cuenta para resultado del ejercicio
     * @return CatPolizas|null
     */
    public function generarPolizaCierre($team_id, $ejercicio, $periodo = 13, $cuenta_resultado = '304001')
    {
        DB::beginTransaction();

        try {
            // 1. Verificar si ya existe una póliza de cierre
            $polizaExistente = CatPolizas::where('team_id', $team_id)
                ->where('ejercicio', $ejercicio)
                ->where('periodo', $periodo)
                ->where('es_cierre', true)
                ->first();

            if ($polizaExistente) {
                throw new \Exception("Ya existe una póliza de cierre para el ejercicio {$ejercicio} periodo {$periodo}");
            }

            // 2. Obtener todas las cuentas de resultados (tipo 4 y 5: Ingresos y Egresos)
            $cuentasResultados = CatCuentas::where('team_id', $team_id)
                ->where(function($query) {
                    $query
                        ->where('tipo', 'D')
                        ->whereBetween(DB::raw("cast(substring(codigo,1,3) as UNSIGNED)"), [400,799]);
                })
                ->get();

            // 3. Calcular saldos de cada cuenta de resultados
            $movimientos = $this->calcularSaldosCuentasResultados($team_id, $ejercicio, $periodo, $cuentasResultados);

            if (empty($movimientos)) {
                throw new \Exception("No hay movimientos de cuentas de resultados para cerrar");
            }

            // 4. Calcular totales y resultado del ejercicio
            $totalCargos = array_sum(array_column($movimientos, 'cargo'));
            $totalAbonos = array_sum(array_column($movimientos, 'abono'));

            // Cargos = cancelación de Ingresos (naturaleza acreedora)
            // Abonos = cancelación de Egresos (naturaleza deudora)
            // Utilidad = Ingresos - Egresos = Cargos - Abonos
            $utilidad = $totalCargos - $totalAbonos; // Si es positivo: utilidad, si es negativo: pérdida

            // 5. Crear la póliza de cierre
            $folio = $this->obtenerSiguienteFolio($team_id, $ejercicio, $periodo, 'C');

            $poliza = CatPolizas::create([
                'tipo' => 'Dr',
                'folio' => $folio,
                'fecha' => Carbon::create($ejercicio, 12, 31),
                'concepto' => "PÓLIZA DE CIERRE DEL EJERCICIO {$ejercicio}",
                'cargos' => $totalCargos + ($utilidad < 0 ? abs($utilidad) : 0), // Cargos de ingresos + pérdida si hay
                'abonos' => $totalAbonos + ($utilidad > 0 ? $utilidad : 0), // Abonos de egresos + utilidad si hay
                'periodo' => $periodo,
                'ejercicio' => $ejercicio,
                'referencia' => "CIERRE-{$ejercicio}",
                'team_id' => $team_id,
                'es_cierre' => true,
            ]);

            // 6. Crear las partidas (auxiliares) para cancelar cuentas de resultados
            $nopartida = 1;
            foreach ($movimientos as $movimiento) {
                if ($movimiento['cargo'] != 0 || $movimiento['abono'] != 0) {
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $poliza->id,
                        'codigo' => $movimiento['codigo'],
                        'cuenta' => $movimiento['nombre'],
                        'concepto' => "Cancelación cuenta de resultados",
                        'cargo' => $movimiento['cargo'],
                        'abono' => $movimiento['abono'],
                        'nopartida' => $nopartida++,
                        'team_id' => $team_id,
                    ]);

                    // Insertar en tabla pivote
                    DB::table('auxiliares_cat_polizas')->insert([
                        'cat_polizas_id' => $poliza->id,
                        'auxiliares_id' => $aux->id,
                    ]);
                }
            }

            // 7. Crear partida para la cuenta de resultado del ejercicio
            $cuentaResultadoObj = CatCuentas::where('team_id', $team_id)
                ->where('codigo', $cuenta_resultado)
                ->first();

            if (!$cuentaResultadoObj) {
                throw new \Exception("No se encontró la cuenta de resultado del ejercicio: {$cuenta_resultado}");
            }

            $aux = Auxiliares::create([
                'cat_polizas_id' => $poliza->id,
                'codigo' => $cuenta_resultado,
                'cuenta' => $cuentaResultadoObj->nombre,
                'concepto' => $utilidad >= 0 ? "UTILIDAD DEL EJERCICIO {$ejercicio}" : "PÉRDIDA DEL EJERCICIO {$ejercicio}",
                'cargo' => $utilidad < 0 ? abs($utilidad) : 0, // Si hay pérdida, se carga la cuenta de capital
                'abono' => $utilidad > 0 ? $utilidad : 0, // Si hay utilidad, se abona la cuenta de capital
                'nopartida' => $nopartida,
                'team_id' => $team_id,
            ]);

            // Insertar en tabla pivote
            DB::table('auxiliares_cat_polizas')->insert([
                'cat_polizas_id' => $poliza->id,
                'auxiliares_id' => $aux->id,
            ]);

            DB::commit();

            return $poliza;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calcula los saldos de las cuentas de resultados considerando su naturaleza
     */
    private function calcularSaldosCuentasResultados($team_id, $ejercicio, $periodo, $cuentasResultados)
    {
        $movimientos = [];

        foreach ($cuentasResultados as $cuenta) {
            // Sumar cargos y abonos de la cuenta hasta el periodo indicado
            $saldos = Auxiliares::join('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
                ->where('auxiliares.team_id', $team_id)
                ->where('auxiliares.codigo', $cuenta->codigo)
                ->where('cat_polizas.ejercicio', $ejercicio)
                ->where('cat_polizas.periodo', '<=', $periodo)
                ->where('cat_polizas.es_cierre', false) // No incluir pólizas de cierre previas
                ->select(
                    DB::raw('SUM(auxiliares.cargo) as total_cargos'),
                    DB::raw('SUM(auxiliares.abono) as total_abonos')
                )
                ->first();

            $totalCargos = $saldos->total_cargos ?? 0;
            $totalAbonos = $saldos->total_abonos ?? 0;

            // Calcular saldo según la naturaleza de la cuenta
            // Naturaleza 'D' (Deudora - Egresos): saldo = cargos - abonos
            // Naturaleza 'A' (Acreedora - Ingresos): saldo = abonos - cargos
            if ($cuenta->naturaleza == 'D') {
                // Cuenta DEUDORA (Egresos 5xxx): saldo = cargos - abonos
                $saldo = $totalCargos - $totalAbonos;
            } else {
                // Cuenta ACREEDORA (Ingresos 4xxx): saldo = abonos - cargos
                $saldo = $totalAbonos - $totalCargos;
            }

            // Para cerrar la cuenta, hacemos el movimiento contrario al saldo
            // Si la cuenta tiene saldo, la cancelamos con el movimiento opuesto
            if ($saldo != 0) {
                // Si es cuenta DEUDORA (Egresos): tiene saldo deudor, se ABONA para cerrar
                // Si es cuenta ACREEDORA (Ingresos): tiene saldo acreedor, se CARGA para cerrar
                if ($cuenta->naturaleza == 'D') {
                    // Egresos: se abonan para cancelar
                    $cargo = 0;
                    $abono = abs($saldo);
                } else {
                    // Ingresos: se cargan para cancelar
                    $cargo = abs($saldo);
                    $abono = 0;
                }

                $movimientos[] = [
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'tipo' => $cuenta->tipo,
                    'naturaleza' => $cuenta->naturaleza,
                    'cargo' => $cargo,
                    'abono' => $abono,
                    'saldo_original' => $saldo,
                    'total_cargos' => $totalCargos,
                    'total_abonos' => $totalAbonos,
                ];
            }
        }

        return $movimientos;
    }

    /**
     * Obtiene el siguiente folio disponible para una póliza
     */
    private function obtenerSiguienteFolio($team_id, $ejercicio, $periodo, $tipo)
    {
        $ultimaPoliza = CatPolizas::where('team_id', $team_id)
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->where('tipo', $tipo)
            ->orderBy('folio', 'desc')
            ->first();

        return $ultimaPoliza ? $ultimaPoliza->folio + 1 : 1;
    }

    /**
     * Elimina una póliza de cierre existente
     */
    public function eliminarPolizaCierre($team_id, $ejercicio, $periodo = 12)
    {
        DB::beginTransaction();

        try {
            $poliza = CatPolizas::where('team_id', $team_id)
                ->where('ejercicio', $ejercicio)
                ->where('periodo', $periodo)
                ->where('es_cierre', true)
                ->first();

            if (!$poliza) {
                throw new \Exception("No existe póliza de cierre para eliminar");
            }

            // Eliminar registros de tabla pivote
            DB::table('auxiliares_cat_polizas')
                ->where('cat_polizas_id', $poliza->id)
                ->delete();

            // Eliminar auxiliares
            Auxiliares::where('cat_polizas_id', $poliza->id)->delete();

            // Eliminar póliza
            $poliza->delete();

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Habilita el periodo de ajuste (periodo 13) para una empresa/ejercicio
     */
    public function habilitarPeriodoAjuste($team_id, $ejercicio)
    {
        $periodo = ContaPeriodos::where('team_id', $team_id)
            ->where('ejercicio', $ejercicio)
            ->where('periodo', 13)
            ->first();

        if ($periodo) {
            $periodo->update(['es_ajuste' => true, 'estado' => 1]);
        } else {
            $periodo = ContaPeriodos::create([
                'periodo' => 13,
                'ejercicio' => $ejercicio,
                'estado' => 1,
                'team_id' => $team_id,
                'es_ajuste' => true,
            ]);
        }

        return $periodo;
    }

    /**
     * Cierra el periodo de ajuste
     */
    public function cerrarPeriodoAjuste($team_id, $ejercicio)
    {
        $periodo = ContaPeriodos::where('team_id', $team_id)
            ->where('ejercicio', $ejercicio)
            ->where('periodo', 13)
            ->where('es_ajuste', true)
            ->first();

        if ($periodo) {
            $periodo->update(['estado' => 0]);
            return true;
        }

        return false;
    }
}
