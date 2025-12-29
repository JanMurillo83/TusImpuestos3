<?php
use \Illuminate\Support\Facades\DB;
$empresas = DB::table('teams')->where('id',$empresa)->get()[0];
$cuentas = DB::select("SELECT * FROM saldos_reportes
    WHERE nivel = 1 AND team_id = $empresa
    AND (anterior+cargos+abonos) != 0");
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
    <title>Balance General</title>
    <style>
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .item1 {
            grid-column: 1 / span 2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="item1" style="margin-top: 2rem">
            {{$empresas->name}}
        </div>
        <div class="item1">
            Posición financiera, Balance General Periodo {{$periodo}} Fecha de Emisión: <?php echo $fecha->toDateString('d-m-Y'); ?>
        </div>
    </div>
    <div class="container">
        <div>
            <table>
                <thead>
                <tr>
                    <th colspan="3">Activo a corto plazo</th>
                </tr>
                <tr>
                    <th colspan="2" style="font-weight: bold">Cuenta</th>
                    <th style="font-weight: bold">Saldo</th>
                </tr>
                </thead>
                @foreach($cuentas as $cuenta)
                        <?php $cod = intval(substr($cuenta->codigo,0,3));
                        $saldo = 0;
                        if($cuenta->naturaleza == 'D') {
                            $saldo = $cuenta->cargos - $cuenta->abonos;
                        }else{
                            $saldo = ($cuenta->abonos - $cuenta->cargos);
                        }
                        $saldo+=$cuenta->anterior;
                        ?>
                    @if($cod < 150)
                            <?php $saldo1+=$saldo; ?>
                        <tr>
                            <td colspan="2">{{$cuenta->cuenta}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                        </tr>

                    @endif
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight: bold;">Total de Activo a corto Plazo:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo1,2)}}</td>
                </tr>
            </table>
            <table>
                <thead>
                <tr><th colspan="3">Activo a largo plazo</th></tr>
                <tr>
                    <th style="font-weight: bold" colspan="2">Cuenta</th>
                    <th style="font-weight: bold">Saldo</th>
                </tr>
                </thead>

                @foreach($cuentas as $cuenta)
                        <?php $cod = intval(substr($cuenta->codigo,0,3));
                        $saldo = 0;
                        if($cuenta->naturaleza == 'D') {
                            $saldo = $cuenta->cargos - $cuenta->abonos;
                        }else{
                            $saldo = ($cuenta->abonos - $cuenta->cargos);
                        }
                        $saldo+=$cuenta->anterior;
                        ?>
                    @if($cod > 149&&$cod < 200)
                            <?php $saldo2+=$saldo; ?>
                        <tr>
                            <td colspan="2">{{$cuenta->cuenta}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight: bold;">Total activo a largo Plazo:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo2,2)}}</td>
                </tr>
                <tr>
                    <td colspan="2" style="font-weight: bold;">Total de Activo:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo1+$saldo2,2)}}</td>
                </tr>
            </table>
        </div>
        <div>
            <table>
                <thead>
                <tr><th colspan="3">Pasivo a corto plazo</th></tr>
                <tr>
                    <th style="font-weight: bold" colspan="2">Cuenta</th>
                    <th style="font-weight: bold">Saldo</th>
                </tr>
                </thead>
                @foreach($cuentas as $cuenta)
                        <?php $cod = intval(substr($cuenta->codigo,0,3));
                        $saldo = 0;
                        if($cuenta->naturaleza == 'D') {
                            $saldo = $cuenta->cargos - $cuenta->abonos;
                        }else{
                            $saldo = ($cuenta->abonos - $cuenta->cargos);
                        }
                        $saldo+=$cuenta->anterior;
                        ?>
                    @if($cod > 199&&$cod < 300)
                            <?php $saldo3+=$saldo; ?>
                        <tr>
                            <td colspan="2">{{$cuenta->cuenta}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight: bold;">Total de Pasivo a corto Plazo:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo3,2)}}</td>
                </tr>
            </table>
            <table>
                <thead>
                <tr><th colspan="3">Capital</th></tr>
                <tr>
                    <th style="font-weight: bold" colspan="2">Cuenta</th>
                    <th style="font-weight: bold">Saldo</th>
                </tr>
                </thead>
                @foreach($cuentas as $cuenta)
                        <?php $cod = intval(substr($cuenta->codigo,0,3));
                        $saldo = 0;
                        if($cuenta->naturaleza == 'D') {
                            $saldo = $cuenta->cargos - $cuenta->abonos;
                        }else{
                            $saldo = ($cuenta->abonos - $cuenta->cargos);
                        }
                        $saldo+=$cuenta->anterior;
                        ?>
                    @if($cod > 299&&$cod < 400)
                            <?php $saldo4+=$saldo; ?>
                        <tr>
                            <td colspan="2">{{$cuenta->cuenta}}</td>
                            <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight: bold;">Total de Capital:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo4,2)}}</td>
                </tr>
            </table>
            <table>
                @foreach($cuentas as $cuenta)
                        <?php $cod = intval(substr($cuenta->codigo,0,3));

                        if($cod > 399) {
                            if ($cuenta->naturaleza == 'A') {
                                $saldo5 += $cuenta->final;
                            } else {
                                $saldo5 -= $cuenta->final;
                            }
                        }

                        ?>
                @endforeach
                <tr>
                    <td colspan="2" style="font-weight: bold;">Resultado del Ejercicio:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo5,2)}}</td>
                </tr>
                <tr>
                    <td colspan="2" style="font-weight: bold;">Suma de Pasivo y Capital:</td>
                    <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo3+$saldo4+$saldo5,2)}}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
