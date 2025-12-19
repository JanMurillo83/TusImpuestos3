<?php
use \Illuminate\Support\Facades\DB;
use \App\Models\SaldosReportes;
$empresas = DB::table('teams')->where('id',$empresa)->get()[0];
$cuentas = SaldosReportes::where('team_id',$empresa)->orderBy('codigo')->get();
$fecha = \Carbon\Carbon::now();
$saldo1 = 0;
$saldo2 = 0;
$saldo3 = 0;
$saldo4 = 0;
?>
    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balanza de Comprobación - Nuevo</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        .text-end { text-align: end; }
        .w-120 { width: 120px; }
        .fw-bold { font-weight: bold; }
        .table thead th { background: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <div class="row mt-5">
        <div class="col-3">
            <img src="{{$logo}}" alt="Tus-Impuestos" width="120px">
        </div>
        <div class="col-6">
            <center>
                <h5>{{$empresas->name}}</h5>
                <div>
                    Balanza de Comprobación (Nuevo) - Periodo {{$periodo}}
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
            <table class="table border table-sm">
                <thead>
                <tr>
                    <th class="fw-bold">Código</th>
                    <th class="fw-bold">Cuenta</th>
                    <th class="fw-bold text-end">Saldo Inicial</th>
                    <th class="fw-bold text-end">Cargos</th>
                    <th class="fw-bold text-end">Abonos</th>
                    <th class="fw-bold text-end">Saldo Final</th>
                </tr>
                </thead>
                <tbody>
                @foreach($cuentas as $cuenta)
                    <?php
                    if($cuenta->nivel == 1) {
                        if ($cuenta->naturaleza == 'D') $saldo1 += $cuenta->anterior; else $saldo1 -= $cuenta->anterior;
                        $saldo2 += $cuenta->cargos;
                        $saldo3 += $cuenta->abonos;
                        if ($cuenta->naturaleza == 'D') $saldo4 += $cuenta->final; else $saldo4 -= $cuenta->final;
                    }
                    ?>
                    @if($cuenta->anterior > 0||$cuenta->cargos > 0||$cuenta->abonos > 0||$cuenta->final > 0)
                        <tr>
                            <td>{{$cuenta->codigo}}</td>
                            <td>{{$cuenta->cuenta}}</td>
                            <td class="text-end">{{'$'.number_format($cuenta->anterior,2)}}</td>
                            <td class="text-end">{{'$'.number_format($cuenta->cargos,2)}}</td>
                            <td class="text-end">{{'$'.number_format($cuenta->abonos,2)}}</td>
                            <td class="text-end">{{'$'.number_format($cuenta->final,2)}}</td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="2" class="fw-bold">Totales</td>
                    <td class="text-end">{{'$'.number_format($saldo1,2)}}</td>
                    <td class="text-end">{{'$'.number_format($saldo2,2)}}</td>
                    <td class="text-end">{{'$'.number_format($saldo3,2)}}</td>
                    <td class="text-end">{{'$'.number_format($saldo4,2)}}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>
