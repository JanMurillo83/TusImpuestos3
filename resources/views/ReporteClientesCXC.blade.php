<!doctype html>
<html lang="en">
<head>
    <?php
        $dafis = DB::table('datos_fiscales')->where('team_id',$id_empresa)->first();
        $logo = public_path('storage/'.$dafis->logo);
        $fecha_i = \Carbon\Carbon::create(2000,1,1);
        $fecha_i_lab = '-';
        $fecha_f = \Carbon\Carbon::create(2100,1,1);
        $fecha_f_lab = '-';
        if($fecha_inicio != null){
            $fecha_i = \Carbon\Carbon::create($fecha_inicio);
            $fecha_i_lab = $fecha_i->format('d-m-Y');
        }
        if($fecha_fin != null){
            $fecha_f = \Carbon\Carbon::create($fecha_fin);
            $fecha_f_lab = $fecha_f->format('d-m-Y');
        }
        if($cliente_id == null) {
            $cuentas = \App\Models\SaldosReportes::where('team_id', $id_empresa)->where('acumula', '10501000')->get();
            $auxiliares = \App\Models\Auxiliares::select(['auxiliares.cat_polizas_id','auxiliares.team_id','auxiliares.codigo','auxiliares.cargo','auxiliares.factura'])->where('auxiliares.team_id', $id_empresa)
                ->join('cat_polizas', 'cat_polizas.id', 'auxiliares.cat_polizas_id')
                ->whereBetween('cat_polizas.fecha', [$fecha_i, $fecha_f])
                ->where('codigo', 'like', '10501%')->get();
        }
        else {
            $cuentas = \App\Models\SaldosReportes::where('team_id', $id_empresa)->where('codigo', $cliente_id)->get();
            $auxiliares = \App\Models\Auxiliares::select(['auxiliares.cat_polizas_id','auxiliares.team_id','auxiliares.codigo','auxiliares.cargo','auxiliares.factura'])->where('auxiliares.team_id', $id_empresa)
                ->join('cat_polizas', 'cat_polizas.id', 'auxiliares.cat_polizas_id')
                ->whereBetween('cat_polizas.fecha', [$fecha_i, $fecha_f])
                ->where('codigo', $cliente_id)->get();
        }
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
                    <label style="font-size: 14px; font-weight: bold">Reporte de Cuentas por Cobrar</label>
                </center>
            </div>
            <div class="col-3">
                <label style="font-size: 12px">Fecha Inicio: {{$fecha_i_lab}}</label>
                <br>
                <label style="font-size: 12px">Fecha Fin: {{$fecha_f_lab}}</label>
            </div>
        </div>
        <hr>
        <div class="row">
            <table class="table table-bordered">
                <thead style="background-color: #aab2b3">
                    <tr>
                        <th>Cliente</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cuentas as $cuenta)
                        <?php
                            $ant = floatval($cuenta->anterior);
                            $car = floatval($cuenta->cargos);
                            $abo = floatval($cuenta->abonos);
                            $saldo = $ant + $car - $abo;
                        ?>
                        @if($saldo != 0)
                            <?php
                                //dd($fecha_i->format('Y-m-d'), $fecha_f->format('Y-m-d'));
                                $aux_c = \App\Models\Auxiliares::where('auxiliares.team_id',$id_empresa)
                                    ->join('cat_polizas', 'cat_polizas.id', 'auxiliares.cat_polizas_id')
                                    ->whereBetween('cat_polizas.fecha', [$fecha_i->format('Y-m-d'), $fecha_f->format('Y-m-d')])
                                    ->where('codigo',$cuenta->codigo)->where('cargo','>',0)->get();
                                $aux_a = \App\Models\Auxiliares::where('auxiliares.team_id',$id_empresa)
                                    ->join('cat_polizas', 'cat_polizas.id', 'auxiliares.cat_polizas_id')
                                    ->whereBetween('cat_polizas.fecha', [$fecha_i->format('Y-m-d'), $fecha_f->format('Y-m-d')])
                                    ->where('codigo',$cuenta->codigo)->where('abono','>',0)->get();
                            ?>
                            <tr style="background-color: #bcbebf">
                                <td style="font-weight: bold">{{$cuenta->cuenta}}</td>
                                <td style="text-align: right;font-weight: bold">{{'$'.number_format($saldo,2)}}</td>
                            </tr>
                            <tr>
                                <td>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th colspan="3">Facturas</th>
                                            </tr>
                                            <tr>
                                                <th>Factura</th>
                                                <th>Fecha</th>
                                                <th>Importe</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $total_c = 0; ?>
                                            @foreach($aux_c as $a_c)
                                                <?php
                                                    $pol = \App\Models\CatPolizas::where('id',$a_c->cat_polizas_id)->first();
                                                    $total_c+= floatval($a_c->cargo);
                                                ?>
                                                <tr>
                                                    <td>{{$a_c->factura}}</td>
                                                    <td>{{\Carbon\Carbon::create($pol->fecha)->format('d-m-Y')}}</td>
                                                    <td style="text-align: right">{{'$'.number_format($a_c->cargo,2)}}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="2" style="text-align: right;font-weight: bold">Total:</td>
                                            <td style="text-align: right;font-weight: bold">{{'$'.number_format($total_c,2)}}</td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </td>
                                <td>
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th colspan="3">Pagos</th>
                                        </tr>
                                        <tr>
                                            <th>Factura</th>
                                            <th>Fecha</th>
                                            <th>Importe</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <?php $total_a = 0; ?>
                                        @foreach($aux_a as $a_a)
                                                <?php
                                                $pol = \App\Models\CatPolizas::where('id',$a_a->cat_polizas_id)->first();
                                                $total_a += floatval($a_a->abono);
                                                ?>
                                            <tr>
                                                <td>{{$a_a->factura}}</td>
                                                <td>{{\Carbon\Carbon::create($pol->fecha)->format('d-m-Y')}}</td>
                                                <td style="text-align: right">{{'$'.number_format($a_a->abono,2)}}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2" style="text-align: right;font-weight: bold">Total:</td>
                                                <td style="text-align: right;font-weight: bold">{{'$'.number_format($total_a,2)}}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
