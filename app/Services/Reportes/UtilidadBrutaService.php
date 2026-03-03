<?php

namespace App\Services\Reportes;

use App\Models\Compras;
use App\Models\Facturas;
use Carbon\Carbon;

class UtilidadBrutaService
{
    /**
     * Construye el reporte de utilidad bruta para un periodo.
     *
     * - Ventas: Facturas Timbradas en el periodo.
     * - Costos: Compras Activas generadas desde Ordenes (orden_id no nulo) en el periodo.
     * - Consolidación: convierte a MXN usando tcambio cuando moneda=USD.
     */
    public function build(int $teamId, ?string $fechaInicio, ?string $fechaFin): array
    {
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio)->format('Y-m-d') : null;
        $fin = $fechaFin ? Carbon::parse($fechaFin)->format('Y-m-d') : null;

        $facturasQuery = Facturas::query()
            ->where('team_id', $teamId)
            ->where('estado', 'Timbrada');

        if ($inicio) {
            $facturasQuery->whereDate('fecha', '>=', $inicio);
        }
        if ($fin) {
            $facturasQuery->whereDate('fecha', '<=', $fin);
        }

        $facturas = $facturasQuery
            ->orderBy('fecha')
            ->get(['id', 'serie', 'folio', 'docto', 'fecha', 'nombre', 'moneda', 'tcambio', 'subtotal']);

        $comprasQuery = Compras::query()
            ->where('team_id', $teamId)
            ->where('estado', 'Activa')
            ->whereNotNull('orden_id');

        if ($inicio) {
            $comprasQuery->whereDate('fecha', '>=', $inicio);
        }
        if ($fin) {
            $comprasQuery->whereDate('fecha', '<=', $fin);
        }

        $compras = $comprasQuery
            ->orderBy('fecha')
            ->get(['id', 'serie', 'folio', 'docto', 'fecha', 'nombre', 'moneda', 'tcambio', 'subtotal', 'orden_id']);

        $ventasRows = $facturas->map(function (Facturas $f): array {
            $doc = $this->resolveDocumento($f->docto, $f->serie, $f->folio);
            $tc = $this->resolveTcambio($f->tcambio);
            $moneda = $f->moneda ?: 'MXN';
            $subtotal = (float) ($f->subtotal ?? 0);
            $subtotalMxn = $moneda === 'USD' ? $subtotal * $tc : $subtotal;

            return [
                'tipo' => 'Factura',
                'documento' => $doc,
                'fecha' => $f->fecha,
                'tercero' => $f->nombre,
                'moneda' => $moneda,
                'tcambio' => $tc,
                'subtotal' => $subtotal,
                'subtotal_mxn' => $subtotalMxn,
            ];
        })->values()->all();

        $costosRows = $compras->map(function (Compras $c): array {
            $doc = $this->resolveDocumento($c->docto, $c->serie, $c->folio);
            $tc = $this->resolveTcambio($c->tcambio);
            $moneda = $c->moneda ?: 'MXN';
            $subtotal = (float) ($c->subtotal ?? 0);
            $subtotalMxn = $moneda === 'USD' ? $subtotal * $tc : $subtotal;

            return [
                'tipo' => 'Compra (OC)',
                'documento' => $doc,
                'fecha' => $c->fecha,
                'tercero' => $c->nombre,
                'moneda' => $moneda,
                'tcambio' => $tc,
                'subtotal' => $subtotal,
                'subtotal_mxn' => $subtotalMxn,
            ];
        })->values()->all();

        $ventasMxn = array_sum(array_column($ventasRows, 'subtotal_mxn'));
        $costosMxn = array_sum(array_column($costosRows, 'subtotal_mxn'));
        $utilidadMxn = $ventasMxn - $costosMxn;
        $margen = $ventasMxn > 0 ? ($utilidadMxn / $ventasMxn) : 0;

        return [
            'periodo' => [
                'inicio' => $inicio,
                'fin' => $fin,
            ],
            'ventas' => $ventasRows,
            'costos' => $costosRows,
            'totales' => [
                'ventas_mxn' => $ventasMxn,
                'costos_mxn' => $costosMxn,
                'utilidad_mxn' => $utilidadMxn,
                'margen' => $margen,
            ],
        ];
    }

    private function resolveDocumento(?string $docto, ?string $serie, $folio): string
    {
        $docto = trim((string) ($docto ?? ''));
        if ($docto !== '') {
            return $docto;
        }

        $serie = trim((string) ($serie ?? ''));
        $folio = trim((string) ($folio ?? ''));
        $alt = trim($serie . $folio);

        return $alt !== '' ? $alt : '-';
    }

    private function resolveTcambio($tcambio): float
    {
        $tc = (float) ($tcambio ?? 1);
        return $tc > 0 ? $tc : 1.0;
    }
}
