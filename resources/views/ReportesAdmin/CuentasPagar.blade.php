<!DOCTYPE html>
<html lang="es">
<head>
    <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.19.0/cdn/components/qr-code/qr-code.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=0.8" charset="UTF-8" />
    <title>Reportes de Ventas - TusImpuestos</title>
    <?php
    use App\Http\Controllers\AdminReportes;
    use Illuminate\Support\Facades\DB;
    $empresa = DB::table('datos_fiscales')->where('team_id',$team_id)->first();
    $datos = app(AdminReportes::class)->reporte_cuentaspagar($fecha_inicial,$fecha_final,$cliente,$team_id);
    $conceptos = array_column($datos->toArray(),'concepto');
    $conceptos = array_map('trim',$conceptos);
    $clientes = array_unique($conceptos);
    //dd($datos,$clientes);
    ?>
    <style>
        @media print {
            body {-webkit-print-color-adjust: exact;}
            @page {
                margin-top: 0.5cm;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-4">
            <img src="{{$empresa->logo}}" alt="" width="100px">
        </div>
        <div class="col-4">
            <center>
                <div><h6>{{$empresa->nombre}}</h6></div>
                <div><h6>Tus-Impuestos</h6></div>
                <div><h3>Cuentas por pagar</h3></div>
            </center>
        </div>
        <div class="col-4">
            <table class="table table-bordered table-info table-sm">
                <tr>
                    <td>Fecha inicial:</td>
                    <td>{{\Carbon\Carbon::create($fecha_inicial)->format('d-m-Y')}}</td>
                </tr>
                <tr>
                    <td>Fecha final:</td>
                    <td>{{\Carbon\Carbon::create($fecha_final)->format('d-m-Y')}}</td>
                </tr>
                <tr>
                    <td>Fecha de emisión:</td>
                    <td>{{\Carbon\Carbon::now()->format('d-m-Y')}}</td>
                </tr>
            </table>
        </div>
    </div>
    <hr>
    <div class="row">
        <?php $gran_total_c = 0;$gran_total_a = 0; ?>
        @foreach( $clientes as $clie)
            <table class="table table-bordered table-striped table-sm">
                <tr>
                    <th colspan="5" style="font-weight: bold">{{$clie}}</th>
                </tr>
            </table>
            <table class="table table-bordered table-sm">
                <tr class="table-secondary">
                    <th>Fecha</th>
                    <th>Factura</th>
                    <th>Póliza</th>
                    <th>UUID</th>
                    <th>Cargos</th>
                    <th>Abonos</th>
                </tr>
                    <?php
                    $total_c = 0;
                    $total_a = 0;
                    ?>
                @foreach($datos as $dato)
                    @if(trim($dato->concepto) == trim($clie))
                        <tr>
                            <td>{{\Carbon\Carbon::create($dato->fecha)->format('d-m-Y')}}</td>
                            <td>{{$dato->factura}}</td>
                            <td>{{$dato->tipo.$dato->folio}}</td>
                            <td>{{$dato->uuid}}</td>
                            <td style="text-align: right">{{'$'.number_format($dato->abono,2)}}</td>
                            <td style="text-align: right">{{'$'.number_format($dato->cargo,2)}}</td>
                        </tr>
                            <?php
                            $total_c+=$dato->abono;
                            $total_a+=$dato->cargo;
                            $gran_total_c+=$dato->abono;
                            $gran_total_a+=$dato->cargo;
                            ?>
                    @endif
                @endforeach
                <tfoot>
                <tr>
                    <td colspan="4">Total :</td>
                    <td style="text-align: right">{{'$'.number_format($total_c,2)}}</td>
                    <td style="text-align: right">{{'$'.number_format($total_a,2)}}</td>
                </tr>
                </tfoot>
            </table>
        @endforeach
        <table class="table table-bordered table-striped table-primary table-sm">
            <tr>
                <th colspan="4" style="font-weight: bold">Importe Total :</th>
                <th style="text-align: right">{{'$'.number_format($gran_total_c,2)}}</th>
                <th style="text-align: right">{{'$'.number_format($gran_total_a,2)}}</th>
            </tr>
        </table>
    </div>
</div>
</body>
</html>

