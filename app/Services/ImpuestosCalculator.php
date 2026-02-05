<?php

namespace App\Services;

use App\Models\Esquemasimp;
use App\Models\Inventario;

class ImpuestosCalculator
{
    public static function fromInventario(?int $itemId, float $subtotal, ?int $fallbackEsquemaId = null): array
    {
        $esquemaId = null;

        if ($itemId) {
            $esquemaId = Inventario::where('id', $itemId)->value('esquema');
        }

        if (!$esquemaId && $fallbackEsquemaId) {
            $esquemaId = $fallbackEsquemaId;
        }

        return self::calculateFromEsquemaId($esquemaId, $subtotal);
    }

    public static function fromEsquema(?int $esquemaId, float $subtotal): array
    {
        return self::calculateFromEsquemaId($esquemaId, $subtotal);
    }

    private static function calculateFromEsquemaId(?int $esquemaId, float $subtotal): array
    {
        $esquema = $esquemaId ? Esquemasimp::find($esquemaId) : null;

        if (!$esquema) {
            return [
                'iva' => 0,
                'retiva' => 0,
                'retisr' => 0,
                'ieps' => 0,
                'total' => $subtotal,
                'por_imp1' => 0,
                'por_imp2' => 0,
                'por_imp3' => 0,
                'por_imp4' => 0,
            ];
        }

        $iva = $subtotal * ((float) $esquema->iva * 0.01);
        $retiva = $subtotal * ((float) $esquema->retiva * 0.01);
        $retisr = $subtotal * ((float) $esquema->retisr * 0.01);
        $ieps = $subtotal * ((float) $esquema->ieps * 0.01);

        return [
            'iva' => $iva,
            'retiva' => $retiva,
            'retisr' => $retisr,
            'ieps' => $ieps,
            'total' => $subtotal + $iva - $retiva - $retisr + $ieps,
            'por_imp1' => (float) $esquema->iva,
            'por_imp2' => (float) $esquema->retiva,
            'por_imp3' => (float) $esquema->retisr,
            'por_imp4' => (float) $esquema->ieps,
        ];
    }
}
