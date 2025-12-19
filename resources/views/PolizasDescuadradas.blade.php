<?php
use \Illuminate\Support\Facades\DB;
use \App\Models\CatPolizas;
use \App\Models\Auxiliares;
use \App\Models\CatCuentas;
$empresas = DB::table('teams')->where('id',$empresa)->first();
$fecha = \Carbon\Carbon::now();
$polizas = CatPolizas::where('team_id',$empresa)
    ->where('periodo',$periodo)
    ->where('ejercicio',$ejercicio)
    ->whereColumn(DB::raw('TRUNCATE(cargos,2)'),'!=',DB::raw('TRUNCATE(abonos,2)'))
    ->orderBy('fecha')
    ->orderBy('folio')
    ->get();
$totalCargos = $polizas->sum('cargos');
$totalAbonos = $polizas->sum('abonos');
$auxiliar_es = Auxiliares::where('team_id',$empresa)
    ->where('a_periodo',$periodo)
    ->where('a_ejercicio',$ejercicio)->get();
$auxiliares = [];
foreach ($auxiliar_es as $auxili_ar)
{
    if(!CatPolizas::where('id',$auxili_ar->cat_polizas_id)->exists())
    {
        $auxiliares[]=
            [
                'Poliza'=>'No Existe',
                'Codigo'=>$auxili_ar->codigo,
                'Cargo'=>$auxili_ar->cargo,
                'Abono'=>$auxili_ar->abono,
                'Auxiliar'=>$auxili_ar->id,
                'Error'=>'Poliza No Existente'
            ];
    }
    $poli = CatPolizas::where('id',$auxili_ar->cat_polizas_id)->first();
    if(!CatCuentas::where('codigo',$auxili_ar->codigo)->where('team_id',$empresa)->exists())
    {
        $auxiliares[]=
            [
                'Poliza'=>$poli->tipo.$poli->folio,
                'Codigo'=>$auxili_ar->codigo,
                'Cargo'=>$auxili_ar->cargo,
                'Abono'=>$auxili_ar->abono,
                'Auxiliar'=>$auxili_ar->id,
                'Error'=>'Cuenta No Existente'
            ];
    }
    $cuenta = CatCuentas::where('codigo',$auxili_ar->codigo)->where('team_id',$empresa)->first();
    $cue_nta = $cuenta?->tipo ?? '';
    if($cue_nta == 'A')
    {
        $auxiliares[]=
            [
                'Poliza'=>$poli->tipo.$poli->folio,
                'Codigo'=>$auxili_ar->codigo,
                'Cargo'=>$auxili_ar->cargo,
                'Abono'=>$auxili_ar->abono,
                'Auxiliar'=>$auxili_ar->id,
                'Error'=>'Cuenta Acumulativa'
            ];
    }
}
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
            <img src="{{$logo}}" alt="Tus-Impuestos" width="120px">
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
    <hr>
    <div class="row mt-2">
        <div class="col-12">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Poliza</th>
                        <th>Codigo</th>
                        <th>Cargo</th>
                        <th>Abono</th>
                        <th>Auxiliar</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $cargos = 0;
                $abonos = 0;
                ?>
                @foreach($auxiliares as $auxi)
                    <?php
                        $cargos+= floatval($auxi['Cargo']);
                        $abonos+= floatval($auxi['Abono']);
                        ?>
                    <tr>
                        <td>{{$auxi['Poliza']}}</td>
                        <td>{{$auxi['Codigo']}}</td>
                        <td>{{'$'.number_format($auxi['Cargo'],2)}}</td>
                        <td>{{'$'.number_format($auxi['Abono'],2)}}</td>
                        <td>{{$auxi['Auxiliar']}}</td>
                        <td>{{$auxi['Error']}}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Totales</td>
                        <td>{{'$'.number_format($cargos,2)}}</td>
                        <td>{{'$'.number_format($abonos,2)}}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>
