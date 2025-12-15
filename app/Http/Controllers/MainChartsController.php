<?php

namespace App\Http\Controllers;

use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Collection;

class MainChartsController extends Controller
{
    public function mainview($team_id)
    {
        return view('MainPage',['team_id' => $team_id]);
    }
    public function mes_letras($mes_act):string
    {
        $mes_let = '';
        switch ($mes_act){
            case 1: $mes_let = 'Enero'; break;
            case 2: $mes_let = 'Febrero'; break;
            case 3: $mes_let = 'Marzo'; break;
            case 4: $mes_let = 'Abril'; break;
            case 5: $mes_let = 'Mayo'; break;
            case 6: $mes_let = 'Junio'; break;
            case 7: $mes_let = 'Julio'; break;
            case 8: $mes_let = 'Agosto'; break;
            case 9: $mes_let = 'Septiembre'; break;
            case 10: $mes_let = 'Octubre'; break;
            case 11: $mes_let = 'Noviembre'; break;
            case 12: $mes_let = 'Diciembre'; break;
        }
        return $mes_let;
    }
    public function GeneraCargos($team_id,$cuenta,$periodo,$ejercicio):float
    {
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('team_id', $team_id)
            ->where('a_periodo', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('cargo', '>', 0)
            ->whereIn('codigo', $codigos)->get();
        return $auxiliares->sum('cargo');
    }

    public function GeneraCargos_an($team_id,$cuenta,$periodo,$ejercicio):float
    {
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('team_id', $team_id)
            ->where('a_periodo','<=', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('cargo', '>', 0)
            ->whereIn('codigo', $codigos)->get();
        return $auxiliares->sum('cargo');
    }

    public function GeneraAbonos($team_id,$cuenta,$periodo,$ejercicio):float
    {
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('team_id', $team_id)
            ->where('a_periodo', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('abono', '>', 0)
            ->whereIn('codigo',$codigos)->get();
        error_log(count($auxiliares));
        return $auxiliares->sum('abono');
    }
    public function GeneraAbonos_an($team_id,$cuenta,$periodo,$ejercicio):float
    {
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('team_id', $team_id)
            ->where('a_periodo','<=', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('abono', '>', 0)
            ->whereIn('codigo',$codigos)
            ->get();
        error_log(count($auxiliares));
        return $auxiliares->sum('abono');
    }

    public function GeneraAbonos_Aux($team_id,$cuenta,$periodo,$ejercicio):\Illuminate\Support\Collection
    {
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('team_id', $team_id)
            ->where('a_periodo', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('abono', '>', 0)
            ->select(DB::raw('sum(abono) as abono'),'concepto')
            ->groupBy('concepto')
            ->orderBy('abono','desc')
            ->whereIn('codigo',$codigos)->get();
        return $auxiliares;
    }

    public function GeneraAbonos_Aux_an($team_id,$cuenta,$periodo,$ejercicio):\Illuminate\Support\Collection
    {
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('team_id', $team_id)
            ->where('a_periodo','<=', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('abono', '>', 0)
            ->select(DB::raw('sum(abono) as abono'),'concepto')
            ->groupBy('concepto')
            ->orderBy('abono','desc')
            ->whereIn('codigo',$codigos)->get();
        return $auxiliares;
    }
    public function GeneraAbonos_Aux_Detalle(Request $request):\Illuminate\Support\Collection
    {
        $team_id = $request->team_id;
        $cuenta = $request->cuenta;
        $periodo = $request->periodo;
        $ejercicio = $request->ejercicio;
        $concepto = $request->concepto;
        error_log("Concepto:".$concepto);
        error_log("Cuenta:".$cuenta);
        error_log("Periodo:".$periodo);
        error_log("Ejercicio:".$ejercicio);
        error_log("TeamID:".$team_id);
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('abono', '>', 0)
            ->where('auxiliares.concepto',$concepto)
            ->orderBy('abono','desc')
            ->whereIn('codigo',$codigos)
            ->join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->get();
        error_log("Resultados:".count($auxiliares));
        return $auxiliares;
    }

    public function GeneraAbonos_Aux_Detalle_an(Request $request):\Illuminate\Support\Collection
    {
        $team_id = $request->team_id;
        $cuenta = $request->cuenta;
        $periodo = $request->periodo;
        $ejercicio = $request->ejercicio;
        $concepto = $request->concepto;
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo','<=', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('abono', '>', 0)
            ->where('auxiliares.concepto',$concepto)
            ->orderBy('abono','desc')
            ->whereIn('codigo',$codigos)
            ->join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->get();
        error_log("Resultados:".count($auxiliares));
        return $auxiliares;
    }

    public function CuentasCobrar($team_id,$cuenta,$mes_act,$eje_act):\Illuminate\Support\Collection
    {
        $codi = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Ctas_X_Cob1:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo','<=', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->whereIn('codigo',$codigos)
            ->orderBy(DB::raw('sum(cargo - abono)'),'desc')
            ->select(DB::raw('sum(cargo - abono)  as importe'),'concepto')
            ->groupBy('concepto')
            ->get();
        error_log("Ctas_X_Cob2:".count($auxiliares));
        return $auxiliares;
    }

    public function CuentasCobrar_detalle(Request $request):\Illuminate\Support\Collection
    {
        $team_id = $request->team_id;
        $cuenta = $request->cuenta;
        $mes_act = $request->periodo;
        $eje_act = $request->ejercicio;
        $concepto = $request->concepto;
        $codi = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Ctas_X_Cob1:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo','<=', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->where('auxiliares.concepto', $concepto)
            ->whereIn('codigo',$codigos)
            ->join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->orderBy('factura')
            ->orderBy('cargo','desc')
            ->get();
        error_log("Ctas_X_Cob2:".count($auxiliares));
        return $auxiliares;
    }

    public function CuentasPagar($team_id,$cuenta,$mes_act,$eje_act):\Illuminate\Support\Collection
    {
        $codi = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Ctas_X_Pag1:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo','<=', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->whereIn('codigo',$codigos)
            ->orderBy(DB::raw('sum(abono - cargo)'),'desc')
            ->select(DB::raw('sum(abono - cargo)  as importe'),'concepto')
            ->groupBy('concepto')
            ->get();
        error_log("Ctas_X_Pag2:".count($auxiliares));
        return $auxiliares;
    }
    public function CuentasPagar_detalle(Request $request):\Illuminate\Support\Collection
    {
        $team_id = $request->team_id;
        $cuenta = $request->cuenta;
        $mes_act = $request->periodo;
        $eje_act = $request->ejercicio;
        $concepto = $request->concepto;
        $codi = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Ctas_X_Pag:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo','<=', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->where('auxiliares.concepto', $concepto)
            ->whereIn('codigo',$codigos)
            ->join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->orderBy('factura')
            ->orderBy('abono','desc')
            ->get();
        error_log("Ctas_X_Pag:".count($auxiliares));
        return $auxiliares;
    }
    public function UtilidadPeriodo($team_id,$mes_act,$eje_act):\Illuminate\Support\Collection
    {
        $codi = DB::table('cat_cuentas')
            ->where('acumula','40000000')
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Utilidad:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->whereIn('auxiliares.codigo',$codigos)
            ->get();
        error_log("Utilidad1:".count($auxiliares));
        return $auxiliares;
    }
    public function UtilidadPeriodoGastos($team_id,$mes_act,$eje_act):\Illuminate\Support\Collection
    {
        $codi = DB::table('cat_cuentas')
            ->whereIn('acumula',['50000000','60000000','70000000'])
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Utilidad:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->whereIn('auxiliares.codigo',$codigos)
            ->get();
        error_log("Utilidad1:".count($auxiliares));
        return $auxiliares;
    }

    public function UtilidadEjercicio($team_id,$mes_act,$eje_act):\Illuminate\Support\Collection
    {
        $codi = DB::table('cat_cuentas')
            ->where('acumula','40000000')
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Utilidad:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo','<=', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->whereIn('auxiliares.codigo',$codigos)
            ->join('cat_cuentas','cat_cuentas.codigo','=','auxiliares.codigo')
            ->select(DB::raw("IF (cat_cuentas.naturaleza = 'D',sum(cargo)-sum(abono),sum(abono)-sum(cargo)) as importe")
                ,'auxiliares.codigo')
            ->groupBy('auxiliares.codigo')
            ->groupBy('cat_cuentas.naturaleza')
            ->get();
        error_log("Utilidad1:".count($auxiliares));
        return $auxiliares;
    }

    public function UtilidadEjercicioGastos($team_id,$mes_act,$eje_act):\Illuminate\Support\Collection
    {
        $codi = DB::table('cat_cuentas')
            ->whereIn('acumula',['50000000','60000000','70000000'])
            ->where('team_id',$team_id)->pluck('codigo');
        $codigos = DB::table('cat_cuentas')
            ->whereIn('acumula',$codi)
            ->where('team_id',$team_id)->pluck('codigo');
        error_log("Utilidad:".count($codigos));
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo','<=', $mes_act)
            ->where('a_ejercicio', $eje_act)
            ->whereIn('auxiliares.codigo',$codigos)
            ->join('cat_cuentas','cat_cuentas.codigo','=','auxiliares.codigo')
            ->select(DB::raw("IF (cat_cuentas.naturaleza = 'D',sum(cargo)-sum(abono),sum(abono)-sum(cargo)) as importe")
            ,'auxiliares.codigo')
            ->groupBy('auxiliares.codigo')
            ->groupBy('cat_cuentas.naturaleza')
            ->get();
        error_log("Utilidad1:".count($auxiliares));
        return $auxiliares;
    }

    public function GetAuxiliares($team_id,$cuenta,$periodo,$ejercicio):\Illuminate\Support\Collection
    {
        $codigos = DB::table('cat_cuentas')
            ->where('acumula',$cuenta)
            ->where('team_id',$team_id)->pluck('codigo');
        $auxiliares = DB::table('auxiliares')
            ->where('auxiliares.team_id', $team_id)
            ->where('a_periodo', $periodo)
            ->where('a_ejercicio', $ejercicio)
            ->where('abono', '>', 0)
            ->orderBy('abono','desc')
            ->join('cat_polizas','cat_polizas.id','=','auxiliares.cat_polizas_id')
            ->whereIn('codigo',$codigos)->get();
        return $auxiliares;
    }

    public function GetUtilidadPeriodo($team_id): float
    {
        $cuentas = DB::select("SELECT * FROM saldos_reportes
        WHERE nivel = 1 AND team_id = $team_id
        AND (cargos+abonos) != 0");
        $importe = 0;
        foreach ($cuentas as $cuenta) {
            $cod = intval(substr($cuenta->codigo, 0, 3));
            if ($cod > 399) {
                if ($cuenta->naturaleza == 'A') {
                    $importe += ($cuenta->cargos-$cuenta->abonos);
                } else {
                    $importe -= ($cuenta->abonos-$cuenta->cargos);
                }
            }
        }
        return $importe;
    }

    public function GetUtilidadEjercicio($team_id): float
    {
        $cuentas = DB::select("SELECT * FROM saldos_reportes
        WHERE nivel = 1 AND team_id = $team_id
        AND (anterior+cargos+abonos) != 0");
        $importe = 0;
        foreach ($cuentas as $cuenta) {
            $cod = intval(substr($cuenta->codigo, 0, 3));
            if ($cod > 399) {
                if ($cuenta->naturaleza == 'A') {
                    $importe += $cuenta->final;
                } else {
                    $importe -= $cuenta->final;
                }
            }
        }
        return $importe;
    }

    public function GetCobrar($team_id):float
    {
        $auxiliares = SaldosReportes::where('codigo','10500000')
        ->where('team_id',$team_id)->first();
        return ($auxiliares->anterior+$auxiliares->cargos-$auxiliares->abonos);
    }

    public function GetPagar($team_id):float
    {
        $auxiliares = SaldosReportes::where('codigo','20100000')
            ->where('team_id',$team_id)->first();
        return ($auxiliares->anterior+$auxiliares->abonos-$auxiliares->cargos);
    }
    public function GetUtiPer($team_id):float
    {
        $ventas = SaldosReportes::where('acumula','40000000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $venta = ($ventas->sum('abonos')-$ventas->sum('cargos'));
        $costos = SaldosReportes::where('acumula','50000000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $costo = ($costos->sum('cargos')-$costos->sum('abonos'));
        $gastos = SaldosReportes::where('acumula','60000000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $gasto = ($gastos->sum('cargos')-$gastos->sum('abonos'));
        $gfinans = SaldosReportes::where('codigo','70100000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $gfinan = ($gfinans->sum('cargos')-$gfinans->sum('abonos'));
        $pfinans = SaldosReportes::where('codigo','70200000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $pfinan = ($pfinans->sum('abonos')-$pfinans->sum('cargos'));
        $importe = $venta-$costo-$gasto-$gfinan-$pfinan;
        //dd($venta,$costo,$gasto,$gfinan,$pfinan);
        return floatval($importe);
    }
    public function GetUtiPerEjer($team_id):float
    {
        $ventas = SaldosReportes::where('acumula','40000000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $venta = ($ventas->sum('anterior')+$ventas->sum('abonos')-$ventas->sum('cargos'));
        $costos = SaldosReportes::where('acumula','50000000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $costo = ($costos->sum('anterior')+$costos->sum('cargos')-$costos->sum('abonos'));
        $gastos = SaldosReportes::where('acumula','60000000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $gasto = ($gastos->sum('anterior')+$gastos->sum('cargos')-$gastos->sum('abonos'));
        $gfinans = SaldosReportes::where('codigo','70100000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $gfinan = ($gfinans->sum('anterior')+$gfinans->sum('cargos')-$gfinans->sum('abonos'));
        $pfinans = SaldosReportes::where('codigo','70200000')
            ->where('team_id',$team_id)->where('nivel',1)->get();
        $pfinan = ($pfinans->sum('anterior')+$pfinans->sum('abonos')-$pfinans->sum('cargos'));
        $importe = $venta-$costo-$gasto-$gfinan-$pfinan;
        //dd($venta,$costo,$gasto,$gfinan,$pfinan);
        return floatval($importe);
    }

}

