<?php
use \Illuminate\Support\Facades\DB;
use \Carbon\Carbon;

$empresaRow = DB::table('teams')->where('id',$empresa)->first();
$fecha = Carbon::now();

// Normalizar rango de cuentas recibido (opcional)
$cuentaIni = $cuenta_ini ?? null;
$cuentaFin = $cuenta_fin ?? null;
if ($cuentaIni && $cuentaFin && $cuentaIni > $cuentaFin) { [$cuentaIni, $cuentaFin] = [$cuentaFin, $cuentaIni]; }

// Calcular saldos iniciales por cuenta: todo lo anterior al periodo/ejercicio actual
$prevSaldosQuery = DB::table('auxiliares as a')
    ->join('cat_polizas as p','p.id','=','a.cat_polizas_id')
    ->join('cat_cuentas as c', function($j){
        $j->on('c.codigo','=','a.codigo')->on('c.team_id','=','a.team_id');
    })
    ->select('a.codigo','a.cuenta', DB::raw("SUM(CASE WHEN c.naturaleza = 'A' THEN (a.abono - a.cargo) ELSE (a.cargo - a.abono) END) as saldo"))
    ->where('a.team_id',$empresa)
    ->where('p.team_id',$empresa)
    ->where('c.team_id',$empresa)
    ->when($cuentaIni, function($q) use ($cuentaIni){ $q->where('a.codigo','>=',$cuentaIni); })
    ->when($cuentaFin, function($q) use ($cuentaFin){ $q->where('a.codigo','<=',$cuentaFin); })
    ->where(function($q) use ($periodo,$ejercicio,$mes_ini,$mes_fin){
        $q->where('p.ejercicio','<',$ejercicio)
          ->orWhere(function($q2) use ($periodo,$ejercicio,$mes_ini,$mes_fin){
              $q2->where('p.ejercicio',$ejercicio)
                 ->where('p.periodo','<',$mes_ini);
          });
    })
    ->groupBy('a.codigo','a.cuenta')
    ->get();
$saldoInicial = [];
foreach ($prevSaldosQuery as $s) {
    $saldoInicial[$s->codigo.'|'.$s->cuenta] = (float)$s->saldo;
}

// Obtener los auxiliares del periodo y ejercicio para la empresa, unidos con pólizas
$movimientos = DB::table('auxiliares as a')
    ->join('cat_polizas as p','p.id','=','a.cat_polizas_id')
    ->join('cat_cuentas as c', function($j){
        $j->on('c.codigo','=','a.codigo')->on('c.team_id','=','a.team_id');
    })
    ->select(
        'a.codigo','a.cuenta','a.concepto','a.cargo','a.abono','a.factura','a.uuid','a.nopartida',
        'p.fecha','p.tipo','p.folio','p.concepto as poliza_concepto','p.referencia',
        'c.naturaleza'
    )
    ->where('a.team_id',$empresa)
    ->where('p.team_id',$empresa)
    ->where('c.team_id',$empresa)
    ->whereBetween('p.periodo',[$mes_ini,$mes_fin])
    ->where('p.ejercicio',$ejercicio)
    ->when($cuentaIni, function($q) use ($cuentaIni){ $q->where('a.codigo','>=',$cuentaIni); })
    ->when($cuentaFin, function($q) use ($cuentaFin){ $q->where('a.codigo','<=',$cuentaFin); })
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
            'naturaleza' => ($m->naturaleza ?? 'D'), // D=Deudora suma cargo-resta abono; A=Acreedora al revés
            'items' => [],
            'total_cargos' => 0.0,
            'total_abonos' => 0.0,
            'saldo_inicial' => $saldoInicial[$key] ?? 0.0,
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
    <meta name="viewport" content="width=device-width, initial-scale=1" charset="utf-8">
    <title>Auxiliares del Periodo</title>
    <script src="{{public_path('WH/jquery-3.7.1.js')}}"></script>
    <link href="{{public_path('WH/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{public_path('WH/bootstrap-theme.min.css')}}" rel="stylesheet">
    <script src="{{public_path('WH/bootstrap.min.js')}}"></script>
    <style>
        th, td { font-size: 10px; }
        .cuenta-header { background: #f5f5f5; font-weight: bold; }
        .totales { font-weight: bold; }
        /* Expand table to full width and control column spacing */
        table { width: 100%; }
        .aux-table { table-layout: fixed; border-collapse: separate; border-spacing: 0; }
        .aux-table th, .aux-table td { padding: 4px 6px; vertical-align: top; }
        /* Allow long text to wrap nicely */
        .wrap { white-space: normal; word-break: break-word; }
        /* Keep compact columns from wrapping */
        .nowrap { white-space: nowrap; }
        .num { text-align: end; white-space: nowrap; }
    </style>
</head>
<body>
<div class="container">
    <div class="row mt-5">
        <div class="col-3">
            <img src="{{public_path('images/MainLogo.png')}}" alt="Tus-Impuestos" width="120px">
        </div>
        <div class="col-6">
            <center>
                <h5>{{ $empresaRow->name ?? 'Empresa' }}</h5>
                <div>
                    Auxiliares del Periodo {{ $mes_ini }} a {{$mes_fin}} / {{ $ejercicio }}
                </div>
                <?php if($cuentaIni || $cuentaFin): ?>
                    <div>
                        Rango de cuentas: {{ $cuentaIni ?? 'Todas' }} — {{ $cuentaFin ?? 'Todas' }}
                    </div>
                <?php endif; ?>
            </center>
        </div>
        <div class="col-3">
            Fecha de Emisión: <?php echo $fecha->toDateString('d-m-Y'); ?>
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
                <table class="table table-sm border aux-table">
                    <colgroup>
                        <col style="width:8%" />
                        <col style="width:6%" />
                        <col style="width:8%" />
                        <col style="width:24%" />
                        <col style="width:18%" />
                        <col style="width:14%" />
                        <col style="width:8%" />
                        <col style="width:8%" />
                        <col style="width:6%" />
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="nowrap">Fecha</th>
                        <th class="nowrap">Tipo</th>
                        <th class="nowrap">Folio</th>
                        <th class="wrap">Concepto</th>
                        <th class="wrap">Factura/Ref</th>
                        <th class="wrap">UUID</th>
                        <th class="num">Cargo</th>
                        <th class="num">Abono</th>
                        <th class="num">Saldo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $saldo = (float)($group['saldo_inicial'] ?? 0); ?>
                    <tr>
                        <td colspan="8" class="text-end"><em>Saldo inicial</em></td>
                        <td style="text-align: end;">{{ '$'.number_format($saldo,2) }}</td>
                    </tr>
                    <?php foreach ($group['items'] as $it): ?>
                        <?php $delta = ((float)$it->cargo - (float)$it->abono); if (($group['naturaleza'] ?? 'D') === 'A') { $delta = -$delta; } $saldo += $delta; ?>
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($it->fecha)->format('d-m-Y') }}</td>
                            <td>{{ $it->tipo }}</td>
                            <td>{{ $it->folio }}</td>
                            <td>{{ $it->concepto ?: $it->poliza_concepto }}</td>
                            <td>{{ $it->factura ?: $it->referencia }}</td>
                            <td>{{ $it->uuid }}</td>
                            <td style="text-align: end;">{{ '$'.number_format($it->cargo,2) }}</td>
                            <td style="text-align: end;">{{ '$'.number_format($it->abono,2) }}</td>
                            <td style="text-align: end;">{{ '$'.number_format($saldo,2) }}</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="totales">
                        <td colspan="6" class="text-end">Totales de la cuenta:</td>
                        <td style="text-align: end;">{{ '$'.number_format($group['total_cargos'],2) }}</td>
                        <td style="text-align: end;">{{ '$'.number_format($group['total_abonos'],2) }}</td>
                        <td style="text-align: end;">{{ '$'.number_format(($group['saldo_inicial'] ?? 0) + ((($group['naturaleza'] ?? 'D') === 'A') ? ($group['total_abonos'] - $group['total_cargos']) : ($group['total_cargos'] - $group['total_abonos'])),2) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
