<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Utilidad Bruta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <center>
                    <h1>Reporte de Utilidad Bruta</h1>
                    <h3>{{ $empresa ?? '' }}</h3>
                    <h5>
                        Periodo:
                        {{ !empty($periodo['inicio']) ? \Carbon\Carbon::parse($periodo['inicio'])->format('d-m-Y') : 'Libre' }}
                        a
                        {{ !empty($periodo['fin']) ? \Carbon\Carbon::parse($periodo['fin'])->format('d-m-Y') : 'Libre' }}
                    </h5>
                </center>
            </div>
        </div>

        <hr>

        <div class="row mb-3">
            <div class="col-12">
                <table class="table table-bordered" style="font-size: 11px !important;">
                    <tbody>
                        <tr>
                            <th style="width: 30%">Ventas (MXN, Subtotal)</th>
                            <td>{{ '$' . number_format($totales['ventas_mxn'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Costo (MXN, Subtotal)</th>
                            <td>{{ '$' . number_format($totales['costos_mxn'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Utilidad Bruta (MXN)</th>
                            <td style="font-weight: bold">{{ '$' . number_format($totales['utilidad_mxn'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Margen Bruto</th>
                            <td>{{ number_format((float)($totales['margen'] ?? 0) * 100, 2) . '%' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h5>Ventas (Facturas Timbradas)</h5>
                <table class="table table-bordered table-striped" style="font-size: 9px !important;">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Moneda</th>
                            <th>T.Cambio</th>
                            <th>Subtotal</th>
                            <th>Subtotal MXN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($ventas ?? []) as $row)
                            <tr>
                                <td>{{ $row['documento'] ?? '-' }}</td>
                                <td>{{ !empty($row['fecha']) ? \Carbon\Carbon::parse($row['fecha'])->format('d-m-Y') : '-' }}</td>
                                <td>{{ $row['tercero'] ?? '' }}</td>
                                <td>{{ $row['moneda'] ?? 'MXN' }}</td>
                                <td>{{ '$' . number_format((float)($row['tcambio'] ?? 1), 4) }}</td>
                                <td>{{ '$' . number_format((float)($row['subtotal'] ?? 0), 2) }}</td>
                                <td>{{ '$' . number_format((float)($row['subtotal_mxn'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Sin ventas en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h5>Costo (Compras recibidas desde Ordenes de Compra)</h5>
                <table class="table table-bordered table-striped" style="font-size: 9px !important;">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Moneda</th>
                            <th>T.Cambio</th>
                            <th>Subtotal</th>
                            <th>Subtotal MXN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($costos ?? []) as $row)
                            <tr>
                                <td>{{ $row['documento'] ?? '-' }}</td>
                                <td>{{ !empty($row['fecha']) ? \Carbon\Carbon::parse($row['fecha'])->format('d-m-Y') : '-' }}</td>
                                <td>{{ $row['tercero'] ?? '' }}</td>
                                <td>{{ $row['moneda'] ?? 'MXN' }}</td>
                                <td>{{ '$' . number_format((float)($row['tcambio'] ?? 1), 4) }}</td>
                                <td>{{ '$' . number_format((float)($row['subtotal'] ?? 0), 2) }}</td>
                                <td>{{ '$' . number_format((float)($row['subtotal_mxn'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Sin compras/entradas en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
