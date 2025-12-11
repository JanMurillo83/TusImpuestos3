<?php

namespace App\Http\Controllers;

use App\Models\Auxiliares;
use App\Models\Facturas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminReportes extends Controller
{
    public function reporte_ventas($fecha_inicio,$fecha_fin,$cliente,$team_id):Collection
    {
        $fecha_inicio = Carbon::create($fecha_inicio)->format('Y-m-d');
        $fecha_fin = Carbon::create($fecha_fin)->format('Y-m-d');
        if($cliente == ''||$cliente == null) {
            $aux = Auxiliares::where('auxiliares.team_id', $team_id)
                ->join('cat_polizas', 'cat_polizas.id', '=', 'auxiliares.cat_polizas_id')
                ->where('auxiliares.codigo', 'LIKE', '401%')
                ->where('auxiliares.abono', '!=', 0)
                ->whereBetween('fecha', [$fecha_inicio, $fecha_fin])
                ->get();
            error_log('Sin Cliente');
        }
        else {
            $aux = Auxiliares::where('auxiliares.team_id', $team_id)
                ->join('cat_polizas', 'cat_polizas.id', '=', 'auxiliares.cat_polizas_id')
                ->where('auxiliares.codigo', 'LIKE', '401%')
                ->where('auxiliares.abono', '!=', 0)
                ->whereBetween('fecha', [$fecha_inicio, $fecha_fin])
                ->where('auxiliares.concepto', $cliente)
                ->get();
            error_log('Con Cliente');
        }
        return $aux;
    }

    public function reporte_facturacion($fecha_inicio,$fecha_fin,$team_id):Collection
    {
        $aux = Facturas::where('team_id',$team_id)
            ->whereBetween('fecha', [$fecha_inicio, $fecha_fin])
            ->get();
        return $aux;
    }
}
