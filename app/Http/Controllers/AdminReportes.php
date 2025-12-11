<?php

namespace App\Http\Controllers;

use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\Facturas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function reporte_cuentascobrar($fecha_inicio,$fecha_fin,$cliente,$team_id):Collection
    {
        if($cliente == ''||$cliente == null) {
            $codi = DB::table('cat_cuentas')
                ->where('acumula', '10500000')
                ->where('team_id', $team_id)->pluck('codigo');
            $codigos = DB::table('cat_cuentas')
                ->whereIn('acumula', $codi)
                ->where('team_id', $team_id)->pluck('codigo');
            error_log("Ctas_X_Cob1:" . count($codigos));
        }else{
            $cuen_cliente = CatCuentas::where(DB::raw("TRIM(nombre)"),trim($cliente))
            ->where('codigo','like','105%')->first();
            $codigos = [$cuen_cliente?->codigo ?? '10500000'];
        }
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->whereBetween('fecha', [$fecha_inicio, $fecha_fin])
            ->whereIn('codigo',$codigos)
            ->join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->orderBy('factura')
            ->orderBy('cargo','desc')
            ->get();
        error_log("Ctas_X_Cob2:".count($auxiliares));
        return $auxiliares;
    }

    public function reporte_cuentaspagar($fecha_inicio,$fecha_fin,$cliente,$team_id):Collection
    {
        if($cliente == ''||$cliente == null) {
            $codi = DB::table('cat_cuentas')
                ->where('acumula', '20100000')
                ->where('team_id', $team_id)->pluck('codigo');
            $codigos = DB::table('cat_cuentas')
                ->whereIn('acumula', $codi)
                ->where('team_id', $team_id)->pluck('codigo');
            error_log("Ctas_X_Cob1:" . count($codigos));
        }else{
            $cuen_cliente = CatCuentas::where(DB::raw("TRIM(nombre)"),trim($cliente))
                ->where('codigo','like','201%')->first();
            $codigos = [$cuen_cliente?->codigo ?? '20100000'];
        }
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->whereBetween('fecha', [$fecha_inicio, $fecha_fin])
            ->whereIn('codigo',$codigos)
            ->join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->orderBy('factura')
            ->orderBy('abono','desc')
            ->get();
        error_log("Ctas_X_Cob2:".count($auxiliares));
        return $auxiliares;
    }
}
