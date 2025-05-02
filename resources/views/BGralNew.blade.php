<?php
use \Illuminate\Support\Facades\DB;
    $empresas = DB::table('teams')->where('id',$empresa)->get()[0];
    $cuentas = DB::select("SELECT codigo,nombre,
    coalesce((SELECT sum(cargo) FROM auxiliares INNER JOIN cat_polizas ON auxiliares.cat_polizas_id = cat_polizas.id
    WHERE auxiliares.team_id = cat_cuentas.team_id AND periodo = $periodo AND ejercicio = $ejercicio
    AND substr(auxiliares.codigo,1,3) = substr(cat_cuentas.codigo,1,3)),0) cargos,
    coalesce((SELECT sum(abono) FROM auxiliares
    INNER JOIN cat_polizas ON auxiliares.cat_polizas_id = cat_polizas.id
    WHERE auxiliares.team_id = cat_cuentas.team_id AND periodo = $periodo AND ejercicio = $ejercicio
    AND substr(auxiliares.codigo,1,3) = substr(cat_cuentas.codigo,1,3)),0) abonos,
    naturaleza,'NA' rubro FROM cat_cuentas
    WHERE tipo = 'A' AND team_id = $empresa
    AND substr(codigo,4,2) = '00' AND substr(codigo,1,3)
    NOT IN('100','200','300','400','500','600','700','800','900')");
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
                        Posicion financiera, Balance General Periodo {{$periodo}}
                    </div>
                </center>
            </div>
            <div class="col-3">
                Fecha de Emision: <?php echo $fecha->toDateString('d-m-Y'); ?>
            </div>
        </div>
        <hr>
        <div class="row mt-2">
            <div class="col-6">
                <label style="font-weight: bold">Activo a corto plazo</label>
                <table class="table border">
                    <tr>
                        <th style="font-weight: bold">Codigo</th>
                        <th style="font-weight: bold">Cuenta</th>
                        <th style="font-weight: bold">Saldo</th>
                    </tr>
                    @foreach($cuentas as $cuenta)
                            <?php $cod = intval(substr($cuenta->codigo,0,3));
                            $saldo = 0;
                            if($cuenta->naturaleza == 'D') {
                                $saldo = $cuenta->cargos - $cuenta->abonos;
                            }else{
                                $saldo = ($cuenta->abonos - $cuenta->cargos);
                            }

                            ?>
                        @if($cod < 150)
                                <?php $saldo1+=$saldo; ?>
                            <tr>
                                <td>{{$cuenta->codigo}}</td>
                                <td>{{$cuenta->nombre}}</td>
                                <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                            </tr>

                        @endif
                    @endforeach
                    <tr>
                        <td colspan="2" style="font-weight: bold;">Total de Activo a corto Plazo:</td>
                        <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo1,2)}}</td>
                    </tr>
                </table>
                <label style="font-weight: bold">Activo a largo plazo</label>
                <table class="table border">
                    <tr>
                        <th style="font-weight: bold">Codigo</th>
                        <th style="font-weight: bold">Cuenta</th>
                        <th style="font-weight: bold">Saldo</th>
                    </tr>
                    @foreach($cuentas as $cuenta)
                            <?php $cod = intval(substr($cuenta->codigo,0,3));
                            $saldo = 0;
                            if($cuenta->naturaleza == 'D') {
                                $saldo = $cuenta->cargos - $cuenta->abonos;
                            }else{
                                $saldo = ($cuenta->abonos - $cuenta->cargos);
                            }

                            ?>
                        @if($cod > 149&&$cod < 200)
                                <?php $saldo2+=$saldo; ?>
                            <tr>
                                <td>{{$cuenta->codigo}}</td>
                                <td>{{$cuenta->nombre}}</td>
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
                <label style="font-weight: bold">Pasivo a corto plazo</label>
                <table class="table border">
                    <tr>
                        <th style="font-weight: bold">Codigo</th>
                        <th style="font-weight: bold">Cuenta</th>
                        <th style="font-weight: bold">Saldo</th>
                    </tr>
                    @foreach($cuentas as $cuenta)
                            <?php $cod = intval(substr($cuenta->codigo,0,3));
                            $saldo = 0;
                            if($cuenta->naturaleza == 'D') {
                                $saldo = $cuenta->cargos - $cuenta->abonos;
                            }else{
                                $saldo = ($cuenta->abonos - $cuenta->cargos);
                            }

                            ?>
                        @if($cod > 199&&$cod < 300)
                                <?php $saldo3+=$saldo; ?>
                            <tr>
                                <td>{{$cuenta->codigo}}</td>
                                <td>{{$cuenta->nombre}}</td>
                                <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="2" style="font-weight: bold;">Total de Pasivo a corto Plazo:</td>
                        <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo3,2)}}</td>
                    </tr>
                </table>
                <label style="font-weight: bold">Capital</label>
                <table class="table border">
                    <tr>
                        <th style="font-weight: bold">Codigo</th>
                        <th style="font-weight: bold">Cuenta</th>
                        <th style="font-weight: bold">Saldo</th>
                    </tr>
                    @foreach($cuentas as $cuenta)
                            <?php $cod = intval(substr($cuenta->codigo,0,3));
                            $saldo = 0;
                            if($cuenta->naturaleza == 'D') {
                                $saldo = $cuenta->cargos - $cuenta->abonos;
                            }else{
                                $saldo = ($cuenta->abonos - $cuenta->cargos);
                            }

                            ?>
                        @if($cod > 299&&$cod < 400)
                                <?php $saldo4+=$saldo; ?>
                            <tr>
                                <td>{{$cuenta->codigo}}</td>
                                <td>{{$cuenta->nombre}}</td>
                                <td style="text-align: end; justify-content: end">{{'$'.number_format($saldo,2)}}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="2" style="font-weight: bold;">Total de Capital:</td>
                        <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo4,2)}}</td>
                    </tr>
                </table>
                <table class="table border">
                    @foreach($cuentas as $cuenta)
                            <?php $cod = intval(substr($cuenta->codigo,0,3));
                            $saldo = 0;
                            if($cuenta->naturaleza == 'D') {
                                $saldo = $cuenta->cargos - $cuenta->abonos;
                            }else{
                                $saldo = ($cuenta->abonos - $cuenta->cargos) * -1;
                            }

                            ?>
                        @if($cod > 399)
                                <?php $saldo5+=$saldo; ?>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="2" style="font-weight: bold;">Resultado del Ejercicio:</td>
                        <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo5,2)}}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight: bold;">Suma de Pasivo y Capital:</td>
                        <td style="font-weight: bold; text-align: end; justify-content: end">{{'$'.number_format($saldo3+$saldo4-$saldo5,2)}}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
