<?php
use Carbon\Carbon;
$empresa = $_GET['empresa'];
$ejercicio = $_GET['ejercicio'];
$periodo = $_GET['periodo'];
$empresas = DB::table('teams')->where('id',$empresa)->get()[0];
if($periodo == 1) $ini = 'si'; else $ini = 'si';
$ca = 'c'.$periodo;
$ab = 'a'.$periodo;
$cuentas = DB::table('saldoscuentas')->select('codigo','nombre','si',DB::raw("$ca cargos, $ab abonos"),'naturaleza')
->where('team_id',$empresa)->where('ejercicio',$ejercicio)->get();
$fecha = Carbon::now();
$gsi = 0;
$gsc = 0;
$gsa = 0;
$gsf = 0;
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
                }
            }
        </style>
        <script type="text/javascript">
            $( document ).ready(async function() {
                await sleep(500);
                window.print();
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
                            Balanza de Comprobacion al 31/01/2024
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
                    <table class="table">
                        <tr>
                            <th class="text-center"><b>Cuenta</b></th>
                            <th class="text-center"><b>Nombre</b></th>
                            <th class="text-center"><b>Saldo Inicial</b></th>
                            <th class="text-center"><b>Cargos</b></th>
                            <th class="text-center"><b>Abonos</b></th>
                            <th class="text-center"><b>Saldo Final</b></th>
                        </tr>
                        @foreach ($cuentas as $cuenta )
                        <?php
                            $gsc+=$cuenta->cargos;
                            $gsa+=$cuenta->abonos;
                            $sf = 0;
                            if($cuenta->naturaleza == 'D') {
                                $sf = $cuenta->si + $cuenta->cargos - $cuenta->abonos;
                                $gsi+=$cuenta->si;
                                $gsf+=$sf;
                            }
                            else {
                                $sf = $cuenta->si - $cuenta->cargos + $cuenta->abonos;
                                $gsi-=$cuenta->si;
                                $gsf-=$sf;
                            }

                        ?>
                        <tr class="ms-4">
                            <td class="text-start">{{$cuenta->codigo}}</td>
                            <td class="text-start">{{$cuenta->nombre}}</td>
                            <td class="text-end">{{number_format($cuenta->si,2)}}</td>
                            <td class="text-end">{{number_format($cuenta->cargos,2)}}</td>
                            <td class="text-end">{{number_format($cuenta->abonos,2)}}</td>
                            <td class="text-end">{{number_format($sf,2)}}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <th><b></b></th>
                            <th><b></b></th>
                            <th class="text-end"><b>{{number_format($gsi,2)}}</b></th>
                            <th class="text-end"><b>{{number_format($gsc,2)}}</b></th>
                            <th class="text-end"><b>{{number_format($gsa,2)}}</b></th>
                            <th class="text-end"><b>{{number_format($gsf,2)}}</b></th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
