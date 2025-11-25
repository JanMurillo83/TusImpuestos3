<!doctype html>
<html lang="en">
<head>
    <?php
    $dafis = DB::table('datos_fiscales')->where('team_id',$id_empresa)->first();
    $logo = public_path('storage/'.$dafis->logo);
    $egresos = \Illuminate\Support\Facades\DB::table('auxiliares')
        ->where('auxiliares.codigo','like','20101%')
        ->where('a_periodo',$periodo)->where('a_ejercicio',$ejercicio)
        ->where('team_id',$id_empresa)->where('cargo','>',0)
        ->get();
    $ingresos = \Illuminate\Support\Facades\DB::table('auxiliares')
        ->where('auxiliares.codigo','like','10501%')
        ->where('a_periodo',$periodo)->where('a_ejercicio',$ejercicio)
        ->where('team_id',$id_empresa)->where('abono','>',0)
        ->get();
    //dd($cuentas);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saldo Clientes</title>
    <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-3">
            <img src="{{$logo}}" alt="Tus Impuestos" width="100px" style="margin-top: 1rem !important;">
        </div>
        <div class="col-6">
            <center>
                <label style="font-size: 12px">{{$dafis->nombre}}</label>
                <br>
                <label style="font-size: 14px; font-weight: bold">Análisis de Afectaciones de IVA y de IETU</label>
            </center>
        </div>
        <div class="col-3">
            <label style="font-size: 12px">Periodo: {{$periodo}}</label>
            <br>
            <label style="font-size: 12px">Ejercicio: {{$ejercicio}}</label>
        </div>
    </div>
    <hr>
    <div class="row">
        <table class="table table-bordered" style="font-size: 8px !important;">
            <thead>
                <tr class="table-primary">
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Número</th>
                    <th>Concepto</th>
                    <th>Flujo de Efectivo</th>
                    <th>Base</th>
                    <th>IVA</th>
                    <th>IVA pagado no acreditable</th>
                    <th>IETU</th>
                    <th>Acreditamiento para IETU</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="10" class="table-secondary">INGRESOS</td>
                </tr>
                <?php
                    $ing_flujo = 0;
                    $ing_base = 0;
                    $ing_iva = 0;
                    $ing_ietu = 0;
                    $eg_flujo = 0;
                    $eg_base = 0;
                    $eg_iva = 0;
                    $eg_ietu = 0;
                ?>
                @foreach($ingresos as $ingreso)
                    <?php
                        $poliza = \Illuminate\Support\Facades\DB::table('cat_polizas')
                        ->where('id',$ingreso->cat_polizas_id)->first();
                        $ing_flujo+= floatval($ingreso->abono);
                        $ing_base+= floatval($ingreso->abono/1.16);
                        $ing_iva+= floatval($ingreso->abono/1.16*0.16);
                        $ing_ietu+= floatval($ingreso->abono/1.16);
                    ?>
                    <tr>
                        <td>{{\Carbon\Carbon::create($poliza->fecha)->format('d-m-Y')}}</td>
                        <td>Ingreso</td>
                        <td>{{$poliza->folio}}</td>
                        <td>{{$ingreso->concepto}}</td>
                        <td style="text-align: right">{{'$'.number_format($ingreso->abono,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($ingreso->abono/1.16,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($ingreso->abono/1.16*+0.16,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($ingreso->abono/1.16,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                    </tr>
                @endforeach
                    <tr style="font-weight: bold">
                        <td colspan="4">Total Ingresos:</td>
                        <td style="text-align: right">{{'$'.number_format($ing_flujo,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($ing_base,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($ing_iva,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($ing_ietu,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                    </tr>
                    <tr>
                        <td colspan="10" class="table-secondary">EGRESOS</td>
                    </tr>
                    @foreach($egresos as $egreso)
                            <?php
                            $poliza = \Illuminate\Support\Facades\DB::table('cat_polizas')
                                ->where('id',$egreso->cat_polizas_id)->first();
                            $eg_flujo+= floatval($egreso->cargo);
                            $eg_base+= floatval($egreso->cargo/1.16);
                            $eg_iva+= floatval($egreso->cargo/1.16*0.16);
                            $eg_ietu+= floatval($egreso->cargo/1.16);
                            ?>
                        <tr>
                            <td>{{\Carbon\Carbon::create($poliza->fecha)->format('d-m-Y')}}</td>
                            <td>Ingreso</td>
                            <td>{{$poliza->folio}}</td>
                            <td>{{$egreso->concepto}}</td>
                            <td style="text-align: right">{{'$'.number_format($egreso->cargo,2)}}</td>
                            <td style="text-align: right">{{'$'.number_format($egreso->cargo/1.16,2)}}</td>
                            <td style="text-align: right">{{'$'.number_format($egreso->cargo/1.16*+0.16,2)}}</td>
                            <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                            <td style="text-align: right">{{'$'.number_format($egreso->cargo/1.16,2)}}</td>
                            <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                        </tr>
                    @endforeach
                    <tr style="font-weight: bold">
                        <td colspan="4">Total Egresos:</td>
                        <td style="text-align: right">{{'$'.number_format($eg_flujo,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($eg_base,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($eg_iva,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format($eg_ietu,2)}}</td>
                        <td style="text-align: right">{{'$'.number_format(0,2)}}</td>
                    </tr>
            </tbody>

        </table>
    </div>
</div>
