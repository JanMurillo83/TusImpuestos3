<?php
    use \Illuminate\Support\Facades\DB;
    use \Carbon\Carbon;
    $orden = DB::table('facturas')->where('id',$idorden)->first();
    $partidas = DB::table('facturas_partidas')->where('facturas_id',$idorden)->get();
    $prove = DB::table('clientes')->where('id',$orden->clie)->first();
    $dafis = DB::table('datos_fiscales')->where('team_id',$id_empresa)->first();
    $emisor = \App\Models\DatosFiscales::where('team_id',$id_empresa)->first();
    $a_xml = $orden->xml;
    $parameters = null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" charset="utf-8">
    <title>CFDI</title>
    <script src="{{public_path('WH/jquery-3.7.1.js')}}"></script>
    <link href="{{public_path('WH/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{public_path('WH/bootstrap-theme.min.css')}}" rel="stylesheet">
    <script src="{{public_path('WH/bootstrap.min.js')}}"></script>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-xs-6" style="font-size: 9px !important;">
                <h6>ESTE DOCUMENTO ES UNA REPRESENTACIÓN IMPRESA DE UN CFDI.</h6>
                <h6>EMISOR <b>{{$dafis->nombre}}</b></h6>
            </div>
            <div class="col-xs-2"></div>
            <div class="col-xs-2" style="font-size: 10px !important;">
                <h4>{{$orden->serie.$orden->folio}}</h4>
            </div>
        </div>
        <hr>
    </div>
</body>
</html>
