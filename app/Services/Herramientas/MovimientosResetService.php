<?php

namespace App\Services\Herramientas;

use Illuminate\Support\Facades\DB;

class MovimientosResetService
{
    /**
     * Elimina movimientos operativos del team indicado.
     *
     * @return array<string,int> Conteos de filas eliminadas por tabla.
     */
    public function purgeForTeam(int $teamId): array
    {
        return DB::transaction(function () use ($teamId): array {
            $counts = [];

            // Movimientos bancarios
            $counts['movbancos'] = DB::table('movbancos')->where('team_id', $teamId)->delete();

            // Cotizaciones (incluye actividades y partidas)
            $cotizacionesIds = DB::table('cotizaciones')->where('team_id', $teamId)->pluck('id');

            $counts['cotizacion_actividades'] = $cotizacionesIds->isEmpty()
                ? 0
                : DB::table('cotizacion_actividades')->whereIn('cotizacion_id', $cotizacionesIds)->delete();

            $counts['cotizaciones_partidas'] = DB::table('cotizaciones_partidas')->where('team_id', $teamId)->delete();
            $counts['cotizaciones'] = DB::table('cotizaciones')->where('team_id', $teamId)->delete();

            // Facturas
            $counts['facturas_partidas'] = DB::table('facturas_partidas')->where('team_id', $teamId)->delete();
            $counts['facturas'] = DB::table('facturas')->where('team_id', $teamId)->delete();

            // Órdenes de compra
            $counts['ordenes_partidas'] = DB::table('ordenes_partidas')->where('team_id', $teamId)->delete();
            $counts['ordenes'] = DB::table('ordenes')->where('team_id', $teamId)->delete();

            // Órdenes de insumos
            $counts['ordenes_insumos_partidas'] = DB::table('ordenes_insumos_partidas')->where('team_id', $teamId)->delete();
            $counts['ordenes_insumos'] = DB::table('ordenes_insumos')->where('team_id', $teamId)->delete();

            return $counts;
        });
    }
}
