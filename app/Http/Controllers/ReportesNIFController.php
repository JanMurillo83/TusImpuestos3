<?php

namespace App\Http\Controllers;

use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\CatPolizas;
use App\Models\SaldosReportes;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportesNIFController extends Controller
{
    /**
     * Balance General conforme NIF B-6
     */
    public function balanceGeneralNIF(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        // Actualizar saldos antes de generar reporte
        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas clasificadas
        $activo_circulante = $this->obtenerCuentasPorRango($team_id, '100', '149');
        $activo_no_circulante = $this->obtenerCuentasPorRango($team_id, '150', '199');
        $pasivo_circulante = $this->obtenerCuentasPorRango($team_id, '200', '249');
        $pasivo_no_circulante = $this->obtenerCuentasPorRango($team_id, '250', '299');
        $capital = $this->obtenerCuentasPorRango($team_id, '300', '399');

        // Calcular totales
        $total_activo_circulante = $this->calcularTotal($activo_circulante);
        $total_activo_no_circulante = $this->calcularTotal($activo_no_circulante);
        $total_activo = $total_activo_circulante + $total_activo_no_circulante;

        $total_pasivo_circulante = $this->calcularTotal($pasivo_circulante);
        $total_pasivo_no_circulante = $this->calcularTotal($pasivo_no_circulante);
        $total_pasivo = $total_pasivo_circulante + $total_pasivo_no_circulante;

        $total_capital_base = $this->calcularTotal($capital);

        // Calcular resultado del ejercicio
        $resultado_ejercicio = $this->calcularResultadoEjercicio($team_id);
        $total_capital = $total_capital_base + $resultado_ejercicio;

        $total_pasivo_capital = $total_pasivo + $total_capital;

        // Valores del año anterior (periodo 12 del año anterior)
        $total_activo_circulante_ant = $this->calcularTotalAnterior($team_id, '100', '149', $ejercicio - 1);
        $total_activo_no_circulante_ant = $this->calcularTotalAnterior($team_id, '150', '199', $ejercicio - 1);
        $total_activo_ant = $total_activo_circulante_ant + $total_activo_no_circulante_ant;

        $total_pasivo_circulante_ant = $this->calcularTotalAnterior($team_id, '200', '249', $ejercicio - 1);
        $total_pasivo_no_circulante_ant = $this->calcularTotalAnterior($team_id, '250', '299', $ejercicio - 1);
        $total_pasivo_ant = $total_pasivo_circulante_ant + $total_pasivo_no_circulante_ant;

        $total_capital_base_ant = $this->calcularTotalAnterior($team_id, '300', '399', $ejercicio - 1);
        $resultado_ejercicio_ant = $this->calcularResultadoEjercicioAnterior($team_id, $ejercicio - 1);
        $total_capital_ant = $total_capital_base_ant + $resultado_ejercicio_ant;

        $total_pasivo_capital_ant = $total_pasivo_ant + $total_capital_ant;

        // Verificar si el balance está cuadrado
        $diferencia = abs($total_activo - $total_pasivo_capital);
        $balance_cuadrado = $diferencia < 0.01;

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'fecha_corte' => $this->obtenerFechaCorte($periodo, $ejercicio),
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,

            'activo_circulante' => $activo_circulante,
            'activo_no_circulante' => $activo_no_circulante,
            'pasivo_circulante' => $pasivo_circulante,
            'pasivo_no_circulante' => $pasivo_no_circulante,
            'capital' => $capital,

            'total_activo_circulante' => $total_activo_circulante,
            'total_activo_no_circulante' => $total_activo_no_circulante,
            'total_activo' => $total_activo,

            'total_pasivo_circulante' => $total_pasivo_circulante,
            'total_pasivo_no_circulante' => $total_pasivo_no_circulante,
            'total_pasivo' => $total_pasivo,

            'total_capital' => $total_capital,
            'resultado_ejercicio' => $resultado_ejercicio,
            'total_pasivo_capital' => $total_pasivo_capital,

            // Valores año anterior
            'total_activo_circulante_ant' => $total_activo_circulante_ant,
            'total_activo_no_circulante_ant' => $total_activo_no_circulante_ant,
            'total_activo_ant' => $total_activo_ant,
            'total_pasivo_circulante_ant' => $total_pasivo_circulante_ant,
            'total_pasivo_no_circulante_ant' => $total_pasivo_no_circulante_ant,
            'total_pasivo_ant' => $total_pasivo_ant,
            'total_capital_ant' => $total_capital_ant,
            'resultado_ejercicio_ant' => $resultado_ejercicio_ant,
            'total_pasivo_capital_ant' => $total_pasivo_capital_ant,

            'balance_cuadrado' => $balance_cuadrado,
        ];

        $pdf = SnappyPdf::loadView('Reportes/BalanceGeneralNIF', $data);
        $nombre = public_path('TMPCFDI/BalanceGeneralNIF_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOption('margin-top', '10mm');
        $pdf->setOption('margin-bottom', '10mm');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/BalanceGeneralNIF_' . $team_id . '.pdf';
    }

    /**
     * Balanza de Comprobación
     */
    public function balanzaComprobacion(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;
        $nivel_detalle = $request->nivel_detalle ?? 'mayor'; // Por defecto solo cuentas de mayor
        $mostrar_cuentas = $request->mostrar_cuentas ?? 'con_movimiento'; // Por defecto solo con movimientos

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas según el filtro
        $query = SaldosReportes::where('team_id', $team_id);

        // Filtrar por nivel si se solicita solo cuentas de mayor
        if ($nivel_detalle === 'mayor') {
            $query->where('nivel', 1);
        }

        // Filtrar por cuentas con movimientos o saldo si se solicita
        if ($mostrar_cuentas === 'con_movimiento') {
            $query->where(function($query) {
                $query->where('anterior', '!=', 0)
                      ->orWhere('cargos', '!=', 0)
                      ->orWhere('abonos', '!=', 0)
                      ->orWhere('final', '!=', 0);
            });
        }

        $cuentas = $query->orderBy('codigo')->get();

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'fecha_inicio' => '01/01/' . $ejercicio,
            'fecha_fin' => $this->obtenerFechaCorte($periodo, $ejercicio),
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'cuentas' => $cuentas,
        ];

        $pdf = SnappyPdf::loadView('Reportes/BalanzaComprobacion', $data);
        $nombre = public_path('TMPCFDI/BalanzaComprobacion_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOrientation('landscape');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/BalanzaComprobacion_' . $team_id . '.pdf';
    }

    /**
     * Balanza de Comprobación Simplificada (PDF)
     */
    public function balanzaSimplificada(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;
        $nivel_detalle = $request->nivel_detalle ?? 'mayor';
        $mostrar_cuentas = $request->mostrar_cuentas ?? 'con_movimiento';

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas según los filtros
        $query = SaldosReportes::where('team_id', $team_id);

        // Filtrar por nivel
        if ($nivel_detalle === 'mayor') {
            $query->where('nivel', 1);
        }

        // Filtrar por cuentas con movimientos o saldo
        if ($mostrar_cuentas === 'con_movimiento') {
            $query->where(function($query) {
                $query->where('anterior', '!=', 0)
                      ->orWhere('cargos', '!=', 0)
                      ->orWhere('abonos', '!=', 0)
                      ->orWhere('final', '!=', 0);
            });
        }

        $cuentas = $query->orderBy('codigo')->get();

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'fecha_inicio' => '01/01/' . $ejercicio,
            'fecha_fin' => $this->obtenerFechaCorte($periodo, $ejercicio),
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'cuentas' => $cuentas,
        ];

        $pdf = SnappyPdf::loadView('Reportes/BalanzaSimplificada', $data);
        $nombre = public_path('TMPCFDI/BalanzaSimplificada_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/BalanzaSimplificada_' . $team_id . '.pdf';
    }

    /**
     * Libro Mayor
     */
    public function libroMayor(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $cuenta_inicio = $request->cuenta_inicio;
        $cuenta_fin = $request->cuenta_fin;
        $periodo_inicio = $request->periodo_inicio;
        $ejercicio_inicio = $request->ejercicio_inicio;
        $periodo_fin = $request->periodo_fin;
        $ejercicio_fin = $request->ejercicio_fin;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas en el rango especificado
        $cuentas_query = DB::table('cat_cuentas')
            ->where('team_id', $team_id)
            ->where('codigo', '>=', $cuenta_inicio)
            ->where('codigo', '<=', $cuenta_fin)
            ->orderBy('codigo');

        $cuentas_data = [];

        foreach ($cuentas_query->get() as $cuenta) {
            // Calcular saldo inicial
            $saldo_inicial = $this->calcularSaldoInicialCuenta($team_id, $cuenta->codigo, $cuenta->naturaleza, $ejercicio_inicio, $periodo_inicio);

            // Calcular totales de movimientos del periodo
            $result = Auxiliares::where('team_id', $team_id)
                ->where('codigo', $cuenta->codigo)
                ->where(function($query) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                    if ($ejercicio_inicio == $ejercicio_fin) {
                        $query->where('a_ejercicio', '=', $ejercicio_inicio)
                              ->where('a_periodo', '>=', $periodo_inicio)
                              ->where('a_periodo', '<=', $periodo_fin);
                    } else {
                        $query->where(function($q) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                            $q->where(function($subq) use ($ejercicio_inicio, $periodo_inicio) {
                                $subq->where('a_ejercicio', '=', $ejercicio_inicio)
                                     ->where('a_periodo', '>=', $periodo_inicio);
                            })
                            ->orWhere(function($subq) use ($ejercicio_inicio, $ejercicio_fin) {
                                $subq->where('a_ejercicio', '>', $ejercicio_inicio)
                                     ->where('a_ejercicio', '<', $ejercicio_fin);
                            })
                            ->orWhere(function($subq) use ($ejercicio_fin, $periodo_fin) {
                                $subq->where('a_ejercicio', '=', $ejercicio_fin)
                                     ->where('a_periodo', '<=', $periodo_fin);
                            });
                        });
                    }
                })
                ->selectRaw('SUM(cargo) as total_cargo, SUM(abono) as total_abono')
                ->first();

            $total_cargos = $result->total_cargo ?? 0;
            $total_abonos = $result->total_abono ?? 0;

            // Calcular saldo final considerando naturaleza
            if ($cuenta->naturaleza == 'A') {
                $saldo_final = $saldo_inicial + ($total_abonos - $total_cargos);
            } else {
                $saldo_final = $saldo_inicial + ($total_cargos - $total_abonos);
            }

            // Solo incluir cuentas con saldo inicial, movimientos o saldo final
            if ($saldo_inicial != 0 || $total_cargos != 0 || $total_abonos != 0 || $saldo_final != 0) {
                $cuentas_data[] = (object)[
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'naturaleza' => $cuenta->naturaleza,
                    'saldo_inicial' => $saldo_inicial,
                    'total_cargos' => $total_cargos,
                    'total_abonos' => $total_abonos,
                    'saldo_final' => $saldo_final
                ];
            }
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo_inicio' => $periodo_inicio,
            'ejercicio_inicio' => $ejercicio_inicio,
            'periodo_fin' => $periodo_fin,
            'ejercicio_fin' => $ejercicio_fin,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'cuentas' => $cuentas_data,
        ];

        $pdf = SnappyPdf::loadView('Reportes/LibroMayor', $data);
        $nombre = public_path('TMPCFDI/LibroMayor_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/LibroMayor_' . $team_id . '.pdf';
    }

    /**
     * Balance General Comparativo
     */
    public function balanceGeneralComparativo(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo1 = $request->periodo1;
        $ejercicio1 = $request->ejercicio1;
        $periodo2 = $request->periodo2;
        $ejercicio2 = $request->ejercicio2;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Contabilizar ambos periodos
        app(ReportesController::class)->ContabilizaReporte($ejercicio1, $periodo1, $team_id);
        $saldos_periodo1 = SaldosReportes::where('team_id', $team_id)->get()->keyBy('codigo');

        app(ReportesController::class)->ContabilizaReporte($ejercicio2, $periodo2, $team_id);
        $saldos_periodo2 = SaldosReportes::where('team_id', $team_id)->get()->keyBy('codigo');

        // Obtener todas las cuentas únicas de ambos periodos
        $codigos = $saldos_periodo1->keys()->merge($saldos_periodo2->keys())->unique();

        $cuentas_comparativo = [];
        foreach ($codigos as $codigo) {
            $cuenta1 = $saldos_periodo1->get($codigo);
            $cuenta2 = $saldos_periodo2->get($codigo);

            $saldo1 = $cuenta1 ? $cuenta1->final : 0;
            $saldo2 = $cuenta2 ? $cuenta2->final : 0;
            $variacion = $saldo2 - $saldo1;
            $variacion_porcentaje = $saldo1 != 0 ? ($variacion / abs($saldo1)) * 100 : 0;

            if ($saldo1 != 0 || $saldo2 != 0) {
                $cuentas_comparativo[] = (object)[
                    'codigo' => $codigo,
                    'nombre' => $cuenta1 ? $cuenta1->nombre : ($cuenta2 ? $cuenta2->nombre : ''),
                    'saldo1' => $saldo1,
                    'saldo2' => $saldo2,
                    'variacion' => $variacion,
                    'variacion_porcentaje' => $variacion_porcentaje
                ];
            }
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo1' => $periodo1,
            'ejercicio1' => $ejercicio1,
            'periodo2' => $periodo2,
            'ejercicio2' => $ejercicio2,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'cuentas' => $cuentas_comparativo,
        ];

        $pdf = SnappyPdf::loadView('Reportes/BalanceComparativo', $data);
        $nombre = public_path('TMPCFDI/BalanceComparativo_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOrientation('landscape');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/BalanceComparativo_' . $team_id . '.pdf';
    }

    /**
     * Razones Financieras
     */
    public function razonesFinancieras(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener valores clave para cálculos
        $activo_circulante = $this->obtenerSaldoRango($team_id, '101', '115');
        $activo_total = $this->obtenerSaldoRango($team_id, '100', '199');
        $pasivo_circulante = $this->obtenerSaldoRango($team_id, '201', '215');
        $pasivo_total = $this->obtenerSaldoRango($team_id, '200', '299');
        $capital_contable = $this->obtenerSaldoRango($team_id, '300', '399');
        $ventas_netas = $this->obtenerSaldoRango($team_id, '401', '499');
        $utilidad_neta = $this->calcularResultadoEjercicio($team_id);

        // Calcular razones financieras
        $razones = [
            'liquidez' => [
                'razon_circulante' => $pasivo_circulante != 0 ? $activo_circulante / abs($pasivo_circulante) : 0,
                'capital_trabajo' => $activo_circulante + $pasivo_circulante,
            ],
            'endeudamiento' => [
                'deuda_total' => $activo_total != 0 ? abs($pasivo_total) / $activo_total : 0,
                'deuda_capital' => $capital_contable != 0 ? abs($pasivo_total) / $capital_contable : 0,
            ],
            'rentabilidad' => [
                'margen_neto' => $ventas_netas != 0 ? ($utilidad_neta / $ventas_netas) * 100 : 0,
                'roa' => $activo_total != 0 ? ($utilidad_neta / $activo_total) * 100 : 0,
                'roe' => $capital_contable != 0 ? ($utilidad_neta / $capital_contable) * 100 : 0,
            ]
        ];

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'razones' => $razones,
            'valores_base' => [
                'activo_circulante' => $activo_circulante,
                'activo_total' => $activo_total,
                'pasivo_circulante' => $pasivo_circulante,
                'pasivo_total' => $pasivo_total,
                'capital_contable' => $capital_contable,
                'ventas_netas' => $ventas_netas,
                'utilidad_neta' => $utilidad_neta,
            ]
        ];

        $pdf = SnappyPdf::loadView('Reportes/RazonesFinancieras', $data);
        $nombre = public_path('TMPCFDI/RazonesFinancieras_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/RazonesFinancieras_' . $team_id . '.pdf';
    }

    private function obtenerSaldoRango($team_id, $inicio, $fin)
    {
        return SaldosReportes::where('team_id', $team_id)
            ->where('codigo', '>=', $inicio)
            ->where('codigo', '<=', $fin)
            ->sum('final');
    }

    /**
     * Diario General
     */
    public function diarioGeneral(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo_inicio = $request->periodo_inicio;
        $ejercicio_inicio = $request->ejercicio_inicio;
        $periodo_fin = $request->periodo_fin;
        $ejercicio_fin = $request->ejercicio_fin;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener todas las pólizas del rango de fechas
        $polizas = CatPolizas::where('team_id', $team_id)
            ->where(function($query) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                if ($ejercicio_inicio == $ejercicio_fin) {
                    $query->where('ejercicio', '=', $ejercicio_inicio)
                          ->where('periodo', '>=', $periodo_inicio)
                          ->where('periodo', '<=', $periodo_fin);
                } else {
                    $query->where(function($q) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                        $q->where(function($subq) use ($ejercicio_inicio, $periodo_inicio) {
                            $subq->where('ejercicio', '=', $ejercicio_inicio)
                                 ->where('periodo', '>=', $periodo_inicio);
                        })
                        ->orWhere(function($subq) use ($ejercicio_inicio, $ejercicio_fin) {
                            $subq->where('ejercicio', '>', $ejercicio_inicio)
                                 ->where('ejercicio', '<', $ejercicio_fin);
                        })
                        ->orWhere(function($subq) use ($ejercicio_fin, $periodo_fin) {
                            $subq->where('ejercicio', '=', $ejercicio_fin)
                                 ->where('periodo', '<=', $periodo_fin);
                        });
                    });
                }
            })
            ->orderBy('ejercicio')
            ->orderBy('periodo')
            ->orderBy('fecha')
            ->orderBy('folio')
            ->get();

        $polizas_data = [];
        foreach ($polizas as $poliza) {
            // Obtener auxiliares de esta póliza
            $auxiliares = Auxiliares::where('team_id', $team_id)
                ->where('cat_polizas_id', $poliza->id)
                ->orderBy('codigo')
                ->get();

            $total_cargo = $auxiliares->sum('cargo');
            $total_abono = $auxiliares->sum('abono');

            $polizas_data[] = (object)[
                'folio' => $poliza->folio,
                'tipo' => $poliza->tipo,
                'fecha' => $poliza->fecha,
                'periodo' => $poliza->periodo,
                'ejercicio' => $poliza->ejercicio,
                'concepto' => $poliza->concepto,
                'referencia' => $poliza->referencia,
                'auxiliares' => $auxiliares,
                'total_cargo' => $total_cargo,
                'total_abono' => $total_abono,
                'descuadrada' => abs($total_cargo - $total_abono) > 0.01
            ];
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo_inicio' => $periodo_inicio,
            'ejercicio_inicio' => $ejercicio_inicio,
            'periodo_fin' => $periodo_fin,
            'ejercicio_fin' => $ejercicio_fin,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'polizas' => $polizas_data,
        ];

        $pdf = SnappyPdf::loadView('Reportes/DiarioGeneral', $data);
        $nombre = public_path('TMPCFDI/DiarioGeneral_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/DiarioGeneral_' . $team_id . '.pdf';
    }

    /**
     * Pólizas Descuadradas
     */
    public function polizasDescuadradas(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener todas las pólizas del periodo
        $polizas = CatPolizas::where('team_id', $team_id)
            ->where('periodo', $periodo)
            ->where('ejercicio', $ejercicio)
            ->orderBy('folio')
            ->get();

        $polizas_descuadradas = [];
        foreach ($polizas as $poliza) {
            $auxiliares = Auxiliares::where('team_id', $team_id)
                ->where('cat_polizas_id', $poliza->id)
                ->get();

            $total_cargo = $auxiliares->sum('cargo');
            $total_abono = $auxiliares->sum('abono');
            $diferencia = $total_cargo - $total_abono;

            // Considerar descuadrada si hay diferencia mayor a 1 centavo
            if (abs($diferencia) > 0.01) {
                $polizas_descuadradas[] = (object)[
                    'folio' => $poliza->folio,
                    'tipo' => $poliza->tipo,
                    'fecha' => $poliza->fecha,
                    'concepto' => $poliza->concepto,
                    'total_cargo' => $total_cargo,
                    'total_abono' => $total_abono,
                    'diferencia' => $diferencia
                ];
            }
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'polizas' => $polizas_descuadradas,
            'total_polizas' => count($polizas),
            'total_descuadradas' => count($polizas_descuadradas),
        ];

        $pdf = SnappyPdf::loadView('Reportes/PolizasDescuadradas', $data);
        $nombre = public_path('TMPCFDI/PólizasDescuadradas_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/PólizasDescuadradas_' . $team_id . '.pdf';
    }

    /**
     * Estado de Resultados Comparativo
     */
    public function estadoResultadosComparativo(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo1 = $request->periodo1;
        $ejercicio1 = $request->ejercicio1;
        $periodo2 = $request->periodo2;
        $ejercicio2 = $request->ejercicio2;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Contabilizar ambos periodos
        app(ReportesController::class)->ContabilizaReporte($ejercicio1, $periodo1, $team_id);
        $saldos_periodo1 = SaldosReportes::where('team_id', $team_id)
            ->where('codigo', '>=', '400')
            ->where('codigo', '<=', '899')
            ->get()
            ->keyBy('codigo');

        app(ReportesController::class)->ContabilizaReporte($ejercicio2, $periodo2, $team_id);
        $saldos_periodo2 = SaldosReportes::where('team_id', $team_id)
            ->where('codigo', '>=', '400')
            ->where('codigo', '<=', '899')
            ->get()
            ->keyBy('codigo');

        $codigos = $saldos_periodo1->keys()->merge($saldos_periodo2->keys())->unique();

        $cuentas_comparativo = [];
        foreach ($codigos as $codigo) {
            $cuenta1 = $saldos_periodo1->get($codigo);
            $cuenta2 = $saldos_periodo2->get($codigo);

            $saldo1 = $cuenta1 ? $cuenta1->saldo_acumulado : 0;
            $saldo2 = $cuenta2 ? $cuenta2->saldo_acumulado : 0;
            $variacion = $saldo2 - $saldo1;
            $variacion_porcentaje = $saldo1 != 0 ? ($variacion / abs($saldo1)) * 100 : 0;

            if ($saldo1 != 0 || $saldo2 != 0) {
                $cuentas_comparativo[] = (object)[
                    'codigo' => $codigo,
                    'nombre' => $cuenta1 ? $cuenta1->nombre : ($cuenta2 ? $cuenta2->nombre : ''),
                    'saldo1' => $saldo1,
                    'saldo2' => $saldo2,
                    'variacion' => $variacion,
                    'variacion_porcentaje' => $variacion_porcentaje
                ];
            }
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo1' => $periodo1,
            'ejercicio1' => $ejercicio1,
            'periodo2' => $periodo2,
            'ejercicio2' => $ejercicio2,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'cuentas' => $cuentas_comparativo,
        ];

        $pdf = SnappyPdf::loadView('Reportes/EstadoResultadosComparativo', $data);
        $nombre = public_path('TMPCFDI/EstadoResultadosComparativo_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOrientation('landscape');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/EstadoResultadosComparativo_' . $team_id . '.pdf';
    }

    /**
     * Antigüedad de Saldos
     */
    public function antiguedadSaldos(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $tipo = $request->tipo; // 'cobrar' o 'pagar'
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Determinar rango de cuentas según tipo
        if ($tipo == 'cobrar') {
            $cuenta_inicio = '105';
            $cuenta_fin = '105999';
            $titulo = 'ANTIGÜEDAD DE SALDOS - CUENTAS POR COBRAR';
        } else {
            $cuenta_inicio = '201';
            $cuenta_fin = '201999';
            $titulo = 'ANTIGÜEDAD DE SALDOS - CUENTAS POR PAGAR';
        }

        // Obtener cuentas con saldo
        $cuentas = SaldosReportes::where('team_id', $team_id)
            ->where('codigo', '>=', $cuenta_inicio)
            ->where('codigo', '<=', $cuenta_fin)
            ->where('final', '!=', 0)
            ->orderBy('codigo')
            ->get();

        $cuentas_antigüedad = [];
        foreach ($cuentas as $cuenta) {
            // Analizar movimientos por antigüedad
            $movimientos = Auxiliares::where('team_id', $team_id)
                ->where('codigo', $cuenta->codigo)
                ->where('a_ejercicio', '<=', $ejercicio)
                ->where('a_periodo', '<=', $periodo)
                ->orderBy('a_ejercicio')
                ->orderBy('a_periodo')
                ->get();

            // Calcular saldos por antigüedad (0-30, 31-60, 61-90, 91-120, >120 días)
            $fecha_corte = Carbon::create($ejercicio, $periodo, 1)->endOfMonth();

            $vencido_0_30 = 0;
            $vencido_31_60 = 0;
            $vencido_61_90 = 0;
            $vencido_91_120 = 0;
            $vencido_mas_120 = 0;

            foreach ($movimientos as $mov) {
                $fecha_mov = Carbon::create($mov->a_ejercicio, $mov->a_periodo, 1);
                $dias = $fecha_corte->diffInDays($fecha_mov);
                $saldo_mov = $tipo == 'cobrar' ? ($mov->cargo - $mov->abono) : ($mov->abono - $mov->cargo);

                if ($dias <= 30) {
                    $vencido_0_30 += $saldo_mov;
                } elseif ($dias <= 60) {
                    $vencido_31_60 += $saldo_mov;
                } elseif ($dias <= 90) {
                    $vencido_61_90 += $saldo_mov;
                } elseif ($dias <= 120) {
                    $vencido_91_120 += $saldo_mov;
                } else {
                    $vencido_mas_120 += $saldo_mov;
                }
            }

            $cuentas_antigüedad[] = (object)[
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'saldo_total' => abs($cuenta->final),
                'vencido_0_30' => $vencido_0_30,
                'vencido_31_60' => $vencido_31_60,
                'vencido_61_90' => $vencido_61_90,
                'vencido_91_120' => $vencido_91_120,
                'vencido_mas_120' => $vencido_mas_120,
            ];
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'titulo' => $titulo,
            'cuentas' => $cuentas_antigüedad,
        ];

        $pdf = SnappyPdf::loadView('Reportes/AntiguedadSaldos', $data);
        $tipo_nombre = $tipo == 'cobrar' ? 'CuentasPorCobrar' : 'CuentasPorPagar';
        $nombre = public_path('TMPCFDI/AntiguedadSaldos_' . $tipo_nombre . '_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOrientation('landscape');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/AntiguedadSaldos_' . $tipo_nombre . '_' . $team_id . '.pdf';
    }

    /**
     * Reporte de IVA (Declaración Mensual)
     */
    public function reporteIVA(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener CFDIs del periodo usando ejercicio y periodo
        $cfdis_emitidos = Almacencfdis::where('team_id', $team_id)
            ->where('xml_type', 'Emitidos')
            ->where('TipoDeComprobante', 'I')
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->get();

        $cfdis_recibidos = Almacencfdis::where('team_id', $team_id)
            ->where('xml_type', 'Recibidos')
            ->where('TipoDeComprobante', 'I')
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->get();

        // Usar los campos ya calculados en la tabla almacencfdis
        $iva_trasladado = 0;
        $iva_retenido_cobrado = 0;
        $total_ventas = 0;

        foreach ($cfdis_emitidos as $cfdi) {
            $total_ventas += floatval($cfdi->SubTotal ?? 0);
            $iva_trasladado += floatval($cfdi->TotalImpuestosTrasladados ?? 0);
            $iva_retenido_cobrado += floatval($cfdi->TotalImpuestosRetenidos ?? 0);
        }

        $iva_acreditable = 0;
        $iva_retenido_pagado = 0;
        $total_compras = 0;

        foreach ($cfdis_recibidos as $cfdi) {
            $total_compras += floatval($cfdi->SubTotal ?? 0);
            $iva_acreditable += floatval($cfdi->TotalImpuestosTrasladados ?? 0);
            $iva_retenido_pagado += floatval($cfdi->TotalImpuestosRetenidos ?? 0);
        }

        $iva_a_cargo = $iva_trasladado - $iva_acreditable - $iva_retenido_cobrado + $iva_retenido_pagado;

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'total_ventas' => $total_ventas,
            'iva_trasladado' => $iva_trasladado,
            'iva_retenido_cobrado' => $iva_retenido_cobrado,
            'total_compras' => $total_compras,
            'iva_acreditable' => $iva_acreditable,
            'iva_retenido_pagado' => $iva_retenido_pagado,
            'iva_a_cargo' => $iva_a_cargo,
            'iva_a_favor' => $iva_a_cargo < 0 ? abs($iva_a_cargo) : 0,
            'total_cfdis_emitidos' => count($cfdis_emitidos),
            'total_cfdis_recibidos' => count($cfdis_recibidos),
        ];

        $pdf = SnappyPdf::loadView('Reportes/ReporteIVA', $data);
        $nombre = public_path('TMPCFDI/ReporteIVA_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/ReporteIVA_' . $team_id . '.pdf';
    }

    /**
     * DIOT (Declaración Informativa de Operaciones con Terceros)
     */
    public function reporteDIOT(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener CFDIs del periodo usando ejercicio y periodo
        $cfdis_recibidos = Almacencfdis::where('team_id', $team_id)
            ->where('xml_type', 'Recibidos')
            ->where('TipoDeComprobante', 'I')
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->get();

        // Usar los campos ya calculados en la tabla almacencfdis
        $proveedores = [];

        foreach ($cfdis_recibidos as $cfdi) {
            $rfc = $cfdi->Emisor_Rfc;
            $nombre = $cfdi->Emisor_Nombre;

            if (!isset($proveedores[$rfc])) {
                $proveedores[$rfc] = [
                    'rfc' => $rfc,
                    'nombre' => $nombre,
                    'subtotal' => 0,
                    'iva_trasladado' => 0,
                    'iva_retenido' => 0,
                    'isr_retenido' => 0,
                    'total' => 0,
                    'num_facturas' => 0,
                ];
            }

            $proveedores[$rfc]['subtotal'] += floatval($cfdi->SubTotal ?? 0);
            $proveedores[$rfc]['total'] += floatval($cfdi->Total ?? 0);
            $proveedores[$rfc]['num_facturas']++;
            $proveedores[$rfc]['iva_trasladado'] += floatval($cfdi->TotalImpuestosTrasladados ?? 0);
            $proveedores[$rfc]['iva_retenido'] += floatval($cfdi->TotalImpuestosRetenidos ?? 0);
            // Nota: No podemos distinguir ISR de IVA en retenciones sin parsear XML
            // Se asume que las retenciones son principalmente IVA
        }

        ksort($proveedores);

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'proveedores' => array_values($proveedores),
            'total_proveedores' => count($proveedores),
            'total_subtotal' => array_sum(array_column($proveedores, 'subtotal')),
            'total_iva' => array_sum(array_column($proveedores, 'iva_trasladado')),
            'total_ret_iva' => array_sum(array_column($proveedores, 'iva_retenido')),
            'total_ret_isr' => array_sum(array_column($proveedores, 'isr_retenido')),
        ];

        $pdf = SnappyPdf::loadView('Reportes/ReporteDIOT', $data);
        $nombre = public_path('TMPCFDI/DIOT_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOrientation('landscape');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/DIOT_' . $team_id . '.pdf';
    }

    /**
     * Reporte de Retenciones (ISR e IVA)
     */
    public function reporteRetenciones(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener CFDIs del periodo usando ejercicio y periodo
        $ret_que_nos_hicieron = [];
        $cfdis_emitidos = Almacencfdis::where('team_id', $team_id)
            ->where('xml_type', 'Emitidos')
            ->where('TipoDeComprobante', 'I')
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->get();

        foreach ($cfdis_emitidos as $cfdi) {
            if (floatval($cfdi->TotalImpuestosRetenidos ?? 0) > 0) {
                $ret_que_nos_hicieron[] = [
                    'fecha' => $cfdi->Fecha,
                    'folio' => ($cfdi->Serie ?? '') . ($cfdi->Folio ?? ''),
                    'rfc_cliente' => $cfdi->Receptor_Rfc,
                    'cliente' => $cfdi->Receptor_Nombre,
                    'tipo' => 'IVA/ISR',
                    'base' => floatval($cfdi->SubTotal ?? 0),
                    'importe' => floatval($cfdi->TotalImpuestosRetenidos ?? 0),
                ];
            }
        }

        $ret_que_hicimos = [];
        $cfdis_recibidos = Almacencfdis::where('team_id', $team_id)
            ->where('xml_type', 'Recibidos')
            ->where('TipoDeComprobante', 'I')
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->get();

        foreach ($cfdis_recibidos as $cfdi) {
            if (floatval($cfdi->TotalImpuestosRetenidos ?? 0) > 0) {
                $ret_que_hicimos[] = [
                    'fecha' => $cfdi->Fecha,
                    'folio' => ($cfdi->Serie ?? '') . ($cfdi->Folio ?? ''),
                    'rfc_proveedor' => $cfdi->Emisor_Rfc,
                    'proveedor' => $cfdi->Emisor_Nombre,
                    'tipo' => 'IVA/ISR',
                    'base' => floatval($cfdi->SubTotal ?? 0),
                    'importe' => floatval($cfdi->TotalImpuestosRetenidos ?? 0),
                ];
            }
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'ret_que_nos_hicieron' => $ret_que_nos_hicieron,
            'ret_que_hicimos' => $ret_que_hicimos,
            'total_ret_nos_hicieron' => array_sum(array_column($ret_que_nos_hicieron, 'importe')),
            'total_ret_hicimos' => array_sum(array_column($ret_que_hicimos, 'importe')),
        ];

        $pdf = SnappyPdf::loadView('Reportes/ReporteRetenciones', $data);
        $nombre = public_path('TMPCFDI/Retenciones_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->setOrientation('landscape');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/Retenciones_' . $team_id . '.pdf';
    }

    /**
     * Reporte de Auxiliares
     */
    public function auxiliaresReporte(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $cuenta_inicio = $request->cuenta_inicio;
        $cuenta_fin = $request->cuenta_fin;
        $periodo_inicio = $request->periodo_inicio;
        $ejercicio_inicio = $request->ejercicio_inicio;
        $periodo_fin = $request->periodo_fin;
        $ejercicio_fin = $request->ejercicio_fin;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas en el rango especificado
        $cuentas_query = DB::table('cat_cuentas')
            ->where('team_id', $team_id)
            ->where('codigo', '>=', $cuenta_inicio)
            ->where('codigo', '<=', $cuenta_fin)
            ->orderBy('codigo');

        $cuentas_data = [];

        foreach ($cuentas_query->get() as $cuenta) {
            // Calcular saldo inicial (movimientos antes del periodo inicial)
            $saldo_inicial = $this->calcularSaldoInicialCuenta($team_id, $cuenta->codigo, $cuenta->naturaleza, $ejercicio_inicio, $periodo_inicio);

            // Obtener movimientos del periodo
            $movimientos = $this->obtenerMovimientosCuentaPeriodo(
                $team_id,
                $cuenta->codigo,
                $ejercicio_inicio,
                $periodo_inicio,
                $ejercicio_fin,
                $periodo_fin
            );

            // Solo incluir cuentas con saldo inicial o movimientos
            if ($saldo_inicial != 0 || count($movimientos) > 0) {
                $cuentas_data[] = (object)[
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'naturaleza' => $cuenta->naturaleza,
                    'saldo_inicial' => $saldo_inicial,
                    'movimientos' => $movimientos
                ];
            }
        }

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'periodo_inicio' => $periodo_inicio,
            'ejercicio_inicio' => $ejercicio_inicio,
            'periodo_fin' => $periodo_fin,
            'ejercicio_fin' => $ejercicio_fin,
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'cuentas' => $cuentas_data,
        ];

        $pdf = SnappyPdf::loadView('Reportes/Auxiliares', $data);
        $nombre = public_path('TMPCFDI/Auxiliares_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/Auxiliares_' . $team_id . '.pdf';
    }

    private function calcularSaldoInicialCuenta($team_id, $codigo, $naturaleza, $ejercicio_inicio, $periodo_inicio)
    {
        // Para TODAS las cuentas: sumar todo el histórico anterior al periodo actual
        // Incluye: (ejercicios anteriores completos) + (periodos anteriores del ejercicio actual)
        // Esto permite que las cuentas de resultados muestren saldo inicial si no se hizo póliza de cierre
        $result = Auxiliares::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->where(function($query) use ($ejercicio_inicio, $periodo_inicio) {
                $query->where('a_ejercicio', '<', $ejercicio_inicio)
                      ->orWhere(function($q) use ($ejercicio_inicio, $periodo_inicio) {
                          $q->where('a_ejercicio', '=', $ejercicio_inicio)
                            ->where('a_periodo', '<', $periodo_inicio);
                      });
            })
            ->selectRaw('SUM(cargo) as total_cargo, SUM(abono) as total_abono')
            ->first();

        $total_cargo = $result->total_cargo ?? 0;
        $total_abono = $result->total_abono ?? 0;

        // Aplicar la naturaleza de la cuenta
        // D (Deudora): Saldo = Cargo - Abono (Activo, Costos, Gastos)
        // A (Acreedora): Saldo = Abono - Cargo (Pasivo, Capital, Ingresos)
        if ($naturaleza == 'A') {
            return $total_abono - $total_cargo;
        } else {
            return $total_cargo - $total_abono;
        }
    }

    private function obtenerMovimientosCuentaPeriodo($team_id, $codigo, $ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin)
    {
        $movimientos = Auxiliares::join('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
            ->where('auxiliares.team_id', $team_id)
            ->where('auxiliares.codigo', $codigo)
            ->where(function($query) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                // Caso 1: Mismo ejercicio (ej. Enero 2026 - Marzo 2026)
                if ($ejercicio_inicio == $ejercicio_fin) {
                    $query->where('auxiliares.a_ejercicio', '=', $ejercicio_inicio)
                          ->where('auxiliares.a_periodo', '>=', $periodo_inicio)
                          ->where('auxiliares.a_periodo', '<=', $periodo_fin);
                }
                // Caso 2: Diferentes ejercicios (ej. Noviembre 2025 - Febrero 2026)
                else {
                    $query->where(function($q) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                        // Movimientos del ejercicio inicial desde periodo_inicio hasta fin de año
                        $q->where(function($subq) use ($ejercicio_inicio, $periodo_inicio) {
                            $subq->where('auxiliares.a_ejercicio', '=', $ejercicio_inicio)
                                 ->where('auxiliares.a_periodo', '>=', $periodo_inicio);
                        })
                        // Movimientos de ejercicios intermedios completos
                        ->orWhere(function($subq) use ($ejercicio_inicio, $ejercicio_fin) {
                            $subq->where('auxiliares.a_ejercicio', '>', $ejercicio_inicio)
                                 ->where('auxiliares.a_ejercicio', '<', $ejercicio_fin);
                        })
                        // Movimientos del ejercicio final desde inicio de año hasta periodo_fin
                        ->orWhere(function($subq) use ($ejercicio_fin, $periodo_fin) {
                            $subq->where('auxiliares.a_ejercicio', '=', $ejercicio_fin)
                                 ->where('auxiliares.a_periodo', '<=', $periodo_fin);
                        });
                    });
                }
            })
            ->select(
                'cat_polizas.fecha',
                'cat_polizas.folio',
                'cat_polizas.tipo',
                'auxiliares.concepto',
                'auxiliares.cargo',
                'auxiliares.abono'
            )
            ->orderBy('auxiliares.a_ejercicio')
            ->orderBy('auxiliares.a_periodo')
            ->orderBy('cat_polizas.fecha')
            ->orderBy('cat_polizas.folio')
            ->get();

        return $movimientos;
    }

    /**
     * Estado de Resultados conforme NIF B-3
     */
    public function estadoResultadosNIF(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas de resultados
        $ingresos = $this->obtenerCuentasResultados($team_id, $periodo, $ejercicio, '400', '499');
        $costos = $this->obtenerCuentasResultados($team_id, $periodo, $ejercicio, '500', '599');
        $gastos_operacion = $this->obtenerCuentasResultados($team_id, $periodo, $ejercicio, '600', '699');

        // Separar cuentas de financiamiento (702, 703, etc.)
        $financiamiento = $this->obtenerCuentasFinanciamiento($team_id, $periodo, $ejercicio);
        $otros_resultados = $this->obtenerCuentasResultados($team_id, $periodo, $ejercicio, '700', '701');

        // Impuestos (cuenta 800)
        $impuestos = $this->obtenerCuentasResultados($team_id, $periodo, $ejercicio, '800', '899');

        // Calcular totales periodo
        $total_ingresos_periodo = $this->calcularTotalPeriodo($ingresos);
        $total_costos_periodo = $this->calcularTotalPeriodo($costos);
        $total_gastos_periodo = $this->calcularTotalPeriodo($gastos_operacion);
        $total_financiamiento_periodo = $this->calcularTotalPeriodo($financiamiento);
        $total_otros_periodo = $this->calcularTotalPeriodo($otros_resultados);
        $total_impuestos_periodo = $this->calcularTotalPeriodo($impuestos);

        // Calcular totales acumulados
        $total_ingresos_acumulado = $this->calcularTotalAcumulado($ingresos);
        $total_costos_acumulado = $this->calcularTotalAcumulado($costos);
        $total_gastos_acumulado = $this->calcularTotalAcumulado($gastos_operacion);
        $total_financiamiento_acumulado = $this->calcularTotalAcumulado($financiamiento);
        $total_otros_acumulado = $this->calcularTotalAcumulado($otros_resultados);
        $total_impuestos_acumulado = $this->calcularTotalAcumulado($impuestos);

        // Calcular utilidades
        $utilidad_bruta_periodo = $total_ingresos_periodo - $total_costos_periodo;
        $utilidad_operacion_periodo = $utilidad_bruta_periodo - $total_gastos_periodo;
        $utilidad_antes_impuestos_periodo = $utilidad_operacion_periodo + $total_financiamiento_periodo + $total_otros_periodo;
        $utilidad_neta_periodo = $utilidad_antes_impuestos_periodo - $total_impuestos_periodo;

        $utilidad_bruta_acumulado = $total_ingresos_acumulado - $total_costos_acumulado;
        $utilidad_operacion_acumulado = $utilidad_bruta_acumulado - $total_gastos_acumulado;
        $utilidad_antes_impuestos_acumulado = $utilidad_operacion_acumulado + $total_financiamiento_acumulado + $total_otros_acumulado;
        $utilidad_neta_acumulado = $utilidad_antes_impuestos_acumulado - $total_impuestos_acumulado;

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'fecha_inicio' => $this->obtenerFechaInicio($periodo, $ejercicio),
            'fecha_fin' => $this->obtenerFechaCorte($periodo, $ejercicio),
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,

            'ingresos' => $ingresos,
            'costos' => $costos,
            'gastos_operacion' => $gastos_operacion,
            'financiamiento' => $financiamiento,
            'otros_resultados' => $otros_resultados,
            'impuestos' => $impuestos,

            'total_ingresos_periodo' => $total_ingresos_periodo,
            'total_costos_periodo' => $total_costos_periodo,
            'total_gastos_periodo' => $total_gastos_periodo,
            'total_financiamiento_periodo' => $total_financiamiento_periodo,
            'total_otros_periodo' => $total_otros_periodo,
            'total_impuestos_periodo' => $total_impuestos_periodo,

            'total_ingresos_acumulado' => $total_ingresos_acumulado,
            'total_costos_acumulado' => $total_costos_acumulado,
            'total_gastos_acumulado' => $total_gastos_acumulado,
            'total_financiamiento_acumulado' => $total_financiamiento_acumulado,
            'total_otros_acumulado' => $total_otros_acumulado,
            'total_impuestos_acumulado' => $total_impuestos_acumulado,

            'utilidad_bruta_periodo' => $utilidad_bruta_periodo,
            'utilidad_operacion_periodo' => $utilidad_operacion_periodo,
            'utilidad_antes_impuestos_periodo' => $utilidad_antes_impuestos_periodo,
            'utilidad_neta_periodo' => $utilidad_neta_periodo,

            'utilidad_bruta_acumulado' => $utilidad_bruta_acumulado,
            'utilidad_operacion_acumulado' => $utilidad_operacion_acumulado,
            'utilidad_antes_impuestos_acumulado' => $utilidad_antes_impuestos_acumulado,
            'utilidad_neta_acumulado' => $utilidad_neta_acumulado,
        ];

        $pdf = SnappyPdf::loadView('Reportes/EstadoResultadosNIF', $data);
        $nombre = public_path('TMPCFDI/EstadoResultadosNIF_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/EstadoResultadosNIF_' . $team_id . '.pdf';
    }

    /**
     * Estado de Cambios en Capital Contable conforme NIF B-4
     */
    public function estadoCambiosCapitalNIF(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener saldos iniciales (año anterior)
        $capital_social_inicial = $this->obtenerSaldoCuenta($team_id, '30001000', $ejercicio - 1);
        $aportaciones_inicial = $this->obtenerSaldoCuenta($team_id, '30002000', $ejercicio - 1);
        $prima_acciones_inicial = $this->obtenerSaldoCuenta($team_id, '30003000', $ejercicio - 1);
        $utilidades_retenidas_inicial = $this->obtenerSaldoCuenta($team_id, '30004000', $ejercicio - 1);
        $reserva_legal_inicial = $this->obtenerSaldoCuenta($team_id, '30005000', $ejercicio - 1);
        $resultado_anterior = $this->calcularResultadoEjercicioAnterior($team_id, $ejercicio - 1);

        $total_capital_inicial = $capital_social_inicial + $aportaciones_inicial + $prima_acciones_inicial +
                                  $utilidades_retenidas_inicial + $reserva_legal_inicial + $resultado_anterior;

        // Movimientos del periodo
        $aportaciones_periodo = $this->obtenerMovimientosCuenta($team_id, '30001000', $periodo, $ejercicio);
        $capitalizacion_utilidades = 0; // Se debe obtener de alguna cuenta específica
        $reserva_periodo = $this->obtenerMovimientosCuenta($team_id, '30005000', $periodo, $ejercicio);
        $dividendos_decretados = $this->obtenerMovimientosCuenta($team_id, '30006000', $periodo, $ejercicio);
        $reembolsos_capital = 0;

        // Resultado del ejercicio actual
        $resultado_ejercicio = $this->calcularResultadoEjercicio($team_id);

        // Saldos finales
        $capital_social_final = $capital_social_inicial + $aportaciones_periodo + $capitalizacion_utilidades - $reembolsos_capital;
        $aportaciones_final = $this->obtenerSaldoCuenta($team_id, '30002000', $ejercicio);
        $prima_acciones_final = $this->obtenerSaldoCuenta($team_id, '30003000', $ejercicio);
        $utilidades_retenidas_final = $utilidades_retenidas_inicial + $resultado_anterior - $capitalizacion_utilidades - $reserva_periodo - $dividendos_decretados;
        $reserva_legal_final = $reserva_legal_inicial + $reserva_periodo;

        $total_capital_final = $capital_social_final + $aportaciones_final + $prima_acciones_final +
                               $utilidades_retenidas_final + $reserva_legal_final + $resultado_ejercicio;

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'fecha_inicio' => '01/01/' . $ejercicio,
            'fecha_inicial' => '31/12/' . ($ejercicio - 1),
            'fecha_fin' => $this->obtenerFechaCorte($periodo, $ejercicio),
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,

            'capital_social_inicial' => $capital_social_inicial,
            'aportaciones_inicial' => $aportaciones_inicial,
            'prima_acciones_inicial' => $prima_acciones_inicial,
            'utilidades_retenidas_inicial' => $utilidades_retenidas_inicial,
            'reserva_legal_inicial' => $reserva_legal_inicial,
            'resultado_anterior' => $resultado_anterior,
            'total_capital_inicial' => $total_capital_inicial,

            'aportaciones_periodo' => $aportaciones_periodo,
            'capitalizacion_utilidades' => $capitalizacion_utilidades,
            'reserva_periodo' => $reserva_periodo,
            'dividendos_decretados' => $dividendos_decretados,
            'reembolsos_capital' => $reembolsos_capital,
            'resultado_ejercicio' => $resultado_ejercicio,

            'capital_social_final' => $capital_social_final,
            'aportaciones_final' => $aportaciones_final,
            'prima_acciones_final' => $prima_acciones_final,
            'utilidades_retenidas_final' => $utilidades_retenidas_final,
            'reserva_legal_final' => $reserva_legal_final,
            'total_capital_final' => $total_capital_final,
        ];

        $pdf = SnappyPdf::loadView('Reportes/EstadoCambiosCapitalNIF', $data);
        $nombre = public_path('TMPCFDI/EstadoCambiosCapitalNIF_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/EstadoCambiosCapitalNIF_' . $team_id . '.pdf';
    }

    /**
     * Estado de Flujos de Efectivo conforme NIF B-2
     */
    public function estadoFlujoEfectivoNIF(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        $utilidad_neta = $this->calcularResultadoEjercicio($team_id);

        // Ajustes por partidas que no implican flujo de efectivo
        $ajustes_operacion = [
            (object)['concepto' => 'Depreciación y amortización', 'importe' => $this->obtenerDepreciacion($team_id, $periodo)],
            (object)['concepto' => 'Provisión para cuentas incobrables', 'importe' => $this->obtenerProvisionIncobrables($team_id, $periodo)],
        ];

        // Cambios en activos y pasivos de operación
        $cambios_operacion = [
            (object)['concepto' => '(Aumento) disminución en clientes', 'importe' => $this->cambioEnClientes($team_id, $periodo, $ejercicio)],
            (object)['concepto' => '(Aumento) disminución en inventarios', 'importe' => $this->cambioEnInventarios($team_id, $periodo, $ejercicio)],
            (object)['concepto' => 'Aumento (disminución) en proveedores', 'importe' => $this->cambioEnProveedores($team_id, $periodo, $ejercicio)],
            (object)['concepto' => 'Aumento (disminución) en impuestos por pagar', 'importe' => $this->cambioEnImpuestos($team_id, $periodo, $ejercicio)],
        ];

        $flujo_operacion = $utilidad_neta;
        foreach ($ajustes_operacion as $ajuste) $flujo_operacion += $ajuste->importe;
        foreach ($cambios_operacion as $cambio) $flujo_operacion += $cambio->importe;

        // Actividades de inversión
        $actividades_inversion = [
            (object)['concepto' => 'Adquisición de activo fijo', 'importe' => $this->obtenerAdquisicionActivos($team_id, $periodo, $ejercicio)],
            (object)['concepto' => 'Venta de activo fijo', 'importe' => $this->obtenerVentaActivos($team_id, $periodo, $ejercicio)],
        ];

        $flujo_inversion = 0;
        foreach ($actividades_inversion as $actividad) $flujo_inversion += $actividad->importe;

        // Actividades de financiamiento
        $actividades_financiamiento = [
            (object)['concepto' => 'Aportaciones de capital', 'importe' => $this->obtenerAportacionesCapital($team_id, $periodo, $ejercicio)],
            (object)['concepto' => 'Obtención de préstamos', 'importe' => $this->obtenerPrestamos($team_id, $periodo, $ejercicio)],
            (object)['concepto' => 'Pago de préstamos', 'importe' => $this->obtenerPagoPrestamos($team_id, $periodo, $ejercicio)],
            (object)['concepto' => 'Pago de dividendos', 'importe' => $this->obtenerPagoDividendos($team_id, $periodo, $ejercicio)],
        ];

        $flujo_financiamiento = 0;
        foreach ($actividades_financiamiento as $actividad) $flujo_financiamiento += $actividad->importe;

        $incremento_neto = $flujo_operacion + $flujo_inversion + $flujo_financiamiento;

        // Efectivo inicial y final
        $efectivo_inicial = $this->obtenerEfectivoInicial($team_id, $ejercicio);
        $efectivo_final = $efectivo_inicial + $incremento_neto;

        $data = [
            'empresa_nombre' => $empresa->name,
            'rfc' => Filament::getTenant()->taxid,
            'fecha_inicio' => $this->obtenerFechaInicio($periodo, $ejercicio),
            'fecha_fin' => $this->obtenerFechaCorte($periodo, $ejercicio),
            'fecha_emision' => Carbon::now()->format('d/m/Y'),
            'periodo' => $periodo,
            'ejercicio' => $ejercicio,

            'utilidad_neta' => $utilidad_neta,
            'ajustes_operacion' => $ajustes_operacion,
            'cambios_operacion' => $cambios_operacion,
            'flujo_operacion' => $flujo_operacion,

            'actividades_inversion' => $actividades_inversion,
            'flujo_inversion' => $flujo_inversion,

            'actividades_financiamiento' => $actividades_financiamiento,
            'flujo_financiamiento' => $flujo_financiamiento,

            'incremento_neto' => $incremento_neto,
            'efectivo_inicial' => $efectivo_inicial,
            'efectivo_final' => $efectivo_final,
        ];

        $pdf = SnappyPdf::loadView('Reportes/EstadoFlujoEfectivoNIF', $data);
        $nombre = public_path('TMPCFDI/EstadoFlujoEfectivoNIF_' . $team_id . '.pdf');

        if (file_exists($nombre)) unlink($nombre);
        $pdf->setOption('encoding', 'utf-8');
        $pdf->save($nombre);

        return env('APP_URL') . '/TMPCFDI/EstadoFlujoEfectivoNIF_' . $team_id . '.pdf';
    }

    // ========== MÉTODOS AUXILIARES ==========

    private function obtenerCuentasPorRango($team_id, $inicio, $fin)
    {
        // Convertir inicio y fin a enteros para comparación correcta
        $inicio_int = intval($inicio);
        $fin_int = intval($fin);

        return SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) >= ?", [$inicio_int])
            ->whereRaw("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) <= ?", [$fin_int])
            ->where('nivel', 1) // Solo nivel 1 para no duplicar
            ->where(function($query) {
                $query->where('anterior', '!=', 0)
                      ->orWhere('final', '!=', 0);
            })
            ->orderBy('codigo')
            ->get()
            ->map(function($cuenta) {
                $cuenta->saldo_actual = $cuenta->final;
                $cuenta->saldo_anterior = $cuenta->anterior;
                $cuenta->nombre = $cuenta->cuenta;
                return $cuenta;
            });
    }

    private function obtenerCuentasResultados($team_id, $periodo, $ejercicio, $inicio, $fin)
    {
        $inicio_int = intval($inicio);
        $fin_int = intval($fin);

        return SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) >= ?", [$inicio_int])
            ->whereRaw("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) <= ?", [$fin_int])
            ->where('nivel', 1) // Solo nivel 1
            ->where(function($query) {
                $query->where('cargos', '!=', 0)
                      ->orWhere('abonos', '!=', 0)
                      ->orWhere('final', '!=', 0);
            })
            ->orderBy('codigo')
            ->get()
            ->map(function($cuenta) use ($team_id, $periodo, $ejercicio) {
                // Saldo del periodo actual
                $saldo = ($cuenta->naturaleza == 'A')
                    ? ($cuenta->abonos - $cuenta->cargos)
                    : ($cuenta->cargos - $cuenta->abonos);

                $cuenta->saldo_periodo = $saldo;

                // Para el Estado de Resultados, el acumulado debe ser SOLO del ejercicio actual
                // No debe incluir años anteriores, aunque existan saldos sin póliza de cierre
                $acumulado_ejercicio = Auxiliares::where('team_id', $team_id)
                    ->where('codigo', $cuenta->codigo)
                    ->where('a_ejercicio', $ejercicio)
                    ->where('a_periodo', '<=', $periodo)
                    ->selectRaw('COALESCE(SUM(cargo),0) as total_cargo, COALESCE(SUM(abono),0) as total_abono')
                    ->first();

                $cuenta->saldo_acumulado = ($cuenta->naturaleza == 'A')
                    ? ($acumulado_ejercicio->total_abono - $acumulado_ejercicio->total_cargo)
                    : ($acumulado_ejercicio->total_cargo - $acumulado_ejercicio->total_abono);

                $cuenta->nombre = $cuenta->cuenta;
                return $cuenta;
            });
    }

    private function obtenerCuentasFinanciamiento($team_id, $periodo, $ejercicio)
    {

        return SaldosReportes::where('team_id', $team_id)
            ->where(function($query) {
                $query->where('codigo', 'like', '702%')
                      ->orWhere('codigo', 'like', '703%');
            })
            ->get()
            ->map(function($cuenta) use ($team_id, $periodo, $ejercicio) {
                // Saldo del periodo actual
                $saldo = ($cuenta->naturaleza == 'A')
                    ? ($cuenta->abonos - $cuenta->cargos)
                    : ($cuenta->cargos - $cuenta->abonos);

                $cuenta->saldo_periodo = $saldo;

                // Para el Estado de Resultados, el acumulado debe ser SOLO del ejercicio actual
                $acumulado_ejercicio = Auxiliares::where('team_id', $team_id)
                    ->where('codigo', $cuenta->codigo)
                    ->where('a_ejercicio', $ejercicio)
                    ->where('a_periodo', '<=', $periodo)
                    ->selectRaw('COALESCE(SUM(cargo),0) as total_cargo, COALESCE(SUM(abono),0) as total_abono')
                    ->first();

                $cuenta->saldo_acumulado = ($cuenta->naturaleza == 'A')
                    ? ($acumulado_ejercicio->total_abono - $acumulado_ejercicio->total_cargo)
                    : ($acumulado_ejercicio->total_cargo - $acumulado_ejercicio->total_abono);

                $cuenta->nombre = $cuenta->cuenta;
                return $cuenta;
            });
    }

    private function calcularTotal($cuentas)
    {
        return $cuentas->sum('saldo_actual');
    }

    private function calcularTotalPeriodo($cuentas)
    {
        return $cuentas->sum('saldo_periodo');
    }

    private function calcularTotalAcumulado($cuentas)
    {
        return $cuentas->sum('saldo_acumulado');
    }

    private function calcularResultadoEjercicio($team_id)
    {
        // Usar el campo 'final' que ya está correctamente calculado
        // Ingresos (naturaleza A - acreedora): el saldo final es positivo cuando abonos > cargos
        $total_ingresos = SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 4")
            ->where('nivel', 1)
            ->sum('final');

        // Costos (naturaleza D - deudora): el saldo final es positivo cuando cargos > abonos
        $total_costos = SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 5")
            ->where('nivel', 1)
            ->sum('final');

        // Gastos (naturaleza D - deudora)
        $total_gastos = SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 6")
            ->where('nivel', 1)
            ->sum('final');

        // Otros gastos y financiamiento (naturaleza puede variar)
        $total_otros = SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 7")
            ->where('nivel', 1)
            ->sum('final');

        // Impuestos (naturaleza D - deudora)
        $total_impuestos = SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 8")
            ->where('nivel', 1)
            ->sum('final');

        // Resultado = Ingresos - Costos - Gastos + Otros - Impuestos
        return $total_ingresos - $total_costos - $total_gastos + $total_otros - $total_impuestos;
    }

    private function calcularTotalAnterior($team_id, $inicio, $fin, $ejercicio)
    {
        return SaldosReportes::where('team_id', $team_id)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) BETWEEN ? AND ?", [$inicio, $fin])
            ->sum('anterior');
    }

    private function calcularResultadoEjercicioAnterior($team_id, $ejercicio)
    {
        // Calcular con base en auxiliares del ejercicio anterior
        $ingresos = Auxiliares::where('team_id', $team_id)
            ->where('a_ejercicio', $ejercicio)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 4")
            ->sum(DB::raw('abono - cargo'));

        $egresos = Auxiliares::where('team_id', $team_id)
            ->where('a_ejercicio', $ejercicio)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) IN (5,6,7,8)")
            ->sum(DB::raw('cargo - abono'));

        return $ingresos - $egresos;
    }

    private function obtenerFechaCorte($periodo, $ejercicio)
    {
        $ultimo_dia = Carbon::create($ejercicio, $periodo, 1)->endOfMonth()->day;
        return sprintf('%02d/%02d/%d', $ultimo_dia, $periodo, $ejercicio);
    }

    private function obtenerFechaInicio($periodo, $ejercicio)
    {
        return sprintf('01/%02d/%d', $periodo, $ejercicio);
    }

    private function obtenerSaldoCuenta($team_id, $codigo, $ejercicio)
    {
        $cuenta = SaldosReportes::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->first();

        return $cuenta ? $cuenta->anterior : 0;
    }

    private function obtenerMovimientosCuenta($team_id, $codigo, $periodo, $ejercicio)
    {
        return Auxiliares::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->where('a_periodo', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->sum(DB::raw('cargo - abono'));
    }

    // Métodos para Estado de Flujos de Efectivo
    private function obtenerDepreciacion($team_id, $periodo)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerProvisionIncobrables($team_id, $periodo)
    {
        return 0; // Implementar lógica específica
    }

    private function cambioEnClientes($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function cambioEnInventarios($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function cambioEnProveedores($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function cambioEnImpuestos($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerAdquisicionActivos($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerVentaActivos($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerAportacionesCapital($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerPrestamos($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerPagoPrestamos($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerPagoDividendos($team_id, $periodo, $ejercicio)
    {
        return 0; // Implementar lógica específica
    }

    private function obtenerEfectivoInicial($team_id, $ejercicio)
    {
        return SaldosReportes::where('team_id', $team_id)
            ->where('codigo', 'like', '101%')
            ->sum('anterior');
    }

    // ========== MÉTODOS DE EXPORTACIÓN A EXCEL ==========

    public function balanzaComprobacionExcel(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;
        $nivel_detalle = $request->nivel_detalle ?? 'mayor'; // Por defecto solo cuentas de mayor
        $mostrar_cuentas = $request->mostrar_cuentas ?? 'con_movimiento'; // Por defecto solo con movimientos

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas según los filtros
        $query = SaldosReportes::where('team_id', $team_id);

        // Filtrar por nivel si se solicita solo cuentas de mayor
        if ($nivel_detalle === 'mayor') {
            $query->where('nivel', 1);
        }

        // Filtrar por cuentas con movimientos o saldo si se solicita
        if ($mostrar_cuentas === 'con_movimiento') {
            $query->where(function($query) {
                $query->where('anterior', '!=', 0)
                      ->orWhere('cargos', '!=', 0)
                      ->orWhere('abonos', '!=', 0)
                      ->orWhere('final', '!=', 0);
            });
        }

        $cuentas = $query->orderBy('codigo')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Balanza Comprobación');

        // Encabezado
        $sheet->setCellValue('A1', $empresa->name);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'BALANZA DE COMPROBACIÓN');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Periodo: ' . str_pad($periodo, 2, '0', STR_PAD_LEFT) . '/' . $ejercicio);
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Cabeceras de columnas
        $row = 5;
        $headers = ['CÓDIGO', 'CUENTA', 'SALDO INICIAL DEUDOR', 'SALDO INICIAL ACREEDOR',
                    'DEBE', 'HABER', 'SALDO FINAL DEUDOR', 'SALDO FINAL ACREEDOR'];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF2C3E50');
            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Datos
        $row = 6;
        $total_inicial_deudor = 0;
        $total_inicial_acreedor = 0;
        $total_debe = 0;
        $total_haber = 0;
        $total_final_deudor = 0;
        $total_final_acreedor = 0;

        foreach ($cuentas as $cuenta) {
            // Obtener naturaleza de la cuenta
            $naturaleza = $cuenta->naturaleza ?? 'D';

            // Saldo inicial y final
            $saldo_inicial = $cuenta->anterior ?? 0;
            $saldo_final = $cuenta->final ?? 0;

            // Movimientos
            $debe = $cuenta->cargos ?? 0;
            $haber = $cuenta->abonos ?? 0;

            // Determinar columnas según naturaleza y signo del saldo
            // Para cuentas DEUDORAS (D): saldo positivo = deudor, negativo = acreedor
            // Para cuentas ACREEDORAS (A): saldo positivo = acreedor, negativo = deudor
            if ($naturaleza == 'D') {
                $inicial_deudor = $saldo_inicial >= 0 ? $saldo_inicial : 0;
                $inicial_acreedor = $saldo_inicial < 0 ? abs($saldo_inicial) : 0;
                $final_deudor = $saldo_final >= 0 ? $saldo_final : 0;
                $final_acreedor = $saldo_final < 0 ? abs($saldo_final) : 0;
            } else {
                // Para acreedoras, invertir: saldo positivo va a acreedor
                $inicial_deudor = $saldo_inicial < 0 ? abs($saldo_inicial) : 0;
                $inicial_acreedor = $saldo_inicial >= 0 ? $saldo_inicial : 0;
                $final_deudor = $saldo_final < 0 ? abs($saldo_final) : 0;
                $final_acreedor = $saldo_final >= 0 ? $saldo_final : 0;
            }

            $sheet->setCellValue('A' . $row, $cuenta->codigo);
            $sheet->setCellValue('B' . $row, $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $inicial_deudor > 0 ? $inicial_deudor : '');
            $sheet->setCellValue('D' . $row, $inicial_acreedor > 0 ? $inicial_acreedor : '');
            $sheet->setCellValue('E' . $row, $debe > 0 ? $debe : '');
            $sheet->setCellValue('F' . $row, $haber > 0 ? $haber : '');
            $sheet->setCellValue('G' . $row, $final_deudor > 0 ? $final_deudor : '');
            $sheet->setCellValue('H' . $row, $final_acreedor > 0 ? $final_acreedor : '');

            // Formato de números
            foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $col) {
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }

            // Acumular totales SOLO para cuentas de mayor (nivel 1)
            if (($cuenta->nivel ?? 1) == 1) {
                $total_inicial_deudor += $inicial_deudor;
                $total_inicial_acreedor += $inicial_acreedor;
                $total_debe += $debe;
                $total_haber += $haber;
                $total_final_deudor += $final_deudor;
                $total_final_acreedor += $final_acreedor;
            }

            $row++;
        }

        // Totales
        $sheet->setCellValue('A' . $row, 'TOTALES');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('C' . $row, $total_inicial_deudor);
        $sheet->setCellValue('D' . $row, $total_inicial_acreedor);
        $sheet->setCellValue('E' . $row, $total_debe);
        $sheet->setCellValue('F' . $row, $total_haber);
        $sheet->setCellValue('G' . $row, $total_final_deudor);
        $sheet->setCellValue('H' . $row, $total_final_acreedor);

        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF34495E');
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');

        foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(40);
        foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }

        $filename = 'BalanzaComprobacion_' . $periodo . '_' . $ejercicio . '.xlsx';
        $filepath = public_path('TMPCFDI/' . $filename);

        if (file_exists($filepath)) unlink($filepath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filename;
    }

    public function balanzaSimplificadaExcel(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;
        $nivel_detalle = $request->nivel_detalle ?? 'mayor';
        $mostrar_cuentas = $request->mostrar_cuentas ?? 'con_movimiento';

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas según los filtros
        $query = SaldosReportes::where('team_id', $team_id);

        // Filtrar por nivel
        if ($nivel_detalle === 'mayor') {
            $query->where('nivel', 1);
        }

        // Filtrar por cuentas con movimientos o saldo
        if ($mostrar_cuentas === 'con_movimiento') {
            $query->where(function($query) {
                $query->where('anterior', '!=', 0)
                      ->orWhere('cargos', '!=', 0)
                      ->orWhere('abonos', '!=', 0)
                      ->orWhere('final', '!=', 0);
            });
        }

        $cuentas = $query->orderBy('codigo')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Balanza Simplificada');

        // Encabezado empresa
        $sheet->setCellValue('A1', $empresa->name);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'BALANZA DE COMPROBACIÓN SIMPLIFICADA');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $sheet->setCellValue('A3', 'Periodo: ' . $meses[$periodo] . ' ' . $ejercicio);
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Encabezados de columnas
        $row = 5;
        $headers = ['Código', 'Cuenta', 'Saldo Inicial', 'Cargos', 'Abonos', 'Saldo Final'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($columns[$index] . $row, $header);
        }

        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2C3E50');
        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Datos
        $row = 6;
        $total_inicial = 0;
        $total_cargos = 0;
        $total_abonos = 0;
        $total_final = 0;

        foreach ($cuentas as $cuenta) {
            // Obtener naturaleza
            $naturaleza = $cuenta->naturaleza ?? 'D';

            // Saldos y movimientos
            // Para cuentas acreedoras (A), invertir el signo de los saldos para que la balanza cuadre
            $multiplicador = ($naturaleza == 'A') ? -1 : 1;

            $saldo_inicial_raw = $cuenta->anterior ?? 0;
            $saldo_final_raw = $cuenta->final ?? 0;

            $saldo_inicial = $saldo_inicial_raw * $multiplicador;
            $saldo_final = $saldo_final_raw * $multiplicador;

            $cargos = $cuenta->cargos ?? 0;
            $abonos = $cuenta->abonos ?? 0;

            $sheet->setCellValue('A' . $row, $cuenta->codigo);
            $sheet->setCellValue('B' . $row, $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $saldo_inicial);
            $sheet->setCellValue('D' . $row, $cargos);
            $sheet->setCellValue('E' . $row, $abonos);
            $sheet->setCellValue('F' . $row, $saldo_final);

            // Formato de números
            foreach (['C', 'D', 'E', 'F'] as $col) {
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            }

            // Determinar clase de nivel para estilo
            $nivel = $cuenta->nivel ?? 1;
            if ($nivel == 1) {
                $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
            }

            // Acumular totales solo para nivel 1
            if ($nivel == 1) {
                $total_inicial += $saldo_inicial;
                $total_cargos += $cargos;
                $total_abonos += $abonos;
                $total_final += $saldo_final;
            }

            $row++;
        }

        // Fila de totales
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTALES');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('C' . $row, $total_inicial);
        $sheet->setCellValue('D' . $row, $total_cargos);
        $sheet->setCellValue('E' . $row, $total_abonos);
        $sheet->setCellValue('F' . $row, $total_final);

        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF34495E');
        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');

        foreach (['C', 'D', 'E', 'F'] as $col) {
            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Ajustar anchos de columna
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(40);
        foreach (['C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }

        $filename = 'BalanzaSimplificada_' . $periodo . '_' . $ejercicio . '.xlsx';
        $filepath = public_path('TMPCFDI/' . $filename);

        if (file_exists($filepath)) unlink($filepath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filename;
    }

    public function exportarTodosExcel(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();
        $spreadsheet = new Spreadsheet();

        // 1. Balanza de Comprobación
        $this->crearHojaBalanza($spreadsheet, 0, $team_id, $periodo, $ejercicio, $empresa);

        // 2. Balance General
        $sheet = $spreadsheet->createSheet(1);
        $sheet->setTitle('Balance General');
        $this->crearHojaBalanceGeneral($sheet, $team_id, $periodo, $ejercicio, $empresa);

        // 3. Estado de Resultados
        $sheet = $spreadsheet->createSheet(2);
        $sheet->setTitle('Estado Resultados');
        $this->crearHojaEstadoResultados($sheet, $team_id, $periodo, $ejercicio, $empresa);

        // 4. Cambios en Capital
        $sheet = $spreadsheet->createSheet(3);
        $sheet->setTitle('Cambios Capital');
        $this->crearHojaCambiosCapital($sheet, $team_id, $periodo, $ejercicio, $empresa);

        // 5. Flujo de Efectivo
        $sheet = $spreadsheet->createSheet(4);
        $sheet->setTitle('Flujo Efectivo');
        $this->crearHojaFlujoEfectivo($sheet, $team_id, $periodo, $ejercicio, $empresa);

        $filename = 'ReportesNIF_' . $periodo . '_' . $ejercicio . '.xlsx';
        $filepath = public_path('TMPCFDI/' . $filename);

        if (file_exists($filepath)) unlink($filepath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filename;
    }

    private function crearHojaBalanza($spreadsheet, $index, $team_id, $periodo, $ejercicio, $empresa)
    {
        $sheet = $spreadsheet->getSheet($index);
        $sheet->setTitle('Balanza');

        $cuentas = SaldosReportes::where('team_id', $team_id)
            ->where(function($query) {
                $query->where('anterior', '!=', 0)
                      ->orWhere('cargos', '!=', 0)
                      ->orWhere('abonos', '!=', 0)
                      ->orWhere('final', '!=', 0);
            })
            ->orderBy('codigo')
            ->get();

        $sheet->setCellValue('A1', $empresa->name . ' - BALANZA DE COMPROBACIÓN');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 3;
        $headers = ['CÓDIGO', 'CUENTA', 'INICIAL DEUDOR', 'INICIAL ACREEDOR', 'DEBE', 'HABER', 'FINAL DEUDOR', 'FINAL ACREEDOR'];
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
        }

        $row = 4;
        $total_inicial_deudor = 0;
        $total_inicial_acreedor = 0;
        $total_debe = 0;
        $total_haber = 0;
        $total_final_deudor = 0;
        $total_final_acreedor = 0;

        foreach ($cuentas as $cuenta) {
            $saldo_inicial = $cuenta->anterior ?? 0;
            $inicial_deudor = $saldo_inicial > 0 ? $saldo_inicial : 0;
            $inicial_acreedor = $saldo_inicial < 0 ? abs($saldo_inicial) : 0;
            $debe = $cuenta->cargos ?? 0;
            $haber = $cuenta->abonos ?? 0;
            $saldo_final = $cuenta->final ?? 0;
            $final_deudor = $saldo_final > 0 ? $saldo_final : 0;
            $final_acreedor = $saldo_final < 0 ? abs($saldo_final) : 0;

            $sheet->setCellValue('A' . $row, $cuenta->codigo);
            $sheet->setCellValue('B' . $row, $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $inicial_deudor > 0 ? $inicial_deudor : '');
            $sheet->setCellValue('D' . $row, $inicial_acreedor > 0 ? $inicial_acreedor : '');
            $sheet->setCellValue('E' . $row, $debe > 0 ? $debe : '');
            $sheet->setCellValue('F' . $row, $haber > 0 ? $haber : '');
            $sheet->setCellValue('G' . $row, $final_deudor > 0 ? $final_deudor : '');
            $sheet->setCellValue('H' . $row, $final_acreedor > 0 ? $final_acreedor : '');

            // Acumular totales SOLO para cuentas de mayor (nivel 1)
            if (($cuenta->nivel ?? 1) == 1) {
                $total_inicial_deudor += $inicial_deudor;
                $total_inicial_acreedor += $inicial_acreedor;
                $total_debe += $debe;
                $total_haber += $haber;
                $total_final_deudor += $final_deudor;
                $total_final_acreedor += $final_acreedor;
            }

            $row++;
        }

        // Totales
        $sheet->setCellValue('A' . $row, 'TOTALES');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('C' . $row, $total_inicial_deudor);
        $sheet->setCellValue('D' . $row, $total_inicial_acreedor);
        $sheet->setCellValue('E' . $row, $total_debe);
        $sheet->setCellValue('F' . $row, $total_haber);
        $sheet->setCellValue('G' . $row, $total_final_deudor);
        $sheet->setCellValue('H' . $row, $total_final_acreedor);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);

        $sheet->getColumnDimension('B')->setWidth(40);
    }

    private function crearHojaBalanceGeneral($sheet, $team_id, $periodo, $ejercicio, $empresa)
    {
        $sheet->setCellValue('A1', $empresa->name . ' - BALANCE GENERAL');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $activo_circulante = $this->obtenerCuentasPorRango($team_id, '100', '149');
        $activo_no_circulante = $this->obtenerCuentasPorRango($team_id, '150', '199');

        $row = 3;
        $sheet->setCellValue('A' . $row, 'ACTIVO');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($activo_circulante as $cuenta) {
            $sheet->setCellValue('A' . $row, $cuenta->codigo);
            $sheet->setCellValue('B' . $row, $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $cuenta->final);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(18);
    }

    private function crearHojaEstadoResultados($sheet, $team_id, $periodo, $ejercicio, $empresa)
    {
        $sheet->setCellValue('A1', $empresa->name . ' - ESTADO DE RESULTADOS');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $ingresos = $this->obtenerCuentasResultados($team_id, $periodo, '400', '499');

        $row = 3;
        $sheet->setCellValue('A' . $row, 'INGRESOS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($ingresos as $cuenta) {
            $sheet->setCellValue('A' . $row, $cuenta->codigo);
            $sheet->setCellValue('B' . $row, $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $cuenta->saldo_periodo ?? 0);
            $sheet->setCellValue('D' . $row, $cuenta->saldo_acumulado ?? 0);
            $row++;
        }

        $sheet->getColumnDimension('B')->setWidth(40);
    }

    private function crearHojaCambiosCapital($sheet, $team_id, $periodo, $ejercicio, $empresa)
    {
        $sheet->setCellValue('A1', $empresa->name . ' - ESTADO DE CAMBIOS EN CAPITAL');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $capital = $this->obtenerCuentasPorRango($team_id, '300', '399');

        $row = 3;
        foreach ($capital as $cuenta) {
            $sheet->setCellValue('A' . $row, $cuenta->codigo);
            $sheet->setCellValue('B' . $row, $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $cuenta->final);
            $row++;
        }

        $sheet->getColumnDimension('B')->setWidth(40);
    }

    private function crearHojaFlujoEfectivo($sheet, $team_id, $periodo, $ejercicio, $empresa)
    {
        $sheet->setCellValue('A1', $empresa->name . ' - ESTADO DE FLUJOS DE EFECTIVO');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 3;
        $sheet->setCellValue('A' . $row, 'ACTIVIDADES DE OPERACIÓN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $sheet->getColumnDimension('B')->setWidth(40);
    }

    /**
     * Balance General exportable a Excel
     */
    public function balanceGeneralExcel(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener saldos por categoría
        $activo_circulante = $this->obtenerSaldosCategoria($team_id, '101', '119');
        $activo_no_circulante = $this->obtenerSaldosCategoria($team_id, '120', '199');
        $pasivo_corto_plazo = $this->obtenerSaldosCategoria($team_id, '201', '219');
        $pasivo_largo_plazo = $this->obtenerSaldosCategoria($team_id, '220', '299');
        $capital = $this->obtenerSaldosCategoria($team_id, '301', '399');

        // Calcular resultado del ejercicio
        // Obtener todas las cuentas de resultados
        $ingresos = $this->obtenerSaldosCategoria($team_id, '401', '499'); // Solo ingresos
        $costo_ventas = $this->obtenerSaldosCategoria($team_id, '501', '509'); // Costo de ventas
        $gastos_operacion = $this->obtenerSaldosCategoria($team_id, '510', '599'); // Gastos de operación
        $otros_ingresos_bg = $this->obtenerSaldosCategoria($team_id, '601', '699'); // Otros ingresos
        $otros_gastos_bg = $this->obtenerSaldosCategoria($team_id, '701', '799'); // Otros gastos

        // Los ingresos tienen saldo positivo pero son acreedores (naturaleza A)
        // Los gastos tienen saldo positivo y son deudores (naturaleza D)
        // Resultado = Ingresos - Gastos
        $total_ingresos_bg = abs($ingresos->sum('final')) + abs($otros_ingresos_bg->sum('final'));
        $total_gastos_bg = abs($costo_ventas->sum('final')) + abs($gastos_operacion->sum('final')) + abs($otros_gastos_bg->sum('final'));
        $resultado_ejercicio = $total_ingresos_bg - $total_gastos_bg;

        $total_activo_circulante = $activo_circulante->sum('final');
        $total_activo_no_circulante = $activo_no_circulante->sum('final');
        $total_activo = $total_activo_circulante + $total_activo_no_circulante;

        $total_pasivo_cp = abs($pasivo_corto_plazo->sum('final'));
        $total_pasivo_lp = abs($pasivo_largo_plazo->sum('final'));
        $total_pasivo = $total_pasivo_cp + $total_pasivo_lp;

        $total_capital = abs($capital->sum('final'));
        $total_capital_resultado = $total_capital + $resultado_ejercicio;
        $total_pasivo_capital = $total_pasivo + $total_capital_resultado;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Balance General');

        // Encabezado
        $sheet->setCellValue('A1', $empresa->name);
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'BALANCE GENERAL (Estado de Situación Financiera)');
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $sheet->setCellValue('A3', 'Al ' . $this->obtenerFechaCorte($periodo, $ejercicio));
        $sheet->mergeCells('A3:C3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;

        // ACTIVO
        $sheet->setCellValue('A' . $row, 'ACTIVO');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2C3E50');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row++;

        // Activo Circulante
        $sheet->setCellValue('A' . $row, 'Activo Circulante');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($activo_circulante as $cuenta) {
            $sheet->setCellValue('A' . $row, '  ' . $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $cuenta->final);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Activo Circulante');
        $sheet->setCellValue('C' . $row, $total_activo_circulante);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFECF0F1');
        $row += 2;

        // Activo No Circulante
        $sheet->setCellValue('A' . $row, 'Activo No Circulante');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($activo_no_circulante as $cuenta) {
            $sheet->setCellValue('A' . $row, '  ' . $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, $cuenta->final);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Activo No Circulante');
        $sheet->setCellValue('C' . $row, $total_activo_no_circulante);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFECF0F1');
        $row += 2;

        // Total Activo
        $sheet->setCellValue('A' . $row, 'TOTAL ACTIVO');
        $sheet->setCellValue('C' . $row, $total_activo);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF34495E');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row += 3;

        // PASIVO
        $sheet->setCellValue('A' . $row, 'PASIVO');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2C3E50');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row++;

        // Pasivo Corto Plazo
        $sheet->setCellValue('A' . $row, 'Pasivo a Corto Plazo');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($pasivo_corto_plazo as $cuenta) {
            $sheet->setCellValue('A' . $row, '  ' . $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, abs($cuenta->final));
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Pasivo Corto Plazo');
        $sheet->setCellValue('C' . $row, $total_pasivo_cp);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFECF0F1');
        $row += 2;

        // Pasivo Largo Plazo
        $sheet->setCellValue('A' . $row, 'Pasivo a Largo Plazo');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($pasivo_largo_plazo as $cuenta) {
            $sheet->setCellValue('A' . $row, '  ' . $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, abs($cuenta->final));
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Pasivo Largo Plazo');
        $sheet->setCellValue('C' . $row, $total_pasivo_lp);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFECF0F1');
        $row += 2;

        // Total Pasivo
        $sheet->setCellValue('A' . $row, 'TOTAL PASIVO');
        $sheet->setCellValue('C' . $row, $total_pasivo);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF34495E');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row += 3;

        // CAPITAL
        $sheet->setCellValue('A' . $row, 'CAPITAL CONTABLE');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2C3E50');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row++;

        foreach ($capital as $cuenta) {
            $sheet->setCellValue('A' . $row, '  ' . $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, abs($cuenta->final));
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Resultado del ejercicio
        $sheet->setCellValue('A' . $row, '  Resultado del Ejercicio');
        $sheet->setCellValue('C' . $row, $resultado_ejercicio);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('C' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'TOTAL CAPITAL');
        $sheet->setCellValue('C' . $row, $total_capital_resultado);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF34495E');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row += 2;

        // Total Pasivo + Capital
        $sheet->setCellValue('A' . $row, 'TOTAL PASIVO + CAPITAL');
        $sheet->setCellValue('C' . $row, $total_pasivo_capital);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF27AE60');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(18);

        $filename = 'BalanceGeneral_' . $periodo . '_' . $ejercicio . '.xlsx';
        $filepath = public_path('TMPCFDI/' . $filename);

        if (file_exists($filepath)) unlink($filepath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filename;
    }

    private function obtenerSaldosCategoria($team_id, $inicio, $fin)
    {
        return SaldosReportes::where('team_id', $team_id)
            ->where('nivel', 1)
            ->where('codigo', '>=', $inicio . '00000')
            ->where('codigo', '<=', $fin . '99999')
            ->where(function($query) {
                $query->where('final', '!=', 0);
            })
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Estado de Resultados exportable a Excel
     */
    public function estadoResultadosExcel(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;

        app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener ingresos y gastos
        $ingresos = $this->obtenerSaldosCategoria($team_id, '401', '499');
        $costo_ventas = $this->obtenerSaldosCategoria($team_id, '501', '509');
        $gastos_operacion = $this->obtenerSaldosCategoria($team_id, '510', '599');
        $otros_ingresos = $this->obtenerSaldosCategoria($team_id, '601', '699');
        $otros_gastos = $this->obtenerSaldosCategoria($team_id, '701', '799');

        $total_ingresos = abs($ingresos->sum('final'));
        $total_costo = abs($costo_ventas->sum('final'));
        $utilidad_bruta = $total_ingresos - $total_costo;

        $total_gastos_op = abs($gastos_operacion->sum('final'));
        $utilidad_operacion = $utilidad_bruta - $total_gastos_op;

        $total_otros_ingresos = abs($otros_ingresos->sum('final'));
        $total_otros_gastos = abs($otros_gastos->sum('final'));
        $utilidad_neta = $utilidad_operacion + $total_otros_ingresos - $total_otros_gastos;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estado de Resultados');

        // Calcular acumulados (del inicio del ejercicio al periodo actual)
        $ingresos_acum = $this->obtenerSaldosAcumulados($team_id, '401', '499', $ejercicio, $periodo);
        $costo_ventas_acum = $this->obtenerSaldosAcumulados($team_id, '501', '509', $ejercicio, $periodo);
        $gastos_operacion_acum = $this->obtenerSaldosAcumulados($team_id, '510', '599', $ejercicio, $periodo);
        $otros_ingresos_acum = $this->obtenerSaldosAcumulados($team_id, '601', '699', $ejercicio, $periodo);
        $otros_gastos_acum = $this->obtenerSaldosAcumulados($team_id, '701', '799', $ejercicio, $periodo);

        $total_ingresos_acum = abs($ingresos_acum);
        $total_costo_acum = abs($costo_ventas_acum);
        $utilidad_bruta_acum = $total_ingresos_acum - $total_costo_acum;

        $total_gastos_op_acum = abs($gastos_operacion_acum);
        $utilidad_operacion_acum = $utilidad_bruta_acum - $total_gastos_op_acum;

        $total_otros_ingresos_acum = abs($otros_ingresos_acum);
        $total_otros_gastos_acum = abs($otros_gastos_acum);
        $utilidad_neta_acum = $utilidad_operacion_acum + $total_otros_ingresos_acum - $total_otros_gastos_acum;

        // Encabezado
        $sheet->setCellValue('A1', $empresa->name);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'ESTADO DE RESULTADOS INTEGRAL');
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $sheet->setCellValue('A3', 'Del 01/' . str_pad($periodo, 2, '0', STR_PAD_LEFT) . '/' . $ejercicio . ' al ' . $this->obtenerFechaCorte($periodo, $ejercicio));
        $sheet->mergeCells('A3:D3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Encabezados de columnas
        $row = 5;
        $sheet->setCellValue('A' . $row, 'CONCEPTO');
        $sheet->setCellValue('C' . $row, 'DEL MES');
        $sheet->setCellValue('D' . $row, 'ACUMULADO');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFBDC3C7');
        $sheet->getStyle('C' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;

        // INGRESOS
        $sheet->setCellValue('A' . $row, 'INGRESOS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2C3E50');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row++;

        foreach ($ingresos as $cuenta) {
            $cuenta_acum = $this->obtenerSaldoCuentaAcumulado($team_id, $cuenta->codigo, $ejercicio, $periodo);
            $sheet->setCellValue('A' . $row, '  ' . $cuenta->cuenta);
            $sheet->setCellValue('C' . $row, abs($cuenta->final));
            $sheet->setCellValue('D' . $row, abs($cuenta_acum));
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Ingresos');
        $sheet->setCellValue('C' . $row, $total_ingresos);
        $sheet->setCellValue('D' . $row, $total_ingresos_acum);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFECF0F1');
        $row += 2;

        // COSTO DE VENTAS
        $sheet->setCellValue('A' . $row, 'Costo de Ventas');
        $sheet->setCellValue('C' . $row, $total_costo);
        $sheet->setCellValue('D' . $row, $total_costo_acum);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        // UTILIDAD BRUTA
        $sheet->setCellValue('A' . $row, 'UTILIDAD BRUTA');
        $sheet->setCellValue('C' . $row, $utilidad_bruta);
        $sheet->setCellValue('D' . $row, $utilidad_bruta_acum);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF34495E');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row += 2;

        // GASTOS DE OPERACIÓN
        $sheet->setCellValue('A' . $row, 'Gastos de Operación');
        $sheet->setCellValue('C' . $row, $total_gastos_op);
        $sheet->setCellValue('D' . $row, $total_gastos_op_acum);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        // UTILIDAD DE OPERACIÓN
        $sheet->setCellValue('A' . $row, 'UTILIDAD DE OPERACIÓN');
        $sheet->setCellValue('C' . $row, $utilidad_operacion);
        $sheet->setCellValue('D' . $row, $utilidad_operacion_acum);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF34495E');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        $row += 2;

        // OTROS INGRESOS Y GASTOS
        if ($total_otros_ingresos > 0) {
            $sheet->setCellValue('A' . $row, 'Otros Ingresos');
            $sheet->setCellValue('C' . $row, $total_otros_ingresos);
            $sheet->setCellValue('D' . $row, $total_otros_ingresos_acum);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        if ($total_otros_gastos > 0) {
            $sheet->setCellValue('A' . $row, 'Otros Gastos');
            $sheet->setCellValue('C' . $row, $total_otros_gastos);
            $sheet->setCellValue('D' . $row, $total_otros_gastos_acum);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        if ($total_otros_ingresos > 0 || $total_otros_gastos > 0) {
            $row++;
        }

        // UTILIDAD NETA
        $sheet->setCellValue('A' . $row, 'UTILIDAD NETA');
        $sheet->setCellValue('C' . $row, $utilidad_neta);
        $sheet->setCellValue('D' . $row, $utilidad_neta_acum);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF27AE60');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);

        $filename = 'EstadoResultados_' . $periodo . '_' . $ejercicio . '.xlsx';
        $filepath = public_path('TMPCFDI/' . $filename);

        if (file_exists($filepath)) unlink($filepath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filename;
    }

    /**
     * Auxiliares exportable a Excel
     */
    public function auxiliaresExcel(Request $request)
    {
        $team_id = Filament::getTenant()->id;
        $cuenta_inicio = $request->cuenta_inicio;
        $cuenta_fin = $request->cuenta_fin;
        $periodo_inicio = $request->periodo_inicio;
        $ejercicio_inicio = $request->ejercicio_inicio;
        $periodo_fin = $request->periodo_fin;
        $ejercicio_fin = $request->ejercicio_fin;

        $empresa = DB::table('teams')->where('id', $team_id)->first();

        // Obtener cuentas en el rango
        $cuentas_query = DB::table('cat_cuentas')
            ->where('team_id', $team_id)
            ->where('codigo', '>=', $cuenta_inicio)
            ->where('codigo', '<=', $cuenta_fin)
            ->orderBy('codigo');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Auxiliares');

        // Encabezado
        $sheet->setCellValue('A1', $empresa->name);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'REPORTE DE AUXILIARES');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $sheet->setCellValue('A3', 'Del ' . $meses[$periodo_inicio] . ' ' . $ejercicio_inicio . ' al ' . $meses[$periodo_fin] . ' ' . $ejercicio_fin);
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;

        foreach ($cuentas_query->get() as $cuenta) {
            // Calcular saldo inicial
            $saldo_inicial = $this->calcularSaldoInicialCuenta($team_id, $cuenta->codigo, $cuenta->naturaleza, $ejercicio_inicio, $periodo_inicio);

            // Obtener auxiliares del periodo con información de póliza
            $auxiliares = Auxiliares::select('auxiliares.*', 'cat_polizas.folio as poliza_folio', 'cat_polizas.fecha', 'cat_polizas.tipo')
                ->leftJoin('cat_polizas', 'auxiliares.cat_polizas_id', '=', 'cat_polizas.id')
                ->where('auxiliares.team_id', $team_id)
                ->where('auxiliares.codigo', $cuenta->codigo)
                ->where(function($query) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                    if ($ejercicio_inicio == $ejercicio_fin) {
                        $query->where('auxiliares.a_ejercicio', '=', $ejercicio_inicio)
                              ->where('auxiliares.a_periodo', '>=', $periodo_inicio)
                              ->where('auxiliares.a_periodo', '<=', $periodo_fin);
                    } else {
                        $query->where(function($q) use ($ejercicio_inicio, $periodo_inicio, $ejercicio_fin, $periodo_fin) {
                            $q->where(function($subq) use ($ejercicio_inicio, $periodo_inicio) {
                                $subq->where('auxiliares.a_ejercicio', '=', $ejercicio_inicio)
                                     ->where('auxiliares.a_periodo', '>=', $periodo_inicio);
                            })
                            ->orWhere(function($subq) use ($ejercicio_inicio, $ejercicio_fin) {
                                $subq->where('auxiliares.a_ejercicio', '>', $ejercicio_inicio)
                                     ->where('auxiliares.a_ejercicio', '<', $ejercicio_fin);
                            })
                            ->orWhere(function($subq) use ($ejercicio_fin, $periodo_fin) {
                                $subq->where('auxiliares.a_ejercicio', '=', $ejercicio_fin)
                                     ->where('auxiliares.a_periodo', '<=', $periodo_fin);
                            });
                        });
                    }
                })
                ->orderBy('auxiliares.a_ejercicio')
                ->orderBy('auxiliares.a_periodo')
                ->orderBy('cat_polizas.folio')
                ->get();

            if ($auxiliares->count() > 0 || $saldo_inicial != 0) {
                // Encabezado de cuenta
                $sheet->setCellValue('A' . $row, $cuenta->codigo . ' - ' . $cuenta->nombre);
                $sheet->mergeCells('A' . $row . ':G' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E9F2');
                $row++;

                // Encabezados de columnas
                $headers = ['Fecha', 'Folio', 'Tipo', 'Concepto', 'Cargo', 'Abono', 'Saldo'];
                $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
                foreach ($cols as $index => $col) {
                    $sheet->setCellValue($col . $row, $headers[$index]);
                    $sheet->getStyle($col . $row)->getFont()->setBold(true);
                    $sheet->getStyle($col . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFECF0F1');
                }
                $row++;

                // Saldo inicial
                $sheet->setCellValue('D' . $row, 'SALDO INICIAL');
                $sheet->setCellValue('G' . $row, $saldo_inicial);
                $sheet->getStyle('D' . $row)->getFont()->setBold(true);
                $sheet->getStyle('G' . $row)->getFont()->setBold(true);
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $saldo_acumulado = $saldo_inicial;
                $row++;

                // Movimientos
                foreach ($auxiliares as $auxiliar) {
                    if ($cuenta->naturaleza == 'D') {
                        $saldo_acumulado += ($auxiliar->cargo - $auxiliar->abono);
                    } else {
                        $saldo_acumulado += ($auxiliar->abono - $auxiliar->cargo);
                    }

                    $sheet->setCellValue('A' . $row, $auxiliar->fecha ?? '');
                    $sheet->setCellValue('B' . $row, $auxiliar->poliza_folio ?? '');
                    $sheet->setCellValue('C' . $row, $auxiliar->tipo ?? '');
                    $sheet->setCellValue('D' . $row, $auxiliar->concepto ?? '');
                    $sheet->setCellValue('E' . $row, $auxiliar->cargo);
                    $sheet->setCellValue('F' . $row, $auxiliar->abono);
                    $sheet->setCellValue('G' . $row, $saldo_acumulado);

                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

                    $row++;
                }

                $row += 2;
            }
        }

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);

        $filename = 'Auxiliares_' . $periodo_inicio . '_' . $ejercicio_inicio . '_' . $periodo_fin . '_' . $ejercicio_fin . '.xlsx';
        $filepath = public_path('TMPCFDI/' . $filename);

        if (file_exists($filepath)) unlink($filepath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filename;
    }

    private function obtenerSaldosAcumulados($team_id, $inicio, $fin, $ejercicio, $periodo_fin)
    {
        $saldo_total = 0;
        $cuentas = SaldosReportes::where('team_id', $team_id)
            ->where('nivel', 1)
            ->where('codigo', '>=', $inicio . '00000')
            ->where('codigo', '<=', $fin . '99999')
            ->get();

        foreach ($cuentas as $cuenta) {
            // Sumar todos los movimientos desde inicio del ejercicio hasta el periodo fin
            $movimientos = Auxiliares::where('team_id', $team_id)
                ->where('codigo', $cuenta->codigo)
                ->where('a_ejercicio', $ejercicio)
                ->where('a_periodo', '<=', $periodo_fin)
                ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos'))
                ->first();

            if ($cuenta->naturaleza == 'D') {
                $saldo_total += ($movimientos->cargos - $movimientos->abonos);
            } else {
                $saldo_total += ($movimientos->abonos - $movimientos->cargos);
            }
        }

        return $saldo_total;
    }

    private function obtenerSaldoCuentaAcumulado($team_id, $codigo, $ejercicio, $periodo_fin)
    {
        $cuenta = DB::table('cat_cuentas')
            ->where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->first();

        if (!$cuenta) return 0;

        $movimientos = Auxiliares::where('team_id', $team_id)
            ->where('codigo', $codigo)
            ->where('a_ejercicio', $ejercicio)
            ->where('a_periodo', '<=', $periodo_fin)
            ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos'))
            ->first();

        if ($cuenta->naturaleza == 'D') {
            return $movimientos->cargos - $movimientos->abonos;
        } else {
            return $movimientos->abonos - $movimientos->cargos;
        }
    }
}
