<?php
use Carbon\Carbon;
$empresa = $_GET['empresa'];
$ejercicio = $_GET['ejercicio'];
$periodo = $_GET['periodo'];
$empresas = DB::table('teams')->where('id',$empresa)->get()[0];
if($periodo == 1) $ini = 'si'; else $ini = 'si';
$ca = 'c'.$periodo;
$ab = 'a'.$periodo;
$ingresos = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n2',0)->whereBetween('codigo',[40000000,49999999])->get();
$egresos = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
->where('team_id',$empresa)->where('ejercicio',$ejercicio)->where('n2',0)->whereBetween('codigo',[50000000,99999999])->get();
$fecha = Carbon::now();
$gsfin = 0;
$gsfeg = 0;
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
        <div class="container mt-5">
            <div class="row">
                <div class="col-3">
                    <img src="{{asset('images/MainLogo.png')}}" alt="Tus-Impuestos" width="120px">
                </div>
                <div class="col-6">
                    <center>
                        <h5>{{$empresas->name}}</h5>
                        <div>
                            Estado de Resultados del 01/01/2024 al 31/01/2024
                        </div>
                    </center>
                </div>
                <div class="col-3">
                    Fecha de Emision: <?php echo $fecha->toDateString('d-m-Y'); ?>
                </div>
            </div>
            <hr>
            <div class="mt-2 row">
                <div class="col-12">
                    <div>
                        <table class="table table-sm" style="width: 700px !important">
                        <tr>
                            <th style="width: 300px !important"></th>
                            <th style="width: 200px !important"class="text-end">Periodo</th>
                            <th style="width: 300px !important"class="text-end">Acumulado</th>
                        </tr>
                        </table>
                        <h5><b>Ingresos</b></h5>
                        <table class="table table-sm" style="width: 700px !important">

                            @foreach ($ingresos as $actc )
                            <?php
                                $sf = 0;
                                if($actc->naturaleza == 'D') {
                                    $sf = $actc->si + $actc->cargos - $actc->abonos;
                                }
                                else {
                                    $sf = $actc->si - $actc->cargos + $actc->abonos;
                                }
                                $gsfin+=$sf;
                            ?>
                            <tr>
                                <td style="width: 300px !important">{{$actc->nombre}}</td>
                                <td style="width: 200px !important" class="text-end">{{number_format($sf,2)}}</td>
                                <td style="width: 200px !important" class="text-end">{{number_format($sf,2)}}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td style="width: 300px !important" ><b>Total de Ingresos</b></td>
                                <td style="width: 200px !important" class="text-end"><b>{{number_format($gsfin,2)}}</b></td>
                                <td style="width: 200px !important" class="text-end"><b>{{number_format($gsfin,2)}}</b></td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <h5><b>Egresos</b></h5>
                        <table class="table table-sm" style="width: 700px !important">
                            @foreach ($egresos as $actc )
                            <?php
                                $sf = 0;
                                if($actc->naturaleza == 'D') {
                                    $sf = $actc->si + $actc->cargos - $actc->abonos;
                                }
                                else {
                                    $sf = $actc->si - $actc->cargos + $actc->abonos;
                                }
                                $gsfeg+=$sf;
                            ?>
                            <tr>
                                <td style="width: 300px !important">{{$actc->nombre}}</td>
                                <td style="width: 200px !important" class="text-end">{{number_format($sf,2)}}</td>
                                <td style="width: 200px !important" class="text-end">{{number_format($sf,2)}}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td style="width: 300px !important" ><b>Total de Egresos</b></td>
                                <td style="width: 200px !important" class="text-end"><b>{{number_format($gsfeg,2)}}</b></td>
                                <td style="width: 200px !important" class="text-end"><b>{{number_format($gsfeg,2)}}</b></td>
                            </tr>
                        </table>
                        <hr>
                        <table class="table table-sm" style="width: 700px !important">
                            <tr>
                                <td style="width: 300px !important" ><b>Utilidad o Perdida</b></td>
                                <td style="width: 200px !important" class="text-end"><b>{{number_format($gsfin-$gsfeg,2)}}</b></td>
                                <td style="width: 200px !important" class="text-end"><b>{{number_format($gsfin-$gsfeg,2)}}</b></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
