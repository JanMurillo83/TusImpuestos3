<?php

use App\Models\Auxiliares;
use App\Models\CatPolizas;
use App\Models\SaldosReportes;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

$team_id = Filament::getTenant()->id;
$ejercicio = Filament::getTenant()->ejercicio;
$periodo = Filament::getTenant()->periodo;

$polizas = CatPolizas::where('team_id',$team_id)->get();
foreach($polizas as $poliza)
{
    Auxiliares::where('cat_polizas_id',$poliza->id)->update([
        'a_ejercicio'=>$poliza->ejercicio,
        'a_periodo'=>$poliza->periodo,
    ]);
}
SaldosReportes::where('team_id',$team_id)->delete();
$cuentas = DB::select("SELECT codigo, nombre as cuenta, acumula, naturaleza, team_id
        FROM cat_cuentas WHERE team_id = $team_id
        AND substr(codigo,1,5)
        NOT IN('10000','20000','30000','40000','50000','60000','70000','80000','90000')");
foreach ($cuentas as $cuenta) {
    $nivel = 1;
    $n1 = substr($cuenta->codigo,0,3);
    $n2 = substr($cuenta->codigo,3,2);
    $n3 = substr($cuenta->codigo,5,3);
    $n2 = intval($n2);
    $n3 = intval($n3);
    if($n2 > 0) $nivel++;
    if($n3 > 0) $nivel++;
    $montos = Auxiliares::where('codigo',$cuenta->codigo)->where('a_periodo',$periodo)
        ->where('a_ejercicio',$ejercicio)
        ->where('team_id',$team_id)
        ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos' ))->first();
    $montos_ant = Auxiliares::where('codigo',$cuenta->codigo)->where('a_periodo','<',$periodo)
        ->where('team_id',$team_id)
        ->select(DB::raw('COALESCE(SUM(cargo),0) as cargos, COALESCE(SUM(abono),0) as abonos' ))->first();
    $inicial = 0;
    $final = 0;
    if($cuenta->naturaleza == 'D')
    {
        $inicial = $montos_ant->cargos - $montos_ant->abonos;
        $final = $montos->cargos - $montos->abonos;
    }
    else{
        $inicial = $montos_ant->abonos - $montos_ant->cargos;
        $final = $montos->abonos - $montos->cargos;
    }
    SaldosReportes::insert([
        'codigo' => $cuenta->codigo,
        'cuenta' => $cuenta->cuenta,
        'acumula' => $cuenta->acumula ?? 0,
        'naturaleza' => $cuenta->naturaleza,
        'anterior' => $inicial,
        'cargos' => $montos->cargos,
        'abonos' => $montos->abonos,
        'final' => $final,
        'nivel'=> $nivel,
        'team_id' => $team_id
    ]);
}
$nivel_3 = DB::select("SELECT acumula,COALESCE(SUM(anterior),0) as anterior, COALESCE(SUM(cargos),0) as s_cargos, COALESCE(SUM(abonos),0) as s_abonos
        FROM saldos_reportes WHERE nivel = 3 AND team_id = $team_id GROUP BY acumula");
foreach ($nivel_3 as $n_3) {
    SaldosReportes::where('codigo',$n_3->acumula)->update([
        'cargos'=>$n_3->s_cargos,
        'abonos'=>$n_3->s_abonos,
        'anterior'=>$n_3->anterior
    ]);
}
$nivel_2 = DB::select("SELECT acumula,COALESCE(SUM(anterior),0) as anterior, COALESCE(SUM(cargos),0) as s_cargos, COALESCE(SUM(abonos),0) as s_abonos
        FROM saldos_reportes WHERE nivel = 2 AND team_id = $team_id GROUP BY acumula");
foreach ($nivel_2 as $n_2) {
    SaldosReportes::where('codigo',$n_2->acumula)->update([
        'cargos'=>$n_2->s_cargos,
        'abonos'=>$n_2->s_abonos,
        'anterior'=>$n_2->anterior
    ]);
}
DB::statement("UPDATE saldos_reportes SET final = (anterior + cargos - abonos) WHERE naturaleza = 'D' AND team_id = $team_id");
DB::statement("UPDATE saldos_reportes SET final = (anterior + abonos - cargos) WHERE naturaleza = 'A' AND team_id = $team_id");
return 1;
