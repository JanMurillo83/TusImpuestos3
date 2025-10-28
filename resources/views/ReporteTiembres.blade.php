<!doctype html>
<html lang="es">
<head>
    <?php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;
    $teams = DB::table('teams')->get();
    $f_main = Carbon::create($ejercicio,$periodo);
    $f_day = intval($f_main->firstOfMonth()->format('d'));
    $l_day = intval($f_main->lastOfMonth()->format('d'));
    $f_incial = Carbon::create($ejercicio,$periodo,$f_day);
    $f_final = Carbon::create($ejercicio,$periodo,$l_day);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Timbres</title>
    <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <center>
                    <h1>REPORTE DE TIMBRES EMITIDOS</h1><br>
                    <h4>Fecha Inicial: {{$f_incial->format('d-m-Y')}}  Fecha Final: {{$f_final->format('d-m-Y')}}</h4>
                </center>

            </div>
        </div>
        <div class="row">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>RFC</th>
                        <th>Razón Social</th>
                        <th>Facturas</th>
                        <th>Notas de Crédito</th>
                        <th>Comprobantes de Pago</th>
                        <th>Timbrado Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teams as $team)
                        <?php
                            $facturas = count(DB::table('facturas')->where('team_id',$team->id)->whereBetween(DB::raw('DATE(fecha)'),[$f_incial,$f_final])->where('timbrado','SI')->get());
                            $notas = count(DB::table('notade_creditos')->where('team_id',$team->id)->whereBetween(DB::raw('DATE(fecha)'),[$f_incial,$f_final])->where('timbrado','SI')->get());
                            $pagos = count(DB::table('pagos')->where('team_id',$team->id)->whereBetween(DB::raw('DATE(fecha_doc)'),[$f_incial,$f_final])->where('timbrado','SI')->get());
                            $total = $facturas + $notas + $pagos;
                        ?>
                    <tr>
                        <td style="font-weight: bold">{{$team->taxid}}</td>
                        <td>{{$team->name}}</td>
                        <td>{{intval($facturas)}}</td>
                        <td>{{intval($notas)}}</td>
                        <td>{{intval($pagos)}}</td>
                        <td>{{intval($total)}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
