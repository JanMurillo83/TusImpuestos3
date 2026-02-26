<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Requisiciones - Ordenes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <?php
        use App\Models\Ordenes;
        use App\Models\Proveedores;
        use App\Models\Requisiciones;
        use App\Models\Team;
        use Carbon\Carbon;
        use Illuminate\Support\Facades\DB;

        $empresa = Team::where('id',$team)->first();
        $query = Requisiciones::where('team_id',$team);

        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $query->whereBetween(DB::raw('DATE(fecha)'),[$fecha_inicio,$fecha_fin]);
        }
        if (!empty($proveedor_id)) {
            $query->where('prov',$proveedor_id);
        }

        $requisiciones = $query->orderBy('fecha', 'desc')->get();

        $total_total = 0;
        $pendientes = 0;
        $parciales = 0;
        $con_orden = 0;
        $canceladas = 0;
    ?>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <center>
                    <h1>Reporte de Requisiciones a Ordenes de Compra</h1>
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
                            <th>Requisicion</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Moneda</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Orden</th>
                            <th>Documento Orden</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($requisiciones as $req)
                        <?php
                            $total_total += floatval($req->total);

                            $provRow = Proveedores::where('id',$req->prov)->first();
                            $nombreProv = $req->nombre ?? $provRow?->nombre ?? '';

                            $ordenes = Ordenes::where('requisicion_id', $req->id)->orderBy('fecha')->get();
                            $docs = $ordenes->map(function($o){
                                if (!empty($o->docto)) return $o->docto;
                                $serie = $o->serie ?? '';
                                $folio = $o->folio ?? '';
                                $doc = trim($serie.$folio);
                                return $doc !== '' ? $doc : null;
                            })->filter()->values()->implode(', ');

                            $estadoOrden = 'Pendiente';
                            if ($req->estado === 'Cancelada') {
                                $estadoOrden = 'Cancelada';
                            } elseif ($ordenes->count() > 0) {
                                if ($req->estado === 'Parcial') {
                                    $estadoOrden = 'Parcial';
                                } else {
                                    $estadoOrden = 'Con Orden';
                                }
                            }

                            if ($estadoOrden === 'Pendiente') $pendientes++;
                            elseif ($estadoOrden === 'Parcial') $parciales++;
                            elseif ($estadoOrden === 'Con Orden') $con_orden++;
                            else $canceladas++;

                            $serie = $req->serie ?? '';
                            $folio = $req->folio ?? '';
                            $docto = $req->docto ?? trim($serie.$folio);
                        ?>
                        <tr>
                            <td>{{$docto}}</td>
                            <td>{{Carbon::create($req->fecha)->format('d-m-Y')}}</td>
                            <td>{{$nombreProv}}</td>
                            <td>{{$req->moneda ?? 'MXN'}}</td>
                            <td>{{'$'.number_format($req->total,2)}}</td>
                            <td>{{$req->estado}}</td>
                            <td style="font-weight: bold; color: {{ $estadoOrden === 'Pendiente' ? 'red' : ($estadoOrden === 'Parcial' ? 'orange' : ($estadoOrden === 'Cancelada' ? 'gray' : 'green')) }}">
                                {{$estadoOrden}}
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
                    <strong>Total de Requisiciones:</strong> {{$requisiciones->count()}}<br>
                    <strong>Pendientes de Orden:</strong> {{$pendientes}}<br>
                    <strong>Parciales:</strong> {{$parciales}}<br>
                    <strong>Con Orden:</strong> {{$con_orden}}<br>
                    <strong>Canceladas:</strong> {{$canceladas}}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
