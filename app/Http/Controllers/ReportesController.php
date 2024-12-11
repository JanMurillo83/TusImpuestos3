<?php

namespace App\Http\Controllers;


use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    public function balanza(Request $request)
    {

        $timestamp = time();
        $currentDate = date('d-m-Y', $timestamp);
        $tax_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $periodoant = intval($request->month) - 1;
        $ejercicio = $request->year;
        $this->actualiza_saldos($tax_id,$periodo,$ejercicio);
        $campo1 = 's'.$periodoant;
        if($campo1 == 's0') $campo1 = 'si';
        $campo2 = 'c'.$periodo;
        $campo3 = 'a'.$periodo;
        $campo4 = 's'.$periodo;
        $catac = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo1,2,'en_US')) inicial,CONCAT('$',FORMAT($campo2,2,'en_US')) cargos,CONCAT('$',FORMAT($campo3,2,'en_US')) abonos,CONCAT('$',FORMAT($campo4,2,'en_US')) final FROM saldoscuentas ORDER BY codigo");
        $totales = DB::select("SELECT
        CONCAT('$',FORMAT(SUM(IF(naturaleza = 'D',$campo1,($campo1*-1))),2,'en_US')) inicial,
        CONCAT('$',FORMAT(SUM($campo2),2,'en_US')) cargos,
        CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) abonos,
        CONCAT('$',FORMAT(SUM(IF(naturaleza = 'D',$campo4,($campo4*-1))),2,'en_US')) final
        FROM saldoscuentas
        WHERE codigo IN ('10001000','10002000','20001000','30000000','40000000','50000000','60000000','70000000')");
        $data = [
            'titulo' => 'Balanza de Comprobacion',
            'datos'=>$catac,
            'tax_id'=>Filament::getTenant()->taxid,
            'fecha'=>$currentDate,
            'ejercicio'=>$ejercicio,
            'periodo'=>$periodo,
            'totinicial'=>$totales[0]->inicial,
            'totcargos'=>$totales[0]->cargos,
            'totabonos'=>$totales[0]->abonos,
            'totfinal'=>$totales[0]->final,
            ];

            $pdf = SnappyPdf::loadView('/Reportes/balanza',$data)->setOption("footer-right", "Pagina [page] de [topage]");
            $nombre = public_path('Reportes/balanza_'.Filament::getTenant()->id.'_'.$periodo.'_'.$ejercicio.'.pdf');

            if(file_exists($nombre)) unlink($nombre);
            $pdf->setOption('encoding', 'utf-8');
            if(!$pdf->save($nombre)) return 'Error';
            $archivo = DB::table('archivos_pdfs')->insertGetId([
                'archivo'=>$nombre,'empresa'=>$tax_id,'fecha'=>Carbon::now()
            ]);
            $nombreruta = env('APP_URL').'/Reportes/balanza_'.Filament::getTenant()->id.'_'.$periodo.'_'.$ejercicio.'.pdf';
            return $nombreruta;
    }
    public function balancegral(Request $request)
    {
        //$this->actualiza_saldos();
        $timestamp = time();
        $currentDate = date('d-m-Y', $timestamp);
        $tax_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;
        $this->actualiza_saldos($tax_id,$periodo,$ejercicio);
        $campo1 = 'c'.$periodo;
        $campo2 = 'a'.$periodo;
        $campo3 = 's'.$periodo;
        $catac = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '10001000' AND team_id = $tax_id ORDER BY codigo");
        $cataf = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '10002000' AND team_id = $tax_id ORDER BY codigo");
        $catpa = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '20001000' AND team_id = $tax_id ORDER BY codigo");
        $catca = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '30000000' AND team_id = $tax_id ORDER BY codigo");
        //-------------------------------------
        $catacs = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '10001000' AND team_id = $tax_id");
        $catafs = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '10002000' AND team_id = $tax_id");
        $catpas = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '20001000' AND team_id = $tax_id");
        $catcas = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '30000000' AND team_id = $tax_id");
        $catsumac = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 in ('10001000','10002000') AND team_id = $tax_id");
        $catsumpc = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo, SUM($campo3) saldo2 FROM saldoscuentas WHERE n1 in ('20001000','30000000') AND team_id = $tax_id");
        //--------------------------------------------------------------
        $ventas = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '40000000' AND team_id = $tax_id");
        $costos = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '50000000' AND team_id = $tax_id");
        $gastos = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '60000000' AND team_id = $tax_id");
        $rotros = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '70000000' AND team_id = $tax_id");
        $pasres = floatval($catsumpc[0]->saldo2);
        $saldov = floatval($ventas[0]->saldo);
        $saldoc = floatval($costos[0]->saldo);
        $saldog = floatval($gastos[0]->saldo);
        $saldor = floatval($rotros[0]->saldo);
        $resultado = $saldov - $saldoc -$saldog -$saldor;
        $pasmasres = $pasres + $resultado;
        $saldores = DB::select("SELECT CONCAT('$',FORMAT($resultado,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $saldopre = DB::select("SELECT CONCAT('$',FORMAT($pasmasres,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $data = [
        'titulo' => 'Balance General',
        'datos'=>$catac,
        'datosfinal'=>$catacs[0]->saldo,
        'datos2'=>$cataf,
        'datos2final'=>$catafs[0]->saldo,
        'datos3'=>$catpa,
        'datos3final'=>$catpas[0]->saldo,
        'datos4'=>$catca,
        'datos4final'=>$catcas[0]->saldo,
        'datos5final'=>$catsumac[0]->saldo,
        'datos6final'=>$saldopre[0]->saldo,
        'datos7final'=>$saldores[0]->saldo,
        'tax_id'=>Filament::getTenant()->taxid,
        'fecha'=>$currentDate,
        'ejercicio'=>$ejercicio,
        'periodo'=>$periodo,
        ];
        $pdf = SnappyPdf::loadView('Reportes/balancegral',$data);
        $nombre = public_path('Reportes/balance_'.Filament::getTenant()->id.'_'.$periodo.'_'.$ejercicio.'.pdf');

            if(file_exists($nombre)) unlink($nombre);
            $pdf->setOption('encoding', 'utf-8');
            if(!$pdf->save($nombre)) return 'Error';
            $archivo = DB::table('archivos_pdfs')->insertGetId([
                'archivo'=>$nombre,'empresa'=>$tax_id,'fecha'=>Carbon::now()
            ]);
            $nombreruta = env('APP_URL').'/Reportes/balance_'.Filament::getTenant()->id.'_'.$periodo.'_'.$ejercicio.'.pdf';
            return $nombreruta;
    }
    public function edores(Request $request)
    {
        //$this->actualiza_saldos();
        $timestamp = time();
        $currentDate = date('d-m-Y', $timestamp);
        $tax_id = Filament::getTenant()->id;
        $periodo = $request->month;
        $ejercicio = $request->year;
        $campo1 = 'c'.$periodo;
        $campo2 = 'a'.$periodo;
        $campo3 = 's'.$periodo;
        $catac = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '40000000' AND team_id = $tax_id");
        $cataf = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '50000000' AND team_id = $tax_id");
        $catpa = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '60000000' AND team_id = $tax_id");
        $catca = DB::select("SELECT codigo,nombre,CONCAT('$',FORMAT($campo3,2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '70000000' AND team_id = $tax_id");
        //-------------------------------------
        $catacs = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '40000000' AND team_id = $tax_id");
        $catafs = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '50000000' AND team_id = $tax_id");
        $catpas = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '60000000' AND team_id = $tax_id");
        $catcas = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 = '70000000' AND team_id = $tax_id");
        $catsumac = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo FROM saldoscuentas WHERE n1 in ('10001000','10002000') AND team_id = $tax_id");
        $catsumpc = DB::select("SELECT CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) saldo, SUM($campo3) saldo2 FROM saldoscuentas WHERE n1 in ('20001000','30000000') AND team_id = $tax_id");
        //--------------------------------------------------------------
        $ventas = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '40000000' AND team_id = $tax_id");
        $costos = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '50000000' AND team_id = $tax_id");
        $gastos = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '60000000' AND team_id = $tax_id");
        $rotros = DB::select("SELECT SUM($campo3) saldo FROM saldoscuentas WHERE n1 = '70000000' AND team_id = $tax_id");
        $pasres = floatval($catsumpc[0]->saldo2);
        $saldov = floatval($ventas[0]->saldo);
        $saldoc = floatval($costos[0]->saldo);
        $saldog = floatval($gastos[0]->saldo);
        $saldor = floatval($rotros[0]->saldo);
        $utibruta = $saldov - $saldoc;
        $utigast = $saldov - $saldoc -$saldog;
        $resultado = $saldov - $saldoc -$saldog -$saldor;
        $pasmasres = $pasres + $resultado;
        $saldores = DB::select("SELECT CONCAT('$',FORMAT($resultado,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $saldopre = DB::select("SELECT CONCAT('$',FORMAT($pasmasres,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $utilidadbruta = DB::select("SELECT CONCAT('$',FORMAT($utibruta,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $utilidadgasto = DB::select("SELECT CONCAT('$',FORMAT($utigast,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $data = [
        'titulo' => 'Estado de Resultados',
        'datos'=>$catac,
        'datosfinal'=>$catacs[0]->saldo,
        'datos2'=>$cataf,
        'datos2final'=>$catafs[0]->saldo,
        'datos3'=>$catpa,
        'datos3final'=>$catpas[0]->saldo,
        'datos4'=>$catca,
        'datos4final'=>$catcas[0]->saldo,
        'datos5final'=>$catsumac[0]->saldo,
        'datos6final'=>$saldopre[0]->saldo,
        'datos7final'=>$saldores[0]->saldo,
        'tax_id'=>Filament::getTenant()->taxid,
        'fecha'=>$currentDate,
        'ejercicio'=>$ejercicio,
        'periodo'=>$periodo,
        'utilidadbruta'=>$utilidadbruta[0]->saldo,
        'utilidadgral'=>$utilidadgasto[0]->saldo,
        'resultado'=>$saldores[0]->saldo,
        ];
        $pdf = SnappyPdf::loadView('Reportes/edores',$data);
        $nombre = public_path('Reportes/edore_'.Filament::getTenant()->id.'_'.$periodo.'_'.$ejercicio.'.pdf');

            if(file_exists($nombre)) unlink($nombre);
            $pdf->setOption('encoding', 'utf-8');
            if(!$pdf->save($nombre)) return 'Error';
            $archivo = DB::table('archivos_pdfs')->insertGetId([
                'archivo'=>$nombre,'empresa'=>$tax_id,'fecha'=>Carbon::now()
            ]);
            $nombreruta = env('APP_URL').'/Reportes/edore_'.Filament::getTenant()->id.'_'.$periodo.'_'.$ejercicio.'.pdf';
            return $nombreruta;
    }

    public function data($tax_id)
    {
    $catcuentas = DB::select("SELECT * FROM `cat_cuentas`");
    return response()->json($catcuentas);
    }

    public function periodo(Request $request)
    {
        $request->session()->forget('UserName');
        $request->session()->put('UserName',$request->nombre);
        $request->session()->forget('UserRFC');
        $request->session()->put('UserRFC',$request->tax_id);
        $request->session()->forget('UserMonth');
        $request->session()->put('UserMonth',$request->periodo);
        $request->session()->forget('UserYear');
        $request->session()->put('UserYear',$request->ejercicio);
        return "Parametros Establecidos";
    }
    public function actualiza_saldos($tax_id,$periodo,$ejercicio)
    {
        $empresa = Filament::getTenant()->id;
        DB::statement("DELETE FROM saldoscuentas WHERE id > 0 AND team_id = $empresa");
        $campo1 = 'c'.$periodo;
        $campo2 = 'a'.$periodo;
        $campo3 = 's'.$periodo;
        DB::statement("INSERT INTO saldoscuentas (codigo,nombre,n1,n2,n3,n4,n5,n6,si,c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11,c12,
        a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,a11,a12,s1,s2,s3,s4,s5,s6,s7,s8,s9,s10,s11,s12,naturaleza,team_id)
        SELECT c.codigo,c.nombre,COALESCE(c.acumula,-1) n1, COALESCE(u.acumula,-1) n2,
        COALESCE(m.acumula,-1) n3, COALESCE(l.acumula,-1) n4,
        COALESCE(l1.acumula,-1) n5, COALESCE(l2.acumula,-1) n6,0 si,
        0 c1,0 c2,0 c3,0 c4,0 c5,0 c6,0 c7,0 c8,0 c9,0 c10,0 c11,0 c12,
        0 a1,0 a2,0 a3,0 a4,0 a5,0 a6,0 a7,0 a8,0 a9,0 a10,0 a11,0 a12,
        0 s1,0 s2,0 s3,0 s4,0 s5,0 s6,0 s7,0 s8,0 s9,0 s10,0 s11,0 s12,
        c.naturaleza, $empresa
        FROM cat_cuentas c
        LEFT JOIN cat_cuentas u ON u.codigo = c.acumula AND u.team_id = $empresa
        LEFT JOIN cat_cuentas m ON m.codigo = u.acumula AND m.team_id = $empresa
        LEFT JOIN cat_cuentas l ON l.codigo = m.acumula AND l.team_id = $empresa
        LEFT JOIN cat_cuentas l1 ON l1.codigo = l.acumula AND l1.team_id = $empresa
        LEFT JOIN cat_cuentas l2 ON l2.codigo = l1.acumula AND l2.team_id = $empresa");

        $saldos_mes = DB::select("SELECT a.codigo,c.nombre, sum(cargo) cargos, sum(abono) abonos,
        IF(c.naturaleza = 'D',sum(cargo)-sum(abono),sum(abono)-sum(cargo)) final,
        c.acumula n1, u.acumula n2,m.acumula n3,COALESCE(l.acumula,-1) n4,COALESCE(l1.acumula,-1) n5,
        COALESCE(l2.acumula,-1) n6 FROM auxiliares a
        INNER JOIN cat_cuentas c ON c.codigo = a.codigo
        INNER JOIN cat_polizas p ON p.id = a.cat_polizas_id
        INNER JOIN cat_cuentas u ON u.codigo = c.acumula
        INNER JOIN cat_cuentas m ON m.codigo = u.acumula
        LEFT JOIN cat_cuentas l ON l.codigo = m.acumula
        LEFT JOIN cat_cuentas l1 ON l1.codigo = l.acumula
        LEFT JOIN cat_cuentas l2 ON l2.codigo = l1.acumula
        WHERE p.periodo = $periodo AND p.ejercicio = $ejercicio AND a.team_id = $empresa
        group by codigo, c.nombre,c.naturaleza,c.acumula,u.acumula,m.acumula,
        l.acumula,l1.acumula,l2.acumula");
        foreach($saldos_mes as $saldo)
        {
            DB::statement("UPDATE saldoscuentas SET $campo1 = $saldo->cargos, $campo2 = $saldo->abonos,
            $campo3 = $saldo->final WHERE codigo = '$saldo->codigo' AND team_id = $empresa");
            DB::statement("UPDATE saldoscuentas SET $campo1 = $campo1 +$saldo->cargos,
            $campo2 = $campo2 + $saldo->abonos, $campo3 = $campo3 + $saldo->final
            WHERE codigo = '$saldo->n1' AND team_id = $empresa");
            DB::statement("UPDATE saldoscuentas SET $campo1 = $campo1 +$saldo->cargos,
            $campo2 = $campo2 + $saldo->abonos, $campo3 = $campo3 + $saldo->final
            WHERE codigo = '$saldo->n2' AND team_id = $empresa");
            DB::statement("UPDATE saldoscuentas SET $campo1 = $campo1 +$saldo->cargos,
            $campo2 = $campo2 + $saldo->abonos, $campo3 = $campo3 + $saldo->final
            WHERE codigo = '$saldo->n3' AND team_id = $empresa");
            DB::statement("UPDATE saldoscuentas SET $campo1 = $campo1 +$saldo->cargos,
            $campo2 = $campo2 + $saldo->abonos, $campo3 = $campo3 + $saldo->final
            WHERE codigo = '$saldo->n4' AND team_id = $empresa");
            DB::statement("UPDATE saldoscuentas SET $campo1 = $campo1 +$saldo->cargos,
            $campo2 = $campo2 + $saldo->abonos, $campo3 = $campo3 + $saldo->final
            WHERE codigo = '$saldo->n5' AND team_id = $empresa");
            DB::statement("UPDATE saldoscuentas SET $campo1 = $campo1 +$saldo->cargos,
            $campo2 = $campo2 + $saldo->abonos, $campo3 = $campo3 + $saldo->final
            WHERE codigo = '$saldo->n6' AND team_id = $empresa");
        }
    }
}
