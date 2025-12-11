<!DOCTYPE html>
<html lang="es">
<head>
    <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.19.0/cdn/components/qr-code/qr-code.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=0.8" charset="UTF-8" />
    <title>Facturación - TusImpuestos</title>
    <?php
    use App\Http\Controllers\AdminReportes;
    use Illuminate\Support\Facades\DB;
    $empresa = DB::table('datos_fiscales')->where('team_id',$team_id)->first();
    $datos = app(AdminReportes::class)->reporte_facturacion($fecha_inicial,$fecha_final,$team_id);
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
                <div><h3>Facturación</h3></div>
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
    <?php
        $total_gral = 0;
        $total_timb = 0;
        $total_subtimb = 0;
        $total_ivatimb = 0;
        $total_rettimb = 0;
        $total_canc = 0;
        $total_pend = 0;
    ?>
        <table class="table table-primary table-sm table-striped">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Subtotal</th>
                    <th>IVA</th>
                    <th>Retenciones</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($datos as $dato)
                <tr>
                    <td>{{$dato->serie.$dato->folio}}</td>
                    <td>{{$dato->fecha}}</td>
                    <td>{{$dato->nombre}}</td>
                    <td>{{'$'.number_format($dato->subtotal,2)}}</td>
                    <td>{{'$'.number_format($dato->iva,2)}}</td>
                    <td>{{'$'.number_format(floatval($dato->retiva)+floatval($dato->retisr),2)}}</td>
                    <td>{{'$'.number_format($dato->total,2)}}</td>
                    <td>{{$dato->estado}}</td>
                </tr>
                <?php
                    switch ($dato->estado){
                        case 'Timbrada':
                            $total_timb+=$dato->total;
                            $total_subtimb+= $dato->subtotal;
                            $total_ivatimb+= $dato->iva;
                            $total_rettimb+= floatval($dato->retiva)+floatval($dato->retisr);
                        break;
                        case 'Cancelada':
                            $total_canc+=$dato->total;
                        break;
                        case 'Activa':
                            $total_pend+=$dato->total;
                        break;
                    }
                    $total_gral+=$dato->total;
                ?>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Totales:</td>
                    <td>{{'$'.number_format($total_subtimb,2)}}</td>
                    <td>{{'$'.number_format($total_ivatimb,2)}}</td>
                    <td>{{'$'.number_format($total_rettimb,2)}}</td>
                    <td>{{'$'.number_format($total_timb,2)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="6">Total Cancelado:</td>
                    <td>{{'$'.number_format($total_canc,2)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="6">Pendiente de Timbrar:</td>
                    <td>{{'$'.number_format($total_pend,2)}}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
  </div>
</div>
</body>
</html>
