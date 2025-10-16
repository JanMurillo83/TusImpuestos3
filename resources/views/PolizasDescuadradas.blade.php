<?php
use \Illuminate\Support\Facades\DB;
use \App\Models\CatPolizas;
$empresas = DB::table('teams')->where('id',$empresa)->first();
$fecha = \Carbon\Carbon::now();
$polizas = CatPolizas::where('team_id',$empresa)
    ->where('periodo',$periodo)
    ->where('ejercicio',$ejercicio)
    ->whereColumn('cargos','!=','abonos')
    ->orderBy('fecha')
    ->orderBy('folio')
    ->get();
$totalCargos = $polizas->sum('cargos');
$totalAbonos = $polizas->sum('abonos');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pólizas Descuadradas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <div class="row mt-5">
        <div class="col-3">
            <img src="{{asset('images/MainLogo.png')}}" alt="Tus-Impuestos" width="120px">
        </div>
        <div class="col-6">
            <center>
                <h5>{{ $empresas?->name }}</h5>
                <div>
                    Pólizas Descuadradas - Periodo {{ $periodo }} / Ejercicio {{ $ejercicio }}
                </div>
            </center>
        </div>
        <div class="col-3">
            Fecha de Emisión: <?php echo $fecha->toDateString('d-m-Y'); ?>
        </div>
    </div>
    <hr>
    <div class="row mt-2">
        <div class="col-12">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="font-weight: bold">Tipo</th>
                        <th style="font-weight: bold">Folio</th>
                        <th style="font-weight: bold">Fecha</th>
                        <th style="font-weight: bold">Concepto</th>
                        <th style="font-weight: bold; text-align: end">Cargos</th>
                        <th style="font-weight: bold; text-align: end">Abonos</th>
                        <th style="font-weight: bold; text-align: end">Diferencia</th>
                        <th style="font-weight: bold">Referencia</th>
                        <th style="font-weight: bold">UUID</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($polizas as $p)
                    <tr>
                        <td>{{ $p->tipo }}</td>
                        <td>{{ $p->folio }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->fecha)->format('Y-m-d') }}</td>
                        <td>{{ $p->concepto }}</td>
                        <td style="text-align: end">{{ '$'.number_format($p->cargos,2) }}</td>
                        <td style="text-align: end">{{ '$'.number_format($p->abonos,2) }}</td>
                        <td style="text-align: end">{{ '$'.number_format(($p->cargos - $p->abonos),2) }}</td>
                        <td>{{ $p->referencia }}</td>
                        <td style="font-size: 10px">{{ $p->uuid }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No se encontraron pólizas descuadradas para el periodo seleccionado.</td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" style="text-align:right">Totales:</th>
                        <th style="text-align: end">{{ '$'.number_format($totalCargos,2) }}</th>
                        <th style="text-align: end">{{ '$'.number_format($totalAbonos,2) }}</th>
                        <th style="text-align: end">{{ '$'.number_format(($totalCargos - $totalAbonos),2) }}</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>
