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
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        @media print{
            html, body {
            -webkit-print-color-adjust: exact;
            }
        }
        table {
            border-collapse: collapse;
            width: 100%;
            border-bottom: 1px solid #000000;
            margin-bottom: 2rem;
        }
        th, td {
            text-align: left;
            padding: 8px;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            background-color: #edf4ff;
            font-weight: bold;
        }
    </style>
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
                    Posicion financiera, Balance General Periodo {{$periodo}}
                </div>
            </center>
        </div>
        <div class="col-3" style="font-size: 10px">
            Fecha de Emisión: <?php echo $fecha->toDateString('d-m-Y'); ?>
        </div>
    </div>
    <hr>
    <div class="row mt-2">
        <div class="col-6">
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
        <div class="col-6">
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

    <?php
    // Verificar si el balance está cuadrado
    $total_activo = $saldo1 + $saldo2;
    $total_pasivo_capital = $saldo3 + $saldo4 + $saldo5;
    $diferencia = abs($total_activo - $total_pasivo_capital);
    $cuadrado = $diferencia < 0.01; // Tolerancia de 1 centavo
    ?>

    @if($cuadrado)
        <!-- Balance Cuadrado -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-success" style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;">
                    <h5 style="color: #155724; margin: 0;">✅ BALANCE CUADRADO</h5>
                    <p style="color: #155724; margin: 10px 0 0 0;">
                        La ecuación contable se cumple: ACTIVO = PASIVO + CAPITAL
                        <br>
                        <strong>{{'$'.number_format($total_activo,2)}} = {{'$'.number_format($total_pasivo_capital,2)}}</strong>
                    </p>
                </div>
            </div>
        </div>
    @else
        <!-- Balance Descuadrado -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-danger" style="background-color: #f8d7da; border: 2px solid #dc3545; padding: 15px; border-radius: 5px;">
                    <h5 style="color: #721c24; margin: 0;">❌ BALANCE DESCUADRADO</h5>
                    <p style="color: #721c24; margin: 10px 0 0 0;">
                        <strong>Total ACTIVO:</strong> {{'$'.number_format($total_activo,2)}}
                        <br>
                        <strong>Total PASIVO + CAPITAL:</strong> {{'$'.number_format($total_pasivo_capital,2)}}
                        <br>
                        <strong style="font-size: 1.2em; color: #dc3545;">DIFERENCIA: {{'$'.number_format($diferencia,2)}}</strong>
                    </p>
                    <hr style="border-top: 1px solid #dc3545; margin: 10px 0;">
                    <p style="color: #721c24; margin: 0; font-size: 0.9em;">
                        <strong>Acción recomendada:</strong> Ejecute el diagnóstico para identificar el problema:<br>
                        <code style="background-color: #fff; padding: 3px 6px; border-radius: 3px;">php artisan balance:diagnosticar --team-id={{$empresa}}</code>
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Resumen de Verificación -->
    <div class="row mt-3">
        <div class="col-12">
            <table style="border: none; margin-bottom: 0;">
                <thead>
                    <tr style="background-color: #e9ecef;">
                        <th colspan="2" style="text-align: center; font-weight: bold;">VERIFICACIÓN DEL BALANCE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: bold; width: 50%;">Total ACTIVO:</td>
                        <td style="text-align: end; font-weight: bold;">{{'$'.number_format($total_activo,2)}}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Total PASIVO:</td>
                        <td style="text-align: end;">{{'$'.number_format($saldo3,2)}}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Total CAPITAL:</td>
                        <td style="text-align: end;">{{'$'.number_format($saldo4,2)}}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Resultado del Ejercicio:</td>
                        <td style="text-align: end;">{{'$'.number_format($saldo5,2)}}</td>
                    </tr>
                    <tr style="border-top: 2px solid #000;">
                        <td style="font-weight: bold;">Total PASIVO + CAPITAL:</td>
                        <td style="text-align: end; font-weight: bold;">{{'$'.number_format($total_pasivo_capital,2)}}</td>
                    </tr>
                    <tr style="background-color: {{$cuadrado ? '#d4edda' : '#f8d7da'}};">
                        <td style="font-weight: bold; color: {{$cuadrado ? '#155724' : '#721c24'}};">Estado:</td>
                        <td style="text-align: end; font-weight: bold; color: {{$cuadrado ? '#155724' : '#721c24'}};">
                            {{$cuadrado ? '✅ CUADRADO' : '❌ DESCUADRADO'}}
                        </td>
                    </tr>
                    @if(!$cuadrado)
                    <tr style="background-color: #fff3cd; border-top: 2px solid #dc3545;">
                        <td style="font-weight: bold; color: #856404;">Diferencia a corregir:</td>
                        <td style="text-align: end; font-weight: bold; color: #dc3545; font-size: 1.1em;">{{'$'.number_format($diferencia,2)}}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
