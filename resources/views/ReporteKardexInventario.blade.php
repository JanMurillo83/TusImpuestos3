<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte Kardex Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <div class="row mt-2">
        <div class="col-12 text-center">
            <h2>Reporte Kardex Inventario</h2>
            <h4>{{ $empresa }}</h4>
            <p>Fecha: {{ $fecha }}</p>
        </div>
    </div>
    <hr>
    @php
        $grupos = $kardex['grupos'] ?? [];
        $totales = $kardex['totales'] ?? ['cant' => 0, 'costo' => 0, 'precio' => 0];
    @endphp

    @forelse($grupos as $grupo)
        @php
            $producto = trim(($grupo['producto_clave'] ?? '') . ' - ' . ($grupo['producto_descripcion'] ?? ''));
            $movimientos = $grupo['movimientos'] ?? [];
            $sub = $grupo['totales'] ?? ['cant' => 0, 'costo' => 0, 'precio' => 0];
        @endphp
        <div class="row mt-3">
            <div class="col-12">
                <h5>{{ $producto }}</h5>
                <table class="table table-bordered table-sm" style="font-size: 9px;">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th style="text-align:right;">Cantidad</th>
                            <th style="text-align:right;">Costo</th>
                            <th style="text-align:right;">Precio</th>
                            <th style="text-align:right;">Total Costo</th>
                            <th style="text-align:right;">Total Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimientos as $mov)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($mov['fecha'])->format('d-m-Y') }}</td>
                                <td>{{ $mov['tipo'] }}</td>
                                <td>{{ $mov['concepto'] }}</td>
                                <td style="text-align:right;">{{ number_format($mov['cant'], 2) }}</td>
                                <td style="text-align:right;">{{ number_format($mov['costo'], 2) }}</td>
                                <td style="text-align:right;">{{ number_format($mov['precio'], 2) }}</td>
                                <td style="text-align:right;">{{ number_format($mov['importe_costo'], 2) }}</td>
                                <td style="text-align:right;">{{ number_format($mov['importe_precio'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold;">
                            <td colspan="3">Totales</td>
                            <td style="text-align:right;">{{ number_format($sub['cant'], 2) }}</td>
                            <td></td>
                            <td></td>
                            <td style="text-align:right;">{{ number_format($sub['costo'], 2) }}</td>
                            <td style="text-align:right;">{{ number_format($sub['precio'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @empty
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">No hay movimientos para los filtros seleccionados.</div>
            </div>
        </div>
    @endforelse

    <div class="row mt-3">
        <div class="col-12">
            <div class="alert alert-secondary" style="font-size: 10px;">
                <strong>Gran Total Cantidad:</strong> {{ number_format($totales['cant'], 2) }}<br>
                <strong>Gran Total Costo:</strong> {{ number_format($totales['costo'], 2) }}<br>
                <strong>Gran Total Precio:</strong> {{ number_format($totales['precio'], 2) }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
