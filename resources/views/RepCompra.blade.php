<?php
    use \Illuminate\Support\Facades\DB;
    $orden = DB::table('compras')->where('id',$idorden)->first();
    $partidas = DB::table('compras_partidas')->where('compras_id',$idorden)->get();
    $prove = $orden ? DB::table('proveedores')->where('id',$orden->prov)->first() : null;
    $dafis = DB::table('datos_fiscales')->where('team_id',$id_empresa)->first();
    $emisor = \App\Models\DatosFiscales::where('team_id',$id_empresa)->first();
    $proy = $orden ? \App\Models\Proyectos::where('id',$orden->proyecto)->first() : null;
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Compra</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row border border-dark mt-2 ">
                <div class="col-2 mt-2 mb-2">
                    <img src="{{$dafis->logo64 ?? ''}}" alt="Tus-Impuestos" width="300px" onerror="this.style.display='none'">
                </div>
                <div class="col-8 mt-2 mb-2">
                    <center><h3 style="font-weight: bold">COMPRA</h3></center>
                </div>
                <div class="col-2 mt-2 mb-2">
                    <h5>CODIGO  {{$orden->folio}}</h5>
                    <h5>FECHA  {{\Carbon\Carbon::make($orden->fecha)->format('d-m-Y')}}</h5>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-9">
                    <h4 style="font-weight: bold">PROVEEDOR: {{$prove->nombre ?? ''}}</h4>
                    <h4 style="font-weight: bold">DEPARTAMENTO DE COMPRAS - EMPRESA: {{$dafis->nombre ?? ''}}</h4>
                </div>
                <div class="col-3">
                    <h5>FOLIO  <span style="color: red">{{$orden->folio}}</span></h5>
                    <h5>FECHA  {{\Carbon\Carbon::make($orden->fecha)->format('d-m-Y')}}</h5>
                </div>
            </div>
            <hr>
            <div class="row mt-3">
                <div class="col-12">
                    <table class="table table-bordered border-dark">
                        <thead>
                            <tr style="background-color: whitesmoke; color: black; font-weight: bold">
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Unidad SAT</th>
                                <th>Clave SAT</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        @foreach($partidas as $partida)
                            <?php
                                $prod = \App\Models\Inventario::where('id',$partida->item)->first();
                            ?>
                            <tr style="color: black; font-weight: normal">
                                <th style="font-weight: normal">{{number_format($partida->cant,2)}}</th>
                                <th style="font-weight: normal">{{$prod->unidad ?? ''}}</th>
                                <th style="font-weight: normal">{{$prod->unidad ?? ''}}</th>
                                <th style="font-weight: normal">{{$prod->cvesat ?? ''}}</th>
                                <th style="font-weight: normal">{{$partida->descripcion}}</th>
                                <th style="text-align: end; font-weight: normal">{{'$'.number_format($partida->costo,2)}}</th>
                                <th style="text-align: end; font-weight: normal">{{'$'.number_format($partida->subtotal,2)}}</th>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-9">
                    <h4 style="color:white;">....</h4>
                </div>
                <div class="col-3">
                    <table class="table table-bordered border-dark">
                        <tr>
                            <td style="font-size: larger; font-weight: bold; background-color: whitesmoke">SUBTOTAL:</td>
                            <td style="font-size: larger;text-align: end">{{'$'.number_format($orden->subtotal,2)}}</td>
                        </tr>
                        <tr>
                            <td style="font-size: larger; font-weight: bold; background-color: whitesmoke">IVA:</td>
                            <td style="font-size: larger;text-align: end">{{'$'.number_format($orden->iva,2)}}</td>
                        </tr>
                        <tr>
                            <td style="font-size: larger; font-weight: bold; background-color: whitesmoke">TOTAL:</td>
                            <td style="font-size: larger;text-align: end">{{'$'.number_format($orden->total,2)}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <?php
                    $formatter = new \Luecano\NumeroALetras\NumeroALetras();
                    $cant_letras = '';
                    $moneda = $orden->moneda ?? 'MXN';
                    if($moneda == 'MXN'){
                        $formatter->conector = 'PESOS';
                        $cant_letras = $formatter->toInvoice($orden->total, 2, 'M.N.');
                    }else{
                        $formatter->conector = 'DOLARES';
                        $cant_letras = $formatter->toInvoice($orden->total, 2, 'USD');
                    }
                    ?>
                    <h5 style="font-weight: bold">{{$cant_letras}}</h5>
                    <h5>MONEDA: <b>{{$orden->moneda}}</b></h5>
                </div>
            </div>
            <hr>
            <div class="row mt-3">
                <div class="col-12 border border-dark">
                    <h5 style="font-weight: bold" class="mb-1 mt-1">Proyecto: <span style="font-weight: normal">{{$proy->descripcion ?? ''}}</span></h5>
                </div>
            </div>
            <div class="row mt-3">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="font-size: larger; font-weight: bold; background-color: whitesmoke;text-align: center;">COMENTARIOS</th>
                            <th style="font-size: larger; font-weight: bold; background-color: whitesmoke;text-align: center;">RECIBE</th>
                        </tr>
                    </thead>
                        <tr>
                            <td style="font-size: larger; font-weight: normal; text-align: center;">{{$orden->observa ?? ''}}</td>
                            <td style="font-size: larger; font-weight: normal; text-align: center;">{{$orden->recibe ?? ''}}</td>
                        </tr>
                </table>
            </div>
            <hr>
            <div class="row mt-3">
                <div class="col-12" style="text-align: end">
                    <h5>{{$dafis->direccion ?? ''}}</h5>
                    <h5>Teléfonos: {{$dafis->telefono ?? ''}}</h5>
                    <h5>Correo: {{$dafis->correo ?? ''}}</h5>
                </div>
            </div>
            <hr>
        </div>
    </body>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</html>
