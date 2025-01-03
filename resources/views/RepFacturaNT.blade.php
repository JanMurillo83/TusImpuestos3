<?php
use Filament\Facades\Filament;
    $orden = DB::table('facturas')->where('id',$idorden)->get();
    $orden = $orden[0];
    $partidas = DB::table('facturas_partidas')->where('facturas_id',$idorden)->get();
    $prove = DB::table('clientes')->where('id',$orden->clie)->get();
    $prove = $prove[0];
    $logo = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->get()[0]->logo64 ?? '';
?>
<!DOCTYPE html>
<html>
    <head>
        <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
        <link href="{{public_path('dist/css/bootstrap.css')}}" rel="stylesheet">
        <script src="{{public_path('dist/js/bootstrap.bundle.js')}}"></script>
        <title>Factura</title>
    </head>
    <body>
        <div class="container mt-5">
            <div class="row">
                <div class="text-start col-2">
                    <img src="{{asset('images/logoNCTR.png')}}" alt="NcPos" width="200px">
                </div>
                <div class="text-center col-7">
                    <h5>FACTURA</h5>
                </div>
                <div class="content-end col-3">
                    <table class="table table-bordered table-striped" style="width:100%">
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Folio:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$orden->folio}}  </td>
                        </tr>
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Fecha:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{date_format(date_create($orden->fecha),'d-m-Y')}}  </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!--Row1-->
            <div class="mt-5 border row">
                <div class="col-6">
                    <table>
                        <tr>
                            <td><b>Cliente:</b></td>
                            <td colspan="3">{{$orden->clie.'   '.$orden->nombre}}</td>
                        </tr>
                        <tr>
                            <td><b>RFC:</b></td>
                            <td>{{$prove->rfc}}</td>
                        </tr>
                        <tr>
                            <td><b>Contacto:</b></td>
                            <td>{{$prove->contacto}}</td>
                        </tr>
                        <tr>
                            <td><b>Telefono:</b></td>
                            <td>{{$prove->telefono}}</td>
                            <td><b>Correo:</b></td>
                            <td>{{$prove->correo}}</td>
                        </tr>
                        <tr>
                            <td><b>Condiciones:</b></td>
                            <td>{{$orden->condiciones}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <!--Row1-->
            <div class="mt-5 row">
                <table class="table table-striped">
                    <tr>
                        <th><b>Cantidad</b></th>
                        <th colspan="3"><b>Descripcion</b></th>
                        <th><b>Precio Unitario</b></th>
                        <th><b>Total</b></th>
                    </tr>
                    @foreach ($partidas as $part)
                    <tr>
                        <td>{{$part->cant}}</td>
                        <td colspan="3">{{$part->item.'  '.$part->descripcion}}</td>
                        <td>{{'$ '.number_format($part->precio, 2, '.')}}</td>
                        <td>{{'$ '.number_format($part->subtotal, 2, '.')}}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
            <!--Row1-->
            <div class="row">
                <div class="border col-8">
                    <label>Observaciones: </label> <br>
                    <p>{{$orden->observa}}</p>
                </div>
                <div class="col-4">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <tr>
                            <th colspan="3" class="text-center"><b>TOTALES</b></th>
                        </tr>
                        <tr>
                            <td><b>Subtotal:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format($orden->subtotal, 2, '.')}}</td>
                        </tr>
                        @if($orden->iva > 0)
                        <tr>
                            <td><b>IVA:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format($orden->iva, 2, '.')}}</td>
                        </tr>
                        @endif
                        @if($orden->retiva > 0)
                        <tr>
                            <td><b>Retencion IVA:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format($orden->iva, 2, '.')}}</td>
                        </tr>
                        @endif
                        @if($orden->retisr > 0)
                        <tr>
                            <td><b>Retencion ISR:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format($orden->retisr, 2, '.')}}</td>
                        </tr>
                        @endif
                        @if($orden->retisr > 0)
                        <tr>
                            <td><b>IEPS:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format($orden->ieps, 2, '.')}}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><b>Total:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format($orden->total, 2, '.')}}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
