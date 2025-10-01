<!doctype html>
<html lang="es">
<head>
    <?php
        use \Illuminate\Support\Facades\DB;
        use Carbon\Carbon;
        $empresa = DB::table('teams')->where('id', $team)->first();
        // Traer todos los clientes del team
        $clientes = DB::table('clientes')
            ->where('team_id', $team)
            ->orderBy('nombre')
            ->get();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $empresa?->name }} - Estado de Cuenta de Clientes</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        .page-break { page-break-after: always; }
        .no-page-break { page-break-after: avoid; }
        .section-title { font-size: 1.25rem; font-weight: 700; }
    </style>
</head>
<body>
<div class="container">
    <div class="row align-items-center mt-2">
        <div class="col-4"></div>
        <div class="col-4 text-center">
            <h1>Estado de Cuenta de Clientes</h1>
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

    <?php
        $primero = true;
    ?>
    @foreach($clientes as $cliente)
        <?php
            $query = DB::table('cuentas_cobrars')
                ->where('team_id', $team)
                ->where('cliente', $cliente->id);
            if (!empty($fecha_inicio)) {
                $query->whereDate('fecha', '>=', $fecha_inicio);
            }
            if (!empty($fecha_fin)) {
                $query->whereDate('fecha', '<=', $fecha_fin);
            }
            $movimientos = $query->orderBy('fecha')->get();
            // Omitir clientes sin movimientos en el rango
            if ($movimientos->count() === 0) {
                continue;
            }
            $total_importe = 0;
            $total_saldo   = 0;
        ?>
        <div class="row mb-3 {{ $primero ? 'no-page-break' : 'page-break' }}">
            <div class="col-12">
                <div class="section-title">Cliente: {{ $cliente?->clave }} - {{ $cliente?->nombre }}</div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <table class="table table-bordered table-sm" style="width: 100%">
                    <thead>
                    <tr>
                        <th style="font-weight: bold;">Fecha</th>
                        <th style="font-weight: bold;">Documento</th>
                        <th style="font-weight: bold;">Concepto</th>
                        <th style="font-weight: bold;">Descripción</th>
                        <th style="font-weight: bold; text-align: right;">Importe</th>
                        <th style="font-weight: bold; text-align: right;">Saldo</th>
                        <th style="font-weight: bold;">Vencimiento</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($movimientos as $mov)
                        <?php
                            $total_importe += floatval($mov->importe);
                            $total_saldo   += floatval($mov->saldo);
                        ?>
                        <tr>
                            <td>{{ Carbon::parse($mov->fecha)->format('d-m-Y') }}</td>
                            <td>{{ $mov->documento }}</td>
                            <td>{{ $mov->concepto }}</td>
                            <td>{{ $mov->descripcion }}</td>
                            <td style="text-align: right;">{{ '$ '.number_format($mov->importe,2) }}</td>
                            <td style="text-align: right;">{{ '$ '.number_format($mov->saldo,2) }}</td>
                            <td>{{ $mov->vencimiento ? Carbon::parse($mov->vencimiento)->format('d-m-Y') : '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold;">Totales:</td>
                        <td style="text-align: right; font-weight: bold;">{{ '$ '.number_format($total_importe,2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ '$ '.number_format($total_saldo,2) }}</td>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php $primero = false; ?>
    @endforeach

</div>
</body>
</html>
