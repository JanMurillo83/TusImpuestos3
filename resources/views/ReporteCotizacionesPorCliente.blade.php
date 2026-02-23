<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Cotizaciones por Cliente</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <?php
        use App\Models\Cotizaciones;
        use App\Models\Facturas;
        use App\Models\Team;
        use Illuminate\Support\Facades\DB;
        use Carbon\Carbon;

        $empresa = Team::where('id',$team)->first();
        $query = Cotizaciones::where('team_id',$team);

        if(isset($fecha_inicio) && $fecha_inicio != null && isset($fecha_fin) && $fecha_fin != null) {
            $query->whereBetween(DB::raw('DATE(fecha)'),[$fecha_inicio,$fecha_fin]);
        }

        if(isset($cliente_id) && $cliente_id != null) {
            $query->where('clie',$cliente_id);
        }

        $cotizaciones = $query->orderBy('fecha', 'desc')->get();

        if(isset($estado) && $estado != 'todas') {
            if($estado == 'facturadas') {
                $cotizaciones = $cotizaciones->filter(function($cotizacion) {
                    return Facturas::where('cotizacion_id', $cotizacion->id)->exists();
                });
            } elseif($estado == 'no_facturadas') {
                $cotizaciones = $cotizaciones->filter(function($cotizacion) {
                    return !Facturas::where('cotizacion_id', $cotizacion->id)->exists();
                });
            }
        }

        $grouped = $cotizaciones->groupBy(function($c) {
            return $c->nombre ?: 'Sin cliente';
        });
    ?>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <center>
                    <h1>Reporte de Cotizaciones por Cliente</h1>
                    <h3>{{$empresa->name}}</h3>
                    @if(isset($fecha_inicio) && $fecha_inicio != null && isset($fecha_fin) && $fecha_fin != null)
                        <h5>Periodo: {{Carbon::create($fecha_inicio)->format('d-m-Y')}} a {{Carbon::create($fecha_fin)->format('d-m-Y')}}</h5>
                    @else
                        <h5>Periodo: Libre</h5>
                    @endif
                    @if(isset($cliente_id) && $cliente_id != null)
                        <?php $nomclie = \App\Models\Clientes::find($cliente_id)->nombre ?? 'N/A'; ?>
                        <h6>Cliente: {{$nomclie}}</h6>
                    @else
                        <h6>Cliente: General</h6>
                    @endif
                    <h6>Estado:
                        @if(isset($estado))
                            @if($estado == 'todas') Todas
                            @elseif($estado == 'facturadas') Facturadas
                            @elseif($estado == 'no_facturadas') No Facturadas
                            @endif
                        @else
                            Todas
                        @endif
                    </h6>
                </center>
            </div>
        </div>
        <hr>
        <?php
            $gran_subtotal_usd = 0; $gran_ivatotal_usd = 0; $gran_tottotal_usd = 0;
            $gran_subtotal_mxn = 0; $gran_ivatotal_mxn = 0; $gran_tottotal_mxn = 0;
        ?>
        @foreach($grouped as $cliente => $items)
        <div class="row mb-2">
            <div class="col-12">
                <h5 style="background-color:#B4C6E7;padding:5px;margin-bottom:0;">{{ $cliente }} ({{ $items->count() }} cotizaciones)</h5>
                <table class="table table-bordered table-striped" style="font-size: 8px !important;">
                    <thead>
                        <tr>
                            <th>Cotización</th>
                            <th>Fecha</th>
                            <th>Vendedor</th>
                            <th>Moneda</th>
                            <th>T.Cambio</th>
                            <th>Subtotal USD</th>
                            <th>IVA USD</th>
                            <th>Total USD</th>
                            <th>Subtotal MXN</th>
                            <th>IVA MXN</th>
                            <th>Total MXN</th>
                            <th>Estado Facturación</th>
                            <th>Factura</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $subtotal_usd = 0; $ivatotal_usd = 0; $tottotal_usd = 0;
                        $subtotal_mxn = 0; $ivatotal_mxn = 0; $tottotal_mxn = 0;
                    ?>
                    @foreach($items as $cotizacion)
                        <?php
                            $tcambio = floatval($cotizacion->tcambio) != 0 ? $cotizacion->tcambio : 1;
                            if($cotizacion->moneda == 'USD'){
                                $sub_mxn = $cotizacion->subtotal * $cotizacion->tcambio;
                                $iva_mxn = $cotizacion->iva * $cotizacion->tcambio;
                                $tot_mxn = $cotizacion->total * $cotizacion->tcambio;
                                $sub_usd = $cotizacion->subtotal;
                                $iva_usd = $cotizacion->iva;
                                $tot_usd = $cotizacion->total;
                            } else {
                                $sub_mxn = $cotizacion->subtotal;
                                $iva_mxn = $cotizacion->iva;
                                $tot_mxn = $cotizacion->total;
                                $sub_usd = 0; $iva_usd = 0; $tot_usd = 0;
                            }
                            $subtotal_usd += $sub_usd; $ivatotal_usd += $iva_usd; $tottotal_usd += $tot_usd;
                            $subtotal_mxn += $sub_mxn; $ivatotal_mxn += $iva_mxn; $tottotal_mxn += $tot_mxn;

                            $factura = Facturas::where('cotizacion_id', $cotizacion->id)->first();
                            $estadoFacturacion = $factura ? 'Facturada' : 'No Facturada';
                            $doctoFactura = $factura ? $factura->docto : '-';
                        ?>
                        <tr>
                            <td>{{$cotizacion->docto}}</td>
                            <td>{{Carbon::create($cotizacion->fecha)->format('d-m-Y')}}</td>
                            <td>{{$cotizacion->nombre_elaboro ?: 'Sin vendedor'}}</td>
                            <td>{{$cotizacion->moneda ?? 'MXN'}}</td>
                            <td>{{'$'.number_format($tcambio,4)}}</td>
                            <td>{{'$'.number_format($sub_usd,2)}}</td>
                            <td>{{'$'.number_format($iva_usd,2)}}</td>
                            <td>{{'$'.number_format($tot_usd,2)}}</td>
                            <td>{{'$'.number_format($sub_mxn,2)}}</td>
                            <td>{{'$'.number_format($iva_mxn,2)}}</td>
                            <td>{{'$'.number_format($tot_mxn,2)}}</td>
                            <td style="font-weight: bold; color: {{ $factura ? 'green' : 'red' }}">{{$estadoFacturacion}}</td>
                            <td>{{$doctoFactura}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; font-size: 10px !important; background-color:#D9E2F3;">
                            <td colspan="5">Subtotal {{ $cliente }} ({{ $items->count() }}):</td>
                            <td>{{'$'.number_format($subtotal_usd,2)}}</td>
                            <td>{{'$'.number_format($ivatotal_usd,2)}}</td>
                            <td>{{'$'.number_format($tottotal_usd,2)}}</td>
                            <td>{{'$'.number_format($subtotal_mxn,2)}}</td>
                            <td>{{'$'.number_format($ivatotal_mxn,2)}}</td>
                            <td>{{'$'.number_format($tottotal_mxn,2)}}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php
            $gran_subtotal_usd += $subtotal_usd; $gran_ivatotal_usd += $ivatotal_usd; $gran_tottotal_usd += $tottotal_usd;
            $gran_subtotal_mxn += $subtotal_mxn; $gran_ivatotal_mxn += $ivatotal_mxn; $gran_tottotal_mxn += $tottotal_mxn;
        ?>
        @endforeach
        <div class="row">
            <div class="col-12">
                <table class="table table-bordered" style="font-size: 10px !important;">
                    <tr style="font-weight: bold; background-color:#2F5496; color:white;">
                        <td colspan="5">GRAN TOTAL ({{ $cotizaciones->count() }} cotizaciones):</td>
                        <td>{{'$'.number_format($gran_subtotal_usd,2)}}</td>
                        <td>{{'$'.number_format($gran_ivatotal_usd,2)}}</td>
                        <td>{{'$'.number_format($gran_tottotal_usd,2)}}</td>
                        <td>{{'$'.number_format($gran_subtotal_mxn,2)}}</td>
                        <td>{{'$'.number_format($gran_ivatotal_mxn,2)}}</td>
                        <td>{{'$'.number_format($gran_tottotal_mxn,2)}}</td>
                        <td colspan="2"></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info" style="font-size: 10px;">
                    <strong>Total de Cotizaciones:</strong> {{$cotizaciones->count()}}<br>
                    <strong>Cotizaciones Facturadas:</strong> {{$cotizaciones->filter(fn($c) => Facturas::where('cotizacion_id', $c->id)->exists())->count()}}<br>
                    <strong>Cotizaciones No Facturadas:</strong> {{$cotizaciones->filter(fn($c) => !Facturas::where('cotizacion_id', $c->id)->exists())->count()}}<br>
                    <strong>Clientes:</strong> {{$grouped->count()}}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
