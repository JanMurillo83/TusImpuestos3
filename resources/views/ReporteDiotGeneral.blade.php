<!doctype html>
<html lang="en">
<head>
    <?php
    $dafis = DB::table('datos_fiscales')->where('team_id',$id_empresa)->first();
    $logo = public_path('storage/'.$dafis->logo);
    $cuentas = \Illuminate\Support\Facades\DB::table('auxiliares')
        ->select('codigo','cuenta')->distinct('codigo','cuenta')
        ->where('auxiliares.codigo','like','20101%')
        ->where('a_periodo',$periodo)->where('a_ejercicio',$ejercicio)
        ->where('team_id',$id_empresa)->where('cargo','>',0)
        ->get();
    //dd($cuentas);
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saldo Clientes</title>
    <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-3">
            <img src="{{$logo}}" alt="Tus Impuestos" width="100px" style="margin-top: 1rem !important;">
        </div>
        <div class="col-6">
            <center>
                <label style="font-size: 12px">{{$dafis->nombre}}</label>
                <br>
                <label style="font-size: 14px; font-weight: bold">DIOT</label>
            </center>
        </div>
        <div class="col-3">
            <label style="font-size: 12px">Periodo: {{$periodo}}</label>
            <br>
            <label style="font-size: 12px">Ejercicio: {{$ejercicio}}</label>
        </div>
    </div>
    <hr>
    <div class="row">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tercero</th>
                    <th>RFC</th>
                    <th>Importe Actos Pagados</th>
                    <th>Importe IVA Actos Pagados</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $tot_cargos = 0;
                    $tot_iva = 0;
                ?>
                @foreach($cuentas as $aux)
                    <?php
                        $auxiliares = \App\Models\Auxiliares::where('codigo',$aux->codigo)
                            ->where('a_periodo',$periodo)->where('a_ejercicio',$ejercicio)
                            ->where('team_id',$id_empresa)->where('cargo','>',0)->get();
                        $catctas = \App\Models\CatCuentas::where('codigo',$aux->codigo)
                            ->where('team_id',$id_empresa)->first();
                        $rfc_Asoc = $catctas->rfc_asociado;
                        //dd($catctas,$rfc_Asoc);
                        $cargos = 0;
                        $iva = 0;
                        foreach ($auxiliares as $auxi){
                            $cargos+= $auxi->cargo;
                            $iva+= $auxi->cargo /1.16 *0.16;
                        }
                        $tot_cargos+= $cargos;
                        $tot_iva+= $iva;
                    ?>
                    <tr>
                        <td>{{substr($aux->cuenta,0,50)}}</td>
                        <td>{{$rfc_Asoc}}</td>
                        <td>{{'$'.number_format($cargos,2)}}</td>
                        <td>{{'$'.number_format($iva,2)}}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Totales :</td>
                    <td>{{'$'.number_format($tot_cargos,2)}}</td>
                    <td>{{'$'.number_format($tot_iva,2)}}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</body>
</html>
