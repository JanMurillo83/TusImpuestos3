<!doctype html>
<html lang="es">
<head>
    <?php
        use \Illuminate\Support\Facades\DB;
        use Carbon\Carbon;
        $empresa = DB::table('teams')->where('id', $team)->first();
        $q = DB::table('compras')->where('team_id', $team);
        if (!empty($fecha_inicio)) {
            $q->whereDate('fecha', '>=', $fecha_inicio);
        }
        if (!empty($fecha_fin)) {
            $q->whereDate('fecha', '<=', $fecha_fin);
        }
        $q->orderBy('fecha');
        $compras = $q->get();
        $total_subtotal = 0; $total_iva = 0; $total_total = 0;
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $empresa?->name }} - Reporte de Compras</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row align-items-center mt-2">
        <div class="col-4"></div>
        <div class="col-4 text-center">
            <h1>Reporte de Compras</h1>
        </div>
        <div class="col-4">
            <div>
                <label style="font-weight: bold; width: 6rem">Empresa:</label>
                <span>{{ $empresa?->name }}</span>
            </div>
            <div>
                <label style="font-weight: bold; width: 6rem">Fecha:</label>
                <span>{{ Carbon::now()->format('d-m-Y') }}</span>
            </div>
            <div>
                <label style="font-weight: bold; width: 6rem">Rango:</label>
                <span>
                    {{ !empty($fecha_inicio) ? Carbon::parse($fecha_inicio)->format('d-m-Y') : 'Inicio' }}
                    a
                    {{ !empty($fecha_fin) ? Carbon::parse($fecha_fin)->format('d-m-Y') : 'Hoy' }}
                </span>
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-12">
            <table class="table table-bordered table-sm" style="width: 100%">
                <thead>
                <tr>
                    <th style="font-weight: bold;">Fecha</th>
                    <th style="font-weight: bold;">Folio</th>
                    <th style="font-weight: bold;">Proveedor</th>
                    <th style="font-weight: bold; text-align: right;">Subtotal</th>
                    <th style="font-weight: bold; text-align: right;">IVA</th>
                    <th style="font-weight: bold; text-align: right;">Total</th>
                    <th style="font-weight: bold;">Moneda</th>
                    <th style="font-weight: bold;">Estado</th>
                </tr>
                </thead>
                <tbody>
                @foreach($compras as $comp)
                    <?php
                        $total_subtotal += floatval($comp->subtotal);
                        $total_iva      += floatval($comp->iva);
                        $total_total    += floatval($comp->total);
                        $prov = DB::table('proveedores')->where('id', $comp->prov)->first();
                    ?>
                    <tr>
                        <td>{{ Carbon::parse($comp->fecha)->format('d-m-Y') }}</td>
                        <td>{{ $comp->folio }}</td>
                        <td>{{ $prov?->clave }} - {{ $comp->nombre ?? $prov?->nombre }}</td>
                        <td style="text-align: right;">{{ '$ '.number_format($comp->subtotal,2) }}</td>
                        <td style="text-align: right;">{{ '$ '.number_format($comp->iva,2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ '$ '.number_format($comp->total,2) }}</td>
                        <td>{{ $comp->moneda ?? 'MXN' }}</td>
                        <td>{{ $comp->estado }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Totales:</td>
                    <td style="text-align: right; font-weight: bold;">{{ '$ '.number_format($total_subtotal,2) }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ '$ '.number_format($total_iva,2) }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ '$ '.number_format($total_total,2) }}</td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>
