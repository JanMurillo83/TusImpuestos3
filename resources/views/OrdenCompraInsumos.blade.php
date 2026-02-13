<?php
    $orden = DB::table('ordenes_insumos')->where('id',$idorden)->get();
    $orden = $orden[0];
    $partidas = DB::table('ordenes_insumos_partidas')->where('ordenes_insumos_id',$idorden)->get();
    $prove = DB::table('proveedores')->where('id',$orden->prov)->get();
    $prove = $prove[0];
    $empresa = DB::table('teams')->where('id',$id_empresa)->get();
    $empresa = $empresa[0];
?>
<!DOCTYPE html>
<html>
    <head>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <title>Orden de Compra de Insumos</title>
        <style>
            body {
                font-size: 11px;
            }
            .table-sm td, .table-sm th {
                padding: 0.3rem;
            }
        </style>
    </head>
    <body>
        <div class="container mt-5">
            <div class="row">
                <div class="text-start col-3">
                    <img src="{{asset('images/logoNCTR.png')}}" alt="Logo" width="180px">
                </div>
                <div class="text-center col-6">
                    <h4><b>{{$empresa->name}}</b></h4>
                    <h5>Orden de Compra de Insumos</h5>
                </div>
                <div class="content-end col-3">
                    <table class="table table-bordered table-sm" style="width:100%">
                        <tr>
                            <td class="text-start"><b>Folio:</b></td>
                            <td class="text-end">{{$orden->folio}}</td>
                        </tr>
                        <tr>
                            <td class="text-start"><b>Fecha:</b></td>
                            <td class="text-end">{{date_format(date_create($orden->fecha),'d-m-Y')}}</td>
                        </tr>
                        <tr>
                            <td class="text-start"><b>Moneda:</b></td>
                            <td class="text-end">{{$orden->moneda}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <!--Datos del Proveedor-->
            <div class="mt-3 row">
                <div class="col-12">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th colspan="4" class="text-center"><b>DATOS DEL PROVEEDOR</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width: 15%"><b>Proveedor:</b></td>
                                <td colspan="3">{{$orden->nombre}}</td>
                            </tr>
                            <tr>
                                <td><b>RFC:</b></td>
                                <td>{{$prove->rfc}}</td>
                                <td style="width: 15%"><b>Contacto:</b></td>
                                <td>{{$prove->contacto}}</td>
                            </tr>
                            <tr>
                                <td><b>Teléfono:</b></td>
                                <td>{{$prove->telefono}}</td>
                                <td><b>Correo:</b></td>
                                <td>{{$prove->correo}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!--Datos de Entrega y Comerciales-->
            @if($orden->entrega_lugar || $orden->entrega_direccion || $orden->condiciones_pago || $orden->solicita)
            <div class="row">
                <div class="col-6">
                    @if($orden->entrega_lugar || $orden->entrega_direccion || $orden->entrega_horario || $orden->entrega_contacto || $orden->entrega_telefono)
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th colspan="2" class="text-center"><b>DATOS DE ENTREGA</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($orden->entrega_lugar)
                            <tr>
                                <td style="width: 40%"><b>Lugar:</b></td>
                                <td>{{$orden->entrega_lugar}}</td>
                            </tr>
                            @endif
                            @if($orden->entrega_direccion)
                            <tr>
                                <td><b>Dirección:</b></td>
                                <td>{{$orden->entrega_direccion}}</td>
                            </tr>
                            @endif
                            @if($orden->entrega_horario)
                            <tr>
                                <td><b>Horario:</b></td>
                                <td>{{$orden->entrega_horario}}</td>
                            </tr>
                            @endif
                            @if($orden->entrega_contacto)
                            <tr>
                                <td><b>Contacto:</b></td>
                                <td>{{$orden->entrega_contacto}}</td>
                            </tr>
                            @endif
                            @if($orden->entrega_telefono)
                            <tr>
                                <td><b>Teléfono:</b></td>
                                <td>{{$orden->entrega_telefono}}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    @endif
                </div>
                <div class="col-6">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th colspan="2" class="text-center"><b>DATOS COMERCIALES</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($orden->solicita)
                            <tr>
                                <td style="width: 40%"><b>Solicita:</b></td>
                                <td>{{$orden->solicita}}</td>
                            </tr>
                            @endif
                            @if($orden->condiciones_pago)
                            <tr>
                                <td><b>Condiciones de Pago:</b></td>
                                <td>{{$orden->condiciones_pago}}</td>
                            </tr>
                            @endif
                            @if($orden->condiciones_entrega)
                            <tr>
                                <td><b>Condiciones de Entrega:</b></td>
                                <td>{{$orden->condiciones_entrega}}</td>
                            </tr>
                            @endif
                            @if($orden->oc_referencia_interna)
                            <tr>
                                <td><b>Referencia Interna:</b></td>
                                <td>{{$orden->oc_referencia_interna}}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            <!--Partidas-->
            <div class="mt-3 row">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 8%"><b>Cantidad</b></th>
                            <th style="width: 12%"><b>Clave</b></th>
                            <th><b>Descripción</b></th>
                            <th style="width: 12%"><b>Costo Unit.</b></th>
                            <th style="width: 12%"><b>Subtotal</b></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($partidas as $part)
                        <tr>
                            <td class="text-center">{{number_format($part->cant, 2, '.', ',')}}</td>
                            <td>{{$part->item}}</td>
                            <td>{{$part->descripcion}}</td>
                            <td class="text-end">$ {{number_format($part->costo, 2, '.', ',')}}</td>
                            <td class="text-end">$ {{number_format($part->subtotal, 2, '.', ',')}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!--Observaciones y Totales-->
            <div class="row">
                <div class="col-7">
                    @if($orden->observa)
                    <div class="border p-2">
                        <label><b>Observaciones:</b></label><br>
                        <p style="font-size: 10px;">{{$orden->observa}}</p>
                    </div>
                    @endif
                </div>
                <div class="col-5">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th colspan="2" class="text-center"><b>TOTALES</b></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width: 50%"><b>Subtotal:</b></td>
                                <td class="text-end">$ {{number_format($orden->subtotal, 2, '.', ',')}}</td>
                            </tr>
                            @if($orden->iva > 0)
                            <tr>
                                <td><b>IVA:</b></td>
                                <td class="text-end">$ {{number_format($orden->iva, 2, '.', ',')}}</td>
                            </tr>
                            @endif
                            @if($orden->retiva > 0)
                            <tr>
                                <td><b>Retención IVA:</b></td>
                                <td class="text-end">$ {{number_format($orden->retiva, 2, '.', ',')}}</td>
                            </tr>
                            @endif
                            @if($orden->retisr > 0)
                            <tr>
                                <td><b>Retención ISR:</b></td>
                                <td class="text-end">$ {{number_format($orden->retisr, 2, '.', ',')}}</td>
                            </tr>
                            @endif
                            @if($orden->ieps > 0)
                            <tr>
                                <td><b>IEPS:</b></td>
                                <td class="text-end">$ {{number_format($orden->ieps, 2, '.', ',')}}</td>
                            </tr>
                            @endif
                            <tr class="table-info">
                                <td><b>Total {{$orden->moneda}}:</b></td>
                                <td class="text-end"><b>$ {{number_format($orden->total, 2, '.', ',')}}</b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!--Firmas-->
            @if($orden->nombre_elaboro || $orden->nombre_autorizo)
            <div class="row mt-5">
                <div class="col-6 text-center">
                    @if($orden->nombre_elaboro)
                    <div class="border-top pt-2" style="margin: 0 50px;">
                        <b>{{$orden->nombre_elaboro}}</b><br>
                        <small>Elaboró</small>
                    </div>
                    @endif
                </div>
                <div class="col-6 text-center">
                    @if($orden->nombre_autorizo)
                    <div class="border-top pt-2" style="margin: 0 50px;">
                        <b>{{$orden->nombre_autorizo}}</b><br>
                        <small>Autorizó</small>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </body>
</html>
