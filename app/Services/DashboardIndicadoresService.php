<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardIndicadoresService
{
    public function obtenerIndicadoresFinancieros(int $tenantId, int $periodo, int $ejercicio): array
    {
        // Resumen financiero basado en saldos contables (nivel 1).
        $sumPeriodo = function (int $periodoCalc, string $condicion, bool $signed = false) use ($tenantId, $ejercicio): float {
            if ($periodoCalc < 1) {
                return 0.0;
            }
            $expr = $signed
                ? "SUM(CASE WHEN naturaleza = 'A' THEN (abonos - cargos) ELSE -(cargos - abonos) END)"
                : "SUM(CASE WHEN naturaleza = 'A' THEN (abonos - cargos) ELSE (cargos - abonos) END)";

            return (float) (DB::table('saldos_reportes')
                ->where('team_id', $tenantId)
                ->where('ejercicio', $ejercicio)
                ->where('periodo', $periodoCalc)
                ->where('nivel', 1)
                ->whereRaw($condicion)
                ->selectRaw("COALESCE($expr,0) as total")
                ->value('total') ?? 0);
        };

        $sumAcum = function (string $condicion, bool $signed = false) use ($tenantId, $ejercicio, $periodo): float {
            $expr = $signed
                ? "SUM(CASE WHEN naturaleza = 'A' THEN final ELSE -final END)"
                : "SUM(final)";

            return (float) (DB::table('saldos_reportes')
                ->where('team_id', $tenantId)
                ->where('ejercicio', $ejercicio)
                ->where('periodo', $periodo)
                ->where('nivel', 1)
                ->whereRaw($condicion)
                ->selectRaw("COALESCE($expr,0) as total")
                ->value('total') ?? 0);
        };

        $ingresosPeriodo = $sumPeriodo($periodo, "CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 4");
        $ingresosAcumulado = $sumAcum("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 4");

        $costosPeriodo = $sumPeriodo($periodo, "CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 5");
        $costosAcumulado = $sumAcum("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 5");

        $gastosPeriodo = $sumPeriodo($periodo, "CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 6");
        $gastosAcumulado = $sumAcum("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 6");

        $financiamientoPeriodo = $sumPeriodo($periodo, "CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) BETWEEN 702 AND 703", true);
        $financiamientoAcumulado = $sumAcum("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) BETWEEN 702 AND 703", true);

        $otrosPeriodo = $sumPeriodo($periodo, "CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) BETWEEN 700 AND 701", true);
        $otrosAcumulado = $sumAcum("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) BETWEEN 700 AND 701", true);

        $impuestosPeriodo = $sumPeriodo($periodo, "CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 8");
        $impuestosAcumulado = $sumAcum("CAST(SUBSTR(codigo, 1, 1) AS UNSIGNED) = 8");

        $utilidadBrutaPeriodo = $ingresosPeriodo - $costosPeriodo;
        $utilidadOperacionPeriodo = $utilidadBrutaPeriodo - $gastosPeriodo;
        $utilidadAntesImpuestosPeriodo = $utilidadOperacionPeriodo + $financiamientoPeriodo + $otrosPeriodo;
        $utilidadNetaPeriodo = $utilidadAntesImpuestosPeriodo - $impuestosPeriodo;

        $utilidadBrutaAcumulada = $ingresosAcumulado - $costosAcumulado;
        $utilidadOperacionAcumulada = $utilidadBrutaAcumulada - $gastosAcumulado;
        $utilidadAntesImpuestosAcumulada = $utilidadOperacionAcumulada + $financiamientoAcumulado + $otrosAcumulado;
        $utilidadNetaAcumulada = $utilidadAntesImpuestosAcumulada - $impuestosAcumulado;

        $ventasMes = (float) $ingresosPeriodo;
        $ventasAnio = (float) $ingresosAcumulado;

        $cuentasPorCobrar = (float) (DB::table('saldos_reportes')
            ->where('team_id', $tenantId)
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->where('nivel', 1)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) = 105")
            ->sum('final'));

        $cuentasPorPagar = (float) (DB::table('saldos_reportes')
            ->where('team_id', $tenantId)
            ->where('ejercicio', $ejercicio)
            ->where('periodo', $periodo)
            ->where('nivel', 1)
            ->whereRaw("CAST(SUBSTR(codigo, 1, 3) AS UNSIGNED) = 201")
            ->sum('final'));

        $tablaInventario = Schema::hasTable('inventarios') ? 'inventarios' : (Schema::hasTable('inventario') ? 'inventario' : null);
        $inventario = 0.0;
        if ($tablaInventario) {
            $inventario = (float) (DB::table($tablaInventario)
                ->where('team_id', $tenantId)
                ->selectRaw('COALESCE(SUM(p_costo * exist), 0) as importe')
                ->value('importe') ?? 0);
        }

        // Saldos de bancos y cartera vencida se calculan con movimientos y auxiliares.
        $saldoBancos = $this->calcularSaldoBancos($tenantId, $periodo, $ejercicio);

        $carteraVencida = $this->calcularCarteraVencida($tenantId, $periodo, $ejercicio);

        return [
            'ventas_mes' => $ventasMes,
            'ventas_anio' => $ventasAnio,
            'utilidad_periodo' => (float) $utilidadNetaPeriodo,
            'utilidad_acumulada' => (float) $utilidadNetaAcumulada,
            'saldo_bancos' => $saldoBancos,
            'cuentas_por_cobrar' => $cuentasPorCobrar,
            'cartera_vencida' => $carteraVencida,
            'cuentas_por_pagar' => $cuentasPorPagar,
            'inventario' => $inventario,
        ];
    }

    public function obtenerIndicadoresComerciales(int $tenantId, Carbon $inicio, Carbon $fin): array
    {
        // Resumen comercial con cotizaciones y facturas del periodo.
        $cotizacionesBase = DB::table('cotizaciones')
            ->where('team_id', $tenantId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

        $numeroCotizaciones = (int) (clone $cotizacionesBase)->count();
        $montoCotizado = (float) (clone $cotizacionesBase)->sum('total');

        $facturasBase = DB::table('facturas')
            ->where('team_id', $tenantId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

        $facturacion = (float) (clone $facturasBase)->sum('total');
        $ventasDirectas = (float) (clone $facturasBase)->whereNull('cotizacion_id')->sum('total');

        $facturasConCotizacion = DB::table('facturas')
            ->where('team_id', $tenantId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->whereNotNull('cotizacion_id')
            ->get(['cotizacion_id', 'total', 'fecha']);

        $facturadoDesdeCotizaciones = (float) $facturasConCotizacion->sum('total');
        $cotizacionesFacturadasIds = $facturasConCotizacion->pluck('cotizacion_id')->unique()->filter()->values();
        $cotizacionesFacturadas = $cotizacionesFacturadasIds->count();

        $conversionComercial = $numeroCotizaciones > 0 ? ($cotizacionesFacturadas / $numeroCotizaciones) : 0;
        $conversionPonderada = $montoCotizado > 0 ? ($facturadoDesdeCotizaciones / $montoCotizado) : 0;

        $cicloPromedio = $this->calcularCicloPromedio($tenantId, $inicio, $fin);

        return [
            'numero_cotizaciones' => $numeroCotizaciones,
            'monto_cotizado' => $montoCotizado,
            'facturacion' => $facturacion,
            'conversion_comercial' => round($conversionComercial * 100, 1),
            'conversion_ponderada' => round($conversionPonderada * 100, 1),
            'ciclo_promedio' => $cicloPromedio,
            'ventas_directas' => $ventasDirectas,
        ];
    }

    private function calcularSaldoBancos(int $tenantId, int $periodo, int $ejercicio): float
    {
        $cuentas = DB::table('banco_cuentas')
            ->where('team_id', $tenantId)
            ->get(['id', 'inicial']);

        $saldoTotal = 0.0;
        foreach ($cuentas as $cuenta) {
            $entradasAct = (float) (DB::table('movbancos')
                ->where('cuenta', $cuenta->id)
                ->where('tipo', 'E')
                ->where('ejercicio', $ejercicio)
                ->where('periodo', $periodo)
                ->sum('importe') ?? 0);
            $salidasAct = (float) (DB::table('movbancos')
                ->where('cuenta', $cuenta->id)
                ->where('tipo', 'S')
                ->where('ejercicio', $ejercicio)
                ->where('periodo', $periodo)
                ->sum('importe') ?? 0);
            $entradasAnt = (float) (DB::table('movbancos')
                ->where('cuenta', $cuenta->id)
                ->where('tipo', 'E')
                ->where('ejercicio', $ejercicio)
                ->where('periodo', '<', $periodo)
                ->sum('importe') ?? 0);
            $salidasAnt = (float) (DB::table('movbancos')
                ->where('cuenta', $cuenta->id)
                ->where('tipo', 'S')
                ->where('ejercicio', $ejercicio)
                ->where('periodo', '<', $periodo)
                ->sum('importe') ?? 0);

            $inicial = (float) $cuenta->inicial + $entradasAnt - $salidasAnt;
            $actual = $inicial + $entradasAct - $salidasAct;
            $saldoTotal += $actual;
        }

        return $saldoTotal;
    }

    private function calcularCarteraVencida(int $tenantId, int $periodo, int $ejercicio): float
    {
        $corte = Carbon::create($ejercicio, $periodo, 1)->format('Y-m-d');

        $facturas = DB::table('auxiliares')
            ->join('cat_polizas', 'cat_polizas.id', '=', 'auxiliares.cat_polizas_id')
            ->where('auxiliares.team_id', $tenantId)
            ->where('auxiliares.codigo', 'like', '105%')
            ->selectRaw('auxiliares.factura as factura, auxiliares.cuenta as cliente, MIN(cat_polizas.fecha) as fecha, SUM(auxiliares.cargo) as cargos, SUM(auxiliares.abono) as abonos')
            ->groupBy('auxiliares.factura', 'auxiliares.cuenta')
            ->get();

        $vencido = 0.0;
        foreach ($facturas as $row) {
            $saldo = (float) $row->cargos - (float) $row->abonos;
            if ($saldo <= 0) {
                continue;
            }
            $fecha = Carbon::create($row->fecha)->format('Y-m-d');
            $vencimiento = Carbon::create($fecha)->addDays(30)->format('Y-m-d');
            if ($vencimiento <= $corte) {
                $vencido += $saldo;
            }
        }

        return $vencido;
    }

    private function calcularCicloPromedio(int $tenantId, Carbon $inicio, Carbon $fin): float
    {
        $rows = DB::table('facturas')
            ->join('cotizaciones', 'cotizaciones.id', '=', 'facturas.cotizacion_id')
            ->where('facturas.team_id', $tenantId)
            ->whereBetween('facturas.fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->select('facturas.fecha as fecha_factura', 'cotizaciones.fecha as fecha_cotizacion')
            ->get();

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += Carbon::parse($row->fecha_cotizacion)->diffInDays(Carbon::parse($row->fecha_factura));
        }

        return round($sum / $rows->count(), 1);
    }
}
