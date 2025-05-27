<?php
use \Illuminate\Support\Facades\DB;
$empresas = DB::table('teams')->where('id',$empresa)->get()[0];
$cuentas = DB::select("SELECT * FROM saldos_reportes
    WHERE nivel = 1 AND team_id = $empresa AND (COALESCE(anterior,0)+COALESCE(cargos,0)+COALESCE(abonos,0)) != 0 ");
$fecha = \Carbon\Carbon::now();
$saldo1 = 0;
$saldo1_acum = 0;
$saldo2 = 0;
$saldo2_acum = 0;
$saldo3 = 0;
$saldo3_acum = 0;
$saldo4 = 0;
$saldo4_acum = 0;
$saldo5 = 0;
$saldo5_acum = 0;
?>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balance General</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row mt-5">
        <div class="col-3">
            <img src="{{asset('images/MainLogo.png')}}" alt="Tus-Impuestos" width="120px">
        </div>
        <div class="col-6">
            <center>
                <h5>{{$empresas->name}}</h5>
                <div>
                    Posicion financiera, Estado de Resultados Periodo {{$periodo}}
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
            <label style="font-weight: bold">Ingresos</label>
            <table class="table border">
                <tr>
                    <th style="font-weight: bold">Codigo</th>
                    <th style="font-weight: bold">Cuenta</th>
                    <th style="font-weight: bold;text-align: end; justify-content: end">Periodo</th>
                    <th style="font-weight: bold;text-align: end; justify-content: end">Acumulado</th>
                </tr>
                @foreach($cuentas as $cuenta)
                        <?php $cod = intval(substr($cuenta->codigo,0,3));
                        $saldo = 0;
                        $saldo_acum = 0;
                        if($cuenta->naturaleza == 'D') {
                            $saldo = $cuenta->cargos - $cuenta->abonos;
                            $saldo_acum = $cuenta->anterior;
                        }else{
                            $saldo = $cuenta->abonos - $cuenta->cargos;
                            $saldo_acum = $cuenta->anterior;
                        }
                        ?>
                    @if($cod > 399&&$cod < 500)
                            <?php $saldo1+=$saldo; $saldo1_acum +=$saldo_acum?>
                        <tr>
                            <td>{{$cuenta->codigo}}</td>
                            <td>{{$cuenta->cuenta}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo_acum + $saldo ,2)}}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight: bold;">Total de Ingresos:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo1,2)}}</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo1 + $saldo1_acum,2)}}</td>
                </tr>
            </table>
            <label style="font-weight: bold">Egresos</label>
            <table class="table border">
                <tr>
                    <th style="font-weight: bold">Codigo</th>
                    <th style="font-weight: bold">Cuenta</th>
                    <th style="font-weight: bold;text-align: end; justify-content: end">Periodo</th>
                    <th style="font-weight: bold;text-align: end; justify-content: end">Acumulado</th>
                </tr>
                @foreach($cuentas as $cuenta)
                        <?php $cod = intval(substr($cuenta->codigo,0,3));
                        $saldo = 0;
                        $saldo_acum = 0;
                        if($cuenta->naturaleza == 'D') {
                            $saldo = $cuenta->cargos - $cuenta->abonos;
                            $saldo_acum = $cuenta->anterior;
                        }else{
                            $saldo = ($cuenta->abonos - $cuenta->cargos);
                            $saldo_acum = $cuenta->anterior;
                        }
                        ?>
                    @if($cod > 499)
                            <?php $saldo2+=$saldo;$saldo2_acum+=$saldo_acum; ?>
                        <tr>
                            <td>{{$cuenta->codigo}}</td>
                            <td>{{$cuenta->cuenta}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo_acum,2)}}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight: bold;">Total Egresos:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo2,2)}}</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo2+$saldo2_acum,2)}}</td>
                </tr>
                <tr>
                    <td colspan="2" style="font-weight: bold;">Utilidad o Perdida:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format(($saldo1-($saldo2*-1)),2)}}</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format(($saldo1-($saldo2*-1))+($saldo1_acum-($saldo2_acum*-1)),2)}}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>

