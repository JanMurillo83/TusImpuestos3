<?php

namespace App\Http\Controllers;

use App\Models\Auxiliares;
use App\Models\CatPolizas;
use App\Models\Saldoscuentas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NuevoReportes extends Controller
{
    public function contabilizar($empresa,$periodo,$ejercicio)
    {
        DB::statement("DELETE FROM saldoscuentas WHERE id > 0 AND team_id = $empresa");
        DB::statement("INSERT INTO saldoscuentas (codigo,nombre,n1,n2,n3,si,c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11,c12,
        a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,a11,a12,s1,s2,s3,s4,s5,s6,s7,s8,s9,s10,s11,s12,naturaleza,ejercicio,team_id)
        SELECT distinct c.codigo,c.nombre,COALESCE(c.acumula,-1) n1, COALESCE(u.acumula,-1) n2,
        COALESCE(m.acumula,-1) n3,0 si,
        0 c1,0 c2,0 c3,0 c4,0 c5,0 c6,0 c7,0 c8,0 c9,0 c10,0 c11,0 c12,
        0 a1,0 a2,0 a3,0 a4,0 a5,0 a6,0 a7,0 a8,0 a9,0 a10,0 a11,0 a12,
        0 s1,0 s2,0 s3,0 s4,0 s5,0 s6,0 s7,0 s8,0 s9,0 s10,0 s11,0 s12,
        c.naturaleza, $ejercicio, $empresa
        FROM cat_cuentas c
        LEFT JOIN cat_cuentas u ON u.codigo = c.acumula AND u.team_id = $empresa
        LEFT JOIN cat_cuentas m ON m.codigo = u.acumula AND m.team_id = $empresa
        ORDER BY c.codigo");
        $vals = "";
        for($i=1;$i<13;$i++)
        {
            $ids = CatPolizas::select('id')->where('ejercicio',$ejercicio)
            ->where('team_id',$empresa)->where('periodo',$i)->get();
            $cnt = count($ids);
            if($cnt > 0)
            {
                $auxiliares = Auxiliares::select('codigo',DB::raw("SUM(cargo) cargos,SUM(abono) abonos"))
                ->whereIn('cat_polizas_id',$ids)->groupBy('codigo')->get();
                foreach($auxiliares as $aux)
                {
                    $periodoC = 'c'.$i;
                    $periodoA = 'a'.$i;
                    $query = "UPDATE saldoscuentas SET $periodoC = $aux->cargos,
                    $periodoA = $aux->abonos WHERE codigo = '$aux->codigo' AND team_id =
                    $empresa AND ejercicio = $ejercicio";
                    $Resulta = DB::statement($query);
                    //-------------------------------------------------------------------
                    $enes3 = DB::table('saldoscuentas')->select('n3',DB::raw("SUM($periodoC) cargos,SUM($periodoA) abonos"))
                    ->whereNotIn('n3',[0,'',-1])->groupBy('n3')->get();
                    foreach($enes3 as $ene3)
                    {
                        $query4 = "UPDATE saldoscuentas SET $periodoC = $ene3->cargos,
                        $periodoA = $ene3->abonos WHERE codigo = '$ene3->n3' AND team_id =
                        $empresa AND ejercicio = $ejercicio";
                        DB::statement($query4);
                    }

                    $enes2 = DB::table('saldoscuentas')->select('n2',DB::raw("SUM($periodoC) cargos,SUM($periodoA) abonos"))
                    ->whereNotIn('n2',[0,'',-1])->groupBy('n2')->get();
                    foreach($enes2 as $ene2)
                    {
                        $query3 = "UPDATE saldoscuentas SET $periodoC = $ene2->cargos,
                        $periodoA = $ene2->abonos WHERE codigo = '$ene2->n2' AND team_id =
                        $empresa AND ejercicio = $ejercicio";
                        DB::statement($query3);
                    }

                    $enes1 = DB::table('saldoscuentas')->select('n1',DB::raw("SUM($periodoC) cargos,SUM($periodoA) abonos"))
                    ->whereNotIn('n1',[0,'',-1])->groupBy('n1')->get();
                    foreach($enes1 as $ene1)
                    {
                        $query2 = "UPDATE saldoscuentas SET $periodoC = $ene1->cargos,
                        $periodoA = $ene1->abonos WHERE codigo = '$ene1->n1' AND team_id =
                        $empresa AND ejercicio = $ejercicio";
                        DB::statement($query2);
                    }

                    $vals = "Exito";
                }
            }
        }

        // FASE 1: Invalidar caché después de contabilizar
        \App\Services\SaldosCache::invalidate($empresa);

        return $vals;
    }
    public function emite($reporte,$empresa,$periodo,$ejercicio)
    {
        switch($reporte)
        {
            case 'Balanza':
                return view('ContaRep/Balanza',['empresa'=>$empresa,'periodo'=>$periodo,'ejercicio'=>$ejercicio]);
                break;
        }
    }
}
