<?php

namespace App\Http\Controllers;


use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatPolizas;
use App\Models\CuentasCobrarTable;
use App\Models\CuentasPagarTable;
use App\Models\Movbancos;
use App\Models\Saldosbanco;
use App\Models\SaldosReportes;
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
        $catac = DB::select("SELECT distinct codigo,nombre,CONCAT('$',FORMAT($campo1,2,'en_US')) inicial,CONCAT('$',FORMAT($campo2,2,'en_US')) cargos,CONCAT('$',FORMAT($campo3,2,'en_US')) abonos,CONCAT('$',FORMAT($campo4,2,'en_US')) final
        FROM saldoscuentas WHERE team_id = $tax_id ORDER BY codigo");
        $totales = DB::select("SELECT
        CONCAT('$',FORMAT(SUM(IF(naturaleza = 'D',$campo1,($campo1*-1))),2,'en_US')) inicial,
        CONCAT('$',FORMAT(SUM($campo2),2,'en_US')) cargos,
        CONCAT('$',FORMAT(SUM($campo3),2,'en_US')) abonos,
        CONCAT('$',FORMAT(SUM(IF(naturaleza = 'D',$campo4,($campo4*-1))),2,'en_US')) final
        FROM saldoscuentas
        WHERE team_id = $tax_id AND codigo IN ('10001000','10002000','20001000','30000000','40000000','50000000','60000000','70000000')");
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
        /*$saldores = DB::select("SELECT CONCAT('$',FORMAT($resultado,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $saldopre = DB::select("SELECT CONCAT('$',FORMAT($pasmasres,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $utilidadbruta = DB::select("SELECT CONCAT('$',FORMAT($utibruta,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");
        $utilidadgasto = DB::select("SELECT CONCAT('$',FORMAT($utigast,2,'en_US')) saldo FROM saldoscuentas WHERE team_id = $tax_id LIMIT 1");*/
        $saldores = '$'.number_format($resultado,2,'.',',');
        $saldopre = '$'.number_format($pasmasres,2,'.',',');
        $utilidadbruta = '$'.number_format($utibruta,2,'.',',');
        $utilidadgasto = '$'.number_format($utigast,2,'.',',');
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
        'datos6final'=>$saldopre,
        'datos7final'=>$saldores,
        'tax_id'=>Filament::getTenant()->taxid,
        'fecha'=>$currentDate,
        'ejercicio'=>$ejercicio,
        'periodo'=>$periodo,
        'utilidadbruta'=>$utilidadbruta,
        'utilidadgral'=>$utilidadgasto,
        'resultado'=>$saldores,
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
    public function actualiza_saldos($periodo,$ejercicio)
    {
        $empresa = Filament::getTenant()->id;
        DB::statement("DELETE FROM auxiliares WHERE id > 0 AND team_id = $empresa AND cat_polizas_id NOT IN (SELECT id FROM cat_polizas WHERE team_id = $empresa)");
        DB::statement("DELETE FROM saldoscuentas WHERE id > 0 AND team_id = $empresa ");
        $campo1 = 'c'.$periodo;
        $campo2 = 'a'.$periodo;
        $campo3 = 's'.$periodo;
        DB::statement("INSERT INTO saldoscuentas (codigo,nombre,n1,n2,n3,si,c1,c2,c3,c4,c5,c6,c7,c8,c9,c10,c11,c12,
        a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,a11,a12,s1,s2,s3,s4,s5,s6,s7,s8,s9,s10,s11,s12,naturaleza,ejercicio,team_id)
        SELECT distinct c.codigo,c.nombre,COALESCE(c.acumula,-1) n1, COALESCE(u.acumula,-1) n2,
        COALESCE(m.acumula,-1) n3,0 si,
        0 c1,0 c2,0 c3,0 c4,0 c5,0 c6,0 c7,0 c8,0 c9,0 c10,0 c11,0 c12,
        0 a1,0 a2,0 a3,0 a4,0 a5,0 a6,0 a7,0 a8,0 a9,0 a10,0 a11,0 a12,
        0 s1,0 s2,0 s3,0 s4,0 s5,0 s6,0 s7,0 s8,0 s9,0 s10,0 s11,0 s12,
        c.naturaleza,$ejercicio, $empresa
        FROM cat_cuentas c
        LEFT JOIN cat_cuentas u ON u.codigo = c.acumula AND u.team_id = $empresa
        LEFT JOIN cat_cuentas m ON m.codigo = u.acumula AND m.team_id = $empresa
        ORDER BY c.codigo");

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
    public function ContabilizaReporte_ret(Request $request)
    {
        $team_id = $request->team_id;
        $periodo = $request->periodo;
        $ejercicio = $request->ejercicio;
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
        self::genera_cuentas_cobrar($team_id);
        self::genera_cuentas_pagar($team_id);
        return 1;
    }

    public function genera_cuentas_cobrar($team_id){
        DB::statement("DELETE FROM cuentas_cobrar_tables WHERE team_id = $team_id AND id > 0");
        $cfdis = Almacencfdis::where('team_id',$team_id)
            ->where('TipoDeComprobante','I')
            ->where('xml_type','Emitidos')
            ->get();
        foreach ($cfdis as $cfdi) {
            $auxi = Auxiliares::where('team_id',$team_id)
                ->where('codigo','like','105%')
                ->where('cargo','>',0)
                ->where('uuid',$cfdi->UUID)
                ->get();
            foreach ($auxi as $aux) {
                $fech = substr($cfdi->Fecha,0,10);
                $fecha = Carbon::createFromFormat('Y-m-d',$fech);
                CuentasCobrarTable::create([
                    'cliente'=>$cfdi->Receptor_Nombre,
                    'documento'=>$aux->factura,
                    'uuid'=>$cfdi->UUID,
                    'concepto'=>'Factura',
                    'fecha'=>$fecha,
                    'vencimiento'=>$fecha->addDays(30),
                    'importe'=>$aux->cargo,
                    'saldo'=>$aux->cargo,
                    'tipo'=>'C',
                    'periodo'=>$aux->a_periodo,
                    'ejercicio'=>$aux->a_ejercicio,
                    'team_id'=>$team_id
                ]);
            }
            $auxi2 = Auxiliares::where('team_id',$team_id)
                ->where('codigo','like','105%')
                ->where('abono','>',0)
                ->where('factura',$cfdi->Serie.$cfdi->Folio)
                ->get();
            foreach ($auxi2 as $aux) {
                $fech = substr($cfdi->Fecha,0,10);
                $fecha = Carbon::createFromFormat('Y-m-d',$fech);
                CuentasCobrarTable::create([
                    'cliente'=>$cfdi->Receptor_Nombre,
                    'documento'=>$aux->factura,
                    'uuid'=>$cfdi->UUID,
                    'concepto'=>'Pago',
                    'fecha'=>$fecha,
                    'vencimiento'=>$fecha,
                    'importe'=>$aux->abono,
                    'saldo'=>$aux->abono,
                    'tipo'=>'A',
                    'periodo'=>$aux->a_periodo,
                    'ejercicio'=>$aux->a_ejercicio,
                    'team_id'=>$team_id
                ]);
                CuentasCobrarTable::where('documento',$aux->factura)
                    ->where('tipo','C')
                    ->decrement('saldo',$aux->abono);
            }
        }
    }

    public function genera_cuentas_pagar($team_id){
        DB::statement("DELETE FROM cuentas_pagar_tables WHERE team_id = $team_id AND id > 0");
        $cfdis = Almacencfdis::where('team_id',$team_id)
            ->where('TipoDeComprobante','I')
            ->where('xml_type','Recibidos')
            ->get();
        foreach ($cfdis as $cfdi) {
            $auxi = Auxiliares::where('team_id',$team_id)
                ->where('codigo','like','201%')
                ->where('abono','>',0)
                ->where('uuid',$cfdi->UUID)
                ->get();
            foreach ($auxi as $aux) {
                $fech = substr($cfdi->Fecha,0,10);
                $fecha = Carbon::createFromFormat('Y-m-d',$fech);
                CuentasPagarTable::create([
                    'cliente'=>$cfdi->Emisor_Nombre,
                    'documento'=>$aux->factura,
                    'uuid'=>$cfdi->UUID,
                    'concepto'=>'Factura',
                    'fecha'=>$fecha,
                    'vencimiento'=>$fecha->addDays(30),
                    'importe'=>$aux->abono,
                    'saldo'=>$aux->abono,
                    'tipo'=>'C',
                    'periodo'=>$aux->a_periodo,
                    'ejercicio'=>$aux->a_ejercicio,
                    'team_id'=>$team_id
                ]);
            }
            $auxi2 = Auxiliares::where('team_id',$team_id)
                ->where('codigo','like','201%')
                ->where('cargo','>',0)
                ->where('factura',$cfdi->Serie.$cfdi->Folio)
                ->get();
            foreach ($auxi2 as $aux) {
                $fech = substr($cfdi->Fecha,0,10);
                $fecha = Carbon::createFromFormat('Y-m-d',$fech);
                CuentasPagarTable::create([
                    'cliente'=>$cfdi->Emisor_Nombre,
                    'documento'=>$aux->factura,
                    'uuid'=>$cfdi->UUID,
                    'concepto'=>'Pago',
                    'fecha'=>$fecha,
                    'vencimiento'=>$fecha,
                    'importe'=>$aux->cargo,
                    'saldo'=>$aux->cargo,
                    'tipo'=>'A',
                    'periodo'=>$aux->a_periodo,
                    'ejercicio'=>$aux->a_ejercicio,
                    'team_id'=>$team_id
                ]);
                CuentasPagarTable::where('documento',$aux->factura)
                    ->where('tipo','C')
                    ->decrement('saldo',$aux->cargo);
            }
        }
    }
    public function ContabilizaReporte($ejercicio,$periodo,$team_id)
    {
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
    }

    public function SaldosBancos($team_id)
    {
        $cuen_banco = BancoCuentas::where('team_id',$team_id)->get();
        foreach ($cuen_banco as $banco) {
            Saldosbanco::where('cuenta',$banco->id  )->update( [
                'inicial' => 0,
                'ingresos' => 0,
                'egresos' => 0,
                'final' => 0
            ]);
        }
        $MovsBancos = Movbancos::where('team_id',$team_id)->get();
        foreach ($MovsBancos as $Mov) {
            $tipo = $Mov->tipo;
            if($tipo == 'E'){
                Saldosbanco::where('cuenta',$Mov->cuenta)
                    ->where('periodo',$Mov->periodo)
                    ->where('ejercicio',$Mov->ejercicio)
                    ->increment('ingresos',$Mov->importe);
            }else{
                Saldosbanco::where('cuenta',$Mov->cuenta)
                    ->where('periodo',$Mov->periodo)
                    ->where('ejercicio',$Mov->ejercicio)
                    ->increment('egresos',$Mov->importe);
            }
        }
    }
}
