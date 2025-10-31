<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <?php
        use App\Models\Facturas;
        use App\Models\Team;
        use Illuminate\Support\Facades\DB;
        use Carbon\Carbon;
        $empresa = Team::where('id',$idempresa)->first();
        $facturas = Facturas::where('team_id',$idempresa)->whereBetween(DB::raw('DATE(fecha)'),[$inicial,$final])->get();
    ?>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <center>
                    <h1>Resumen de Facturas</h1>
                    <h3>{{$empresa->name}}</h3>
                    <h5>Periodo: {{Carbon::create($inicial)->format('d-m-Y')}} a {{Carbon::create($final)->format('d-m-Y')}}</h5>
                </center>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <table class="table table-bordered table-striped" style="font-size: 8px !important;">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Moneda</th>
                            <th>T.Cambio</th>
                            <th>Subtotal USD</th>
                            <th>IVA USD</th>
                            <th>Total USD</th>
                            <th>Subtotal MXN</th>
                            <th>IVA MXN</th>
                            <th>Total MXN</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $subtotal_usd = 0;
                        $ivatotal_usd = 0;
                        $tottotal_usd = 0;
                        $subtotal_mxn = 0;
                        $ivatotal_mxn = 0;
                        $tottotal_mxn = 0;
                    ?>
                    @foreach($facturas as $factura)
                        <?php
                            $tcambio = 1;
                            if(floatval($factura->tcambio) != 0) $tcambio = $factura->tcambio;
                            $sub_mxn = 0;
                            $iva_mxn = 0;
                            $tot_mxn = 0;
                            $sub_usd = 0;
                            $iva_usd = 0;
                            $tot_usd = 0;
                            if($factura->moneda == 'USD'){
                                $sub_mxn = $factura->subtotal * $factura->tcambio;
                                $iva_mxn = $factura->iva * $factura->tcambio;
                                $tot_mxn = $factura->total * $factura->tcambio;
                                $sub_usd = $factura->subtotal;
                                $iva_usd = $factura->iva;
                                $tot_usd = $factura->total;
                            }else{
                                $sub_mxn = $factura->subtotal;
                                $iva_mxn = $factura->iva;
                                $tot_mxn = $factura->total;
                                $sub_usd = 0;
                                $iva_usd = 0;
                                $tot_usd = 0;
                            }
                            if($factura->estado == 'Timbrada') {
                                $subtotal_usd += $sub_usd;
                                $ivatotal_usd += $iva_usd;
                                $tottotal_usd += $tot_usd;
                                $subtotal_mxn += $sub_mxn;
                                $ivatotal_mxn += $iva_mxn;
                                $tottotal_mxn += $tot_mxn;
                            }
                        ?>
                        <tr>
                            <td>{{$factura->docto}}</td>
                            <td>{{Carbon::create($factura->fecha)->format('d-m-Y')}}</td>
                            <td>{{$factura->nombre}}</td>
                            <td>{{$factura->moneda}}</td>
                            <td>{{'$'.number_format($tcambio,4)}}</td>
                            <td>{{'$'.number_format($sub_usd,2)}}</td>
                            <td>{{'$'.number_format($iva_usd,2)}}</td>
                            <td>{{'$'.number_format($tot_usd,2)}}</td>
                            <td>{{'$'.number_format($sub_mxn,2)}}</td>
                            <td>{{'$'.number_format($iva_mxn,2)}}</td>
                            <td>{{'$'.number_format($tot_mxn,2)}}</td>
                            <td>{{$factura->estado}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; font-size: 10px !important;">
                            <td colspan="5">TOTALES :</td>
                            <td>{{'$'.number_format($subtotal_usd,2)}}</td>
                            <td>{{'$'.number_format($ivatotal_usd,2)}}</td>
                            <td>{{'$'.number_format($tottotal_usd,2)}}</td>
                            <td>{{'$'.number_format($subtotal_mxn,2)}}</td>
                            <td>{{'$'.number_format($ivatotal_mxn,2)}}</td>
                            <td>{{'$'.number_format($tottotal_mxn,2)}}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

