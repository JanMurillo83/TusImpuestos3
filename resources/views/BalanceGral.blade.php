<?php
use Carbon\Carbon;
use \Illuminate\Support\Facades\DB;
//$empresa = $empresa;
//$ejercicio = $_GET['ejercicio'];
//$periodo = $_GET['periodo'];


$empresas = DB::table('teams')->where('id',$empresa)->get()[0];
if($periodo == 1) $ini = 'si'; else $ini = 'si';
$ca = 'c'.$periodo;
$ab = 'a'.$periodo;
$activoc = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
    ->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n3',0)->whereBetween('codigo',[10000000,14999999])->get();
$activof = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
    ->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n3',0)->whereBetween('codigo',[15000000,19999999])->get();
$pasivoc = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
    ->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n3',0)->whereBetween('codigo',[20000000,21999999])->get();
$pasivof = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
    ->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n3',0)->whereBetween('codigo',[22000000,29999999])->get();
$capital = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
    ->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n3',0)->whereBetween('codigo',[30000000,39999999])->get();
$resultadoin = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
    ->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n3',0)->whereBetween('codigo',[40000000,49999999])->get();
$resultadoeg = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
    ->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n3',0)->whereBetween('codigo',[50000000,99999999])->get();
$fecha = Carbon::now();
$gsfac = 0;
$gsfaf = 0;
$gsfpc = 0;
$gsfpf = 0;
$gsfca = 0;
$gsfre = 0;
?>
    <!DOCTYPE html>
<html>
<head>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        .pagebreak { page-break-before: always; }
        @page { size: landscape; }
        @media print{
            html, body {
                -webkit-print-color-adjust: exact;
                margin-left: 20px;
                margin-right: 20px;
            }
        }
        html, body {
            margin-left: 20px;
            margin-right: 20px;
        }

    </style>
    <script type="text/javascript">
        $( document ).ready(async function() {
            await sleep(500);
            //window.print();
        });

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    </script>
</head>
<body>
<div class="mt-5 ms-10">
    <div class="row">
        <div class="col-3">
            <img src="{{asset('images/MainLogo.png')}}" alt="Tus-Impuestos" width="120px">
        </div>
        <div class="col-6">
            <center>
                <h5>{{$empresas->name}}</h5>
                <div>
                    Posicion financiera, Balance General Periodo {{$periodo}}
                </div>
            </center>
        </div>
        <div class="col-3">
            Fecha de Emision: <?php echo $fecha->toDateString('d-m-Y'); ?>
        </div>
    </div>
    <hr>
    <div class="mt-2 row">
        <div class="col-6">
            <center><h5><b>Activo</b></h5></center>
            <div>
                <h6>Activo a Corto Plazo</h6>
                <table class="table table-sm">
                    @foreach ($activoc as $actc )
                            <?php
                            $sf = 0;
                            if($actc->naturaleza == 'D') {
                                $sf = $actc->si + $actc->cargos - $actc->abonos;
                            }
                            else {
                                $sf = $actc->si - $actc->cargos + $actc->abonos;
                            }
                            $gsfac+=$sf;
                            ?>
                        <tr>
                            <td>{{$actc->nombre}}</td>
                            <td class="text-end">{{number_format($sf,2)}}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><b>Total de Activo Circulante</b></td>
                        <td class="text-end"><b>{{number_format($gsfac,2)}}</b></td>
                    </tr>
                </table>
            </div>
            <div>
                <h6>Activo a Largo Plazo</h6>
                <table class="table table-sm">
                    @foreach ($activof as $actc )
                            <?php
                            $sf = 0;
                            if($actc->naturaleza == 'D') {
                                $sf = $actc->si + $actc->cargos - $actc->abonos;
                            }
                            else {
                                $sf = $actc->si - $actc->cargos + $actc->abonos;
                            }
                            $gsfaf+=$sf;
                            ?>
                        <tr>
                            <td>{{$actc->nombre}}</td>
                            <td class="text-end">{{number_format($sf,2)}}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><b>Total de Activo Fijo</b></td>
                        <td class="text-end"><b>{{number_format($gsfaf,2)}}</b></td>
                    </tr>
                </table>
            </div>
            <div class="mt-5">
                <table class="table table-sm">
                    <tr>
                        <td><b>SUMA DEL ACTIVO</b></td>
                        <td class="text-end"><b>{{number_format($gsfac+$gsfaf,2)}}</b></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="col-6">
            <center><h6><b>Pasivo</b></h6></center>
            <div>
                <h6>Pasivo a Corto Plazo</h6>
                <table class="table table-sm">
                    @foreach ($pasivoc as $actc )
                            <?php
                            $sf = 0;
                            if($actc->naturaleza == 'D') {
                                $sf = $actc->si + $actc->cargos - $actc->abonos;
                            }
                            else {
                                $sf = $actc->si - $actc->cargos + $actc->abonos;
                            }
                            $gsfpc+=$sf;
                            ?>
                        <tr>
                            <td>{{$actc->nombre}}</td>
                            <td class="text-end">{{number_format($sf,2)}}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><b>Total de Pasivo Circulante</b></td>
                        <td class="text-end"><b>{{number_format($gsfpc,2)}}</b></td>
                    </tr>
                </table>
            </div>
            <div>
                <h6>Pasivo a Largo Plazo</h6>
                <table class="table table-sm">
                    @foreach ($pasivof as $actc )
                            <?php
                            $sf = 0;
                            if($actc->naturaleza == 'D') {
                                $sf = $actc->si + $actc->cargos - $actc->abonos;
                            }
                            else {
                                $sf = $actc->si - $actc->cargos + $actc->abonos;
                            }
                            $gsfpf+=$sf;
                            ?>
                        <tr>
                            <td>{{$actc->nombre}}</td>
                            <td class="text-end">{{number_format($sf,2)}}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><b>Total de Pasivo Fijo</b></td>
                        <td class="text-end"><b>{{number_format($gsfpf,2)}}</b></td>
                    </tr>
                </table>
            </div>
            <div class="mt-5">
                <table class="table table-sm">
                    <tr>
                        <td><b>SUMA DEL PASIVO</b></td>
                        <td class="text-end"><b>{{number_format($gsfpc+$gsfpf,2)}}</b></td>
                    </tr>
                </table>
            </div>
            <div>
                <h6>Capital</h6>
                <table class="table table-sm">
                    @foreach ($capital as $actc )
                            <?php
                            $sf = 0;
                            if($actc->naturaleza == 'D') {
                                $sf = $actc->si + $actc->cargos - $actc->abonos;
                            }
                            else {
                                $sf = $actc->si - $actc->cargos + $actc->abonos;
                            }
                            $gsfca+=$sf;
                            ?>
                        <tr>
                            <td>{{$actc->nombre}}</td>
                            <td class="text-end">{{number_format($sf,2)}}</td>
                        </tr>
                    @endforeach
                    @foreach ($resultadoin as $actc )
                            <?php
                            $sf = 0;
                            if($actc->naturaleza == 'D') {
                                $sf = $actc->si + $actc->cargos - $actc->abonos;
                            }
                            else {
                                $sf = $actc->si - $actc->cargos + $actc->abonos;
                            }
                            $gsfre+=$sf;
                            ?>
                    @endforeach
                    @foreach ($resultadoeg as $actc )
                            <?php
                            $sf = 0;
                            if($actc->naturaleza == 'D') {
                                $sf = $actc->si + $actc->cargos - $actc->abonos;
                            }
                            else {
                                $sf = $actc->si - $actc->cargos + $actc->abonos;
                            }
                            $gsfre-=$sf;
                            ?>
                    @endforeach
                    <tr>
                        <td><b>Total de Capital</b></td>
                        <td class="text-end"><b>{{number_format($gsfca,2)}}</b></td>
                    </tr>
                    <tr>
                        <td><b>Utilidad o Perdida del Ejercicio</b></td>
                        <td class="text-end"><b>{{number_format($gsfre,2)}}</b></td>
                    </tr>
                </table>
            </div>
            <div class="mt-5">
                <table class="table table-sm">
                    <tr>
                        <td><b>SUMA DEL PASIVO y CAPITAL</b></td>
                        <td class="text-end"><b>{{number_format($gsfpc+$gsfpf+$gsfca+$gsfre,2)}}</b></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
