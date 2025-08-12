<?php
use \Illuminate\Support\Facades\DB;
use \Carbon\Carbon;

$empresaRow = DB::table('teams')->where('id',$empresa)->first();
$fecha = Carbon::now();

// Obtener los auxiliares del periodo y ejercicio para la empresa, unidos con pólizas
$movimientos = DB::table('auxiliares as a')
    ->join('cat_polizas as p','p.id','=','a.cat_polizas_id')
    ->select(
        'a.codigo','a.cuenta','a.concepto','a.cargo','a.abono','a.factura','a.uuid','a.nopartida',
        'p.fecha','p.tipo','p.folio','p.concepto as poliza_concepto','p.referencia'
    )
    ->where('a.team_id',$empresa)
    ->where('p.team_id',$empresa)
    ->where('p.periodo',$periodo)
    ->where('p.ejercicio',$ejercicio)
    ->orderBy('a.codigo')
    ->orderBy('p.fecha')
    ->orderBy('p.tipo')
    ->orderBy('p.folio')
    ->orderBy('a.nopartida')
    ->get();

// Agrupar por cuenta contable (codigo + cuenta)
$agrupado = [];
foreach ($movimientos as $m) {
    $key = $m->codigo.'|'.$m->cuenta;
    if (!isset($agrupado[$key])) {
        $agrupado[$key] = [
            'codigo' => $m->codigo,
            'cuenta' => $m->cuenta,
            'items' => [],
            'total_cargos' => 0.0,
            'total_abonos' => 0.0,
        ];
    }
    $agrupado[$key]['items'][] = $m;
    $agrupado[$key]['total_cargos'] += (float)$m->cargo;
    $agrupado[$key]['total_abonos'] += (float)$m->abono;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auxiliares del Periodo</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        th, td { font-size: 12px; }
        .cuenta-header { background: #f5f5f5; font-weight: bold; }
        .totales { font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="row mt-5">
        <div class="col-3">
            <img src="{{asset('images/MainLogo.png')}}" alt="Tus-Impuestos" width="120px">
        </div>
        <div class="col-6">
            <center>
                <h5>{{ $empresaRow->name ?? 'Empresa' }}</h5>
                <div>
                    Auxiliares del Periodo {{ $periodo }} / {{ $ejercicio }}
                </div>
            </center>
        </div>
        <div class="col-3">
            Fecha de Emision: <?php echo $fecha->toDateString('d-m-Y'); ?>
        </div>
    </div>
    <hr>

    <?php if (empty($agrupado)) : ?>
        <div class="alert alert-info">No hay movimientos de auxiliares para el periodo seleccionado.</div>
    <?php endif; ?>

    <?php foreach ($agrupado as $group): ?>
        <div class="row mt-3">
            <div class="col-12">
                <div class="cuenta-header p-2">
                    <?php echo e($group['codigo']); ?> - <?php echo e($group['cuenta']); ?>
                </div>
                <table class="table table-sm border">
                    <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Folio</th>
                        <th>Concepto</th>
                        <th>Factura/Ref</th>
                        <th>UUID</th>
                        <th style="text-align: end;">Cargo</th>
                        <th style="text-align: end;">Abono</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($group['items'] as $it): ?>
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($it->fecha)->format('d-m-Y') }}</td>
                            <td>{{ $it->tipo }}</td>
                            <td>{{ $it->folio }}</td>
                            <td>{{ $it->concepto ?: $it->poliza_concepto }}</td>
                            <td>{{ $it->factura ?: $it->referencia }}</td>
                            <td>{{ $it->uuid }}</td>
                            <td style="text-align: end;">{{ '$'.number_format($it->cargo,2) }}</td>
                            <td style="text-align: end;">{{ '$'.number_format($it->abono,2) }}</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="totales">
                        <td colspan="6" class="text-end">Totales de la cuenta:</td>
                        <td style="text-align: end;">{{ '$'.number_format($group['total_cargos'],2) }}</td>
                        <td style="text-align: end;">{{ '$'.number_format($group['total_abonos'],2) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
