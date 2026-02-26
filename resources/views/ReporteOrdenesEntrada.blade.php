<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Ordenes - Entrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <?php
        use App\Models\Compras;
        use App\Models\Ordenes;
        use App\Models\Proveedores;
        use App\Models\Team;
        use Carbon\Carbon;
        use Illuminate\Support\Facades\DB;

        $empresa = Team::where('id',$team)->first();
        $query = Ordenes::where('team_id',$team);

        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $query->whereBetween(DB::raw('DATE(fecha)'),[$fecha_inicio,$fecha_fin]);
        }
        if (!empty($proveedor_id)) {
            $query->where('prov',$proveedor_id);
        }

        $ordenes = $query->orderBy('fecha', 'desc')->get();

        $total_subtotal = 0;
        $total_iva = 0;
        $total_total = 0;
        $pendientes = 0;
        $parciales = 0;
        $con_entrada = 0;
        $canceladas = 0;
    ?>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <center>
                    <h1>Reporte de Ordenes de Compra - Entrada</h1>
                    <h3>{{$empresa->name ?? ''}}</h3>
                    @if(!empty($fecha_inicio) && !empty($fecha_fin))
                        <h5>Periodo: {{Carbon::create($fecha_inicio)->format('d-m-Y')}} a {{Carbon::create($fecha_fin)->format('d-m-Y')}}</h5>
                    @else
                        <h5>Periodo: Libre</h5>
                    @endif
                    @if(!empty($proveedor_id))
                        <?php $prov = Proveedores::find($proveedor_id); ?>
                        <h6>Proveedor: {{$prov?->nombre ?? 'N/A'}}</h6>
                    @else
                        <h6>Proveedor: General</h6>
                    @endif
                </center>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <table class="table table-bordered table-striped" style="font-size: 9px !important;">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Moneda</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Entrada</th>
                            <th>Documento Entrada</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($ordenes as $orden)
                        <?php
                            $total_subtotal += floatval($orden->subtotal);
                            $total_iva += floatval($orden->iva);
                            $total_total += floatval($orden->total);

                            $provRow = Proveedores::where('id',$orden->prov)->first();
                            $nombreProv = $orden->nombre ?? $provRow?->nombre ?? '';

                            $compras = Compras::where('orden_id', $orden->id)->orderBy('fecha')->get();
                            $docs = $compras->map(function($c){
                                if (!empty($c->docto)) return $c->docto;
                                $serie = $c->serie ?? '';
                                $folio = $c->folio ?? '';
                                $doc = trim($serie.$folio);
                                return $doc !== '' ? $doc : null;
                            })->filter()->values()->implode(', ');

                            $estadoEntrada = 'Pendiente';
                            if ($orden->estado === 'Cancelada') {
                                $estadoEntrada = 'Cancelada';
                            } elseif ($compras->count() > 0) {
                                if ($orden->estado === 'Parcial') {
                                    $estadoEntrada = 'Parcial';
                                } else {
                                    $estadoEntrada = 'Con Entrada';
                                }
                            }

                            if ($estadoEntrada === 'Pendiente') $pendientes++;
                            elseif ($estadoEntrada === 'Parcial') $parciales++;
                            elseif ($estadoEntrada === 'Con Entrada') $con_entrada++;
                            else $canceladas++;

                            $serie = $orden->serie ?? '';
                            $folio = $orden->folio ?? '';
                            $docto = $orden->docto ?? trim($serie.$folio);
                        ?>
                        <tr>
                            <td>{{$docto}}</td>
                            <td>{{Carbon::create($orden->fecha)->format('d-m-Y')}}</td>
                            <td>{{$nombreProv}}</td>
                            <td>{{$orden->moneda ?? 'MXN'}}</td>
                            <td>{{'$'.number_format($orden->total,2)}}</td>
                            <td>{{$orden->estado}}</td>
                            <td style="font-weight: bold; color: {{ $estadoEntrada === 'Pendiente' ? 'red' : ($estadoEntrada === 'Parcial' ? 'orange' : ($estadoEntrada === 'Cancelada' ? 'gray' : 'green')) }}">
                                {{$estadoEntrada}}
                            </td>
                            <td>{{$docs !== '' ? $docs : '-'}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold;">
                            <td colspan="4">TOTALES :</td>
                            <td>{{'$'.number_format($total_total,2)}}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info" style="font-size: 10px;">
                    <strong>Total de Ordenes:</strong> {{$ordenes->count()}}<br>
                    <strong>Pendientes de Entrada:</strong> {{$pendientes}}<br>
                    <strong>Parciales:</strong> {{$parciales}}<br>
                    <strong>Con Entrada:</strong> {{$con_entrada}}<br>
                    <strong>Canceladas:</strong> {{$canceladas}}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
