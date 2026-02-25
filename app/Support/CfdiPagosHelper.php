<?php

namespace App\Support;

class CfdiPagosHelper
{
    public static function findPagosComplement($comprobante)
    {
        $pagoscom = $comprobante->searchNode('cfdi:Complemento', 'pago20:Pagos');
        if (! $pagoscom) {
            $pagoscom = $comprobante->searchNode('cfdi:Complemento', 'pago10:Pagos');
        }

        return $pagoscom;
    }

    public static function calculatePagosTotales($pagoscom): array
    {
        $totales = [
            'subtotal' => 0.0,
            'traslado' => 0.0,
            'retencion' => 0.0,
            'total' => 0.0,
            'tipocambio' => 1.0,
        ];

        if (! $pagoscom) {
            return $totales;
        }

        $pagostot = $pagoscom->searchNode('pago20:Totales');
        if ($pagostot) {
            $totales['subtotal'] = floatval($pagostot['TotalTrasladosBaseIVA16'] ?? 0);
            $totales['traslado'] = floatval($pagostot['TotalTrasladosImpuestoIVA16'] ?? 0);
            $totales['total'] = floatval($pagostot['MontoTotalPagos'] ?? 0);
            return $totales;
        }

        $pagos = $pagoscom->searchNodes('pago20:Pago');
        if (! $pagos) {
            $pagos = $pagoscom->searchNodes('pago10:Pago');
        }

        foreach ($pagos as $pago) {
            $totales['total'] += floatval($pago['Monto'] ?? 0);
        }

        return $totales;
    }
}
