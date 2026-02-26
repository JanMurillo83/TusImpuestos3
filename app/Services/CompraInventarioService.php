<?php

namespace App\Services;

use App\Models\Compras;
use App\Models\Inventario;
use App\Models\Movinventario;
use Carbon\Carbon;

class CompraInventarioService
{
    public static function aplicarEntrada(Compras $compra): void
    {
        $compra->loadMissing('partidas');

        foreach ($compra->partidas as $partida) {
            if (! $partida->item) {
                continue;
            }

            $inve = Inventario::where('id', $partida->item)->first();
            if (! $inve || ($inve->servicio ?? 'NO') === 'SI') {
                continue;
            }

            Movinventario::insert([
                'producto' => $partida->item,
                'tipo' => 'Entrada',
                'fecha' => Carbon::now(),
                'cant' => $partida->cant,
                'costo' => $partida->costo,
                'precio' => 0,
                'concepto' => 1,
                'tipoter' => 'P',
                'tercero' => $compra->prov,
                'team_id' => $compra->team_id,
            ]);

            $exist = (float) ($inve->exist ?? 0);
            $cant = (float) ($partida->cant ?? 0);
            $cost = (float) ($partida->costo ?? 0);
            $newExist = $exist + $cant;
            $avgBase = (float) ($inve->p_costo ?? 0) * $exist;
            $newAvg = $newExist > 0 ? ($avgBase + ($cant * $cost)) / $newExist : $cost;

            Inventario::where('id', $partida->item)->update([
                'exist' => $newExist,
                'u_costo' => $cost,
                'p_costo' => $newAvg,
            ]);
        }
    }
}
