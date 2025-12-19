<?php
use \Illuminate\Support\Facades\DB;
use \App\Models\SaldosReportes;
$empresas = DB::table('teams')->where('id',$empresa)->get()[0];
$cuentas = SaldosReportes::where('team_id',$empresa)->orderBy('codigo')->get();
$fecha = \Carbon\Carbon::now();
$saldo1 = 0;
$saldo2 = 0;
$saldo3 = 0;
$saldo4 = 0;
$saldo5 = 0;
?>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balanza</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row mt-5">
        <div class="col-3">
            <img src="{{$logo}}" alt="Tus-Impuestos" width="120px">
        </div>
        <div class="col-6">
            <center>
                <h5>{{$empresas->name}}</h5>
                <div>
                    Balanza de Comprobacion Periodo {{$periodo}}
                </div>
            </center>
        </div>
        <div class="col-3">
            Fecha de Emision: <?php echo $fecha->toDateString('d-m-Y'); ?>
        </div>
    </div>
    <hr>
    <div class="row mt-2">
        <div class="col-12">
            <table class="table border">
                <tr>
                    <th style="font-weight: bold">Codigo</th>
                    <th style="font-weight: bold">Cuenta</th>
                    <th style="font-weight: bold;text-align: center; justify-content: center;">Saldo Inicial</th>
                    <th style="font-weight: bold;text-align: center; justify-content: center;">Cargos</th>
                    <th style="font-weight: bold;text-align: center; justify-content: center;">Abonos</th>
                    <th style="font-weight: bold;text-align: center; justify-content: center;">Saldo Final</th>
                </tr>
                @foreach($cuentas as $cuenta)
                        <?php
                        if($cuenta->nivel == 1) {
                            if ($cuenta->naturaleza == 'D') $saldo1 += $cuenta->anterior;
                            else $saldo1 -= $cuenta->anterior;
                            $saldo2 += $cuenta->cargos;
                            $saldo3 += $cuenta->abonos;
                            if ($cuenta->naturaleza == 'D') $saldo4 += $cuenta->final;
                            else $saldo4 -= $cuenta->final;
                        }
                        ?>
                        @if($cuenta->anterior > 0||$cuenta->cargos > 0||$cuenta->abonos > 0||$cuenta->final > 0)
                        <tr>
                            <td>{{$cuenta->codigo}}</td>
                            <td>{{$cuenta->cuenta}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($cuenta->anterior,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($cuenta->cargos,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($cuenta->abonos,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($cuenta->final,2)}}</td>
                        </tr>
                       @endif
                @endforeach
                        <tr>
                            <td colspan="2" style="font-weight: bold">Totales</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo1,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo2,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo3,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo4,2)}}</td>
                        </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>

