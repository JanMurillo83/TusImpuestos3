<?php
use Filament\Facades\Filament;
    $orden = DB::table('cotizaciones')->where('id',$idorden)->get();
    $orden = $orden[0];
    $partidas = DB::table('cotizaciones_partidas')->where('cotizaciones_id',$idorden)->get();
    $prove = DB::table('clientes')->where('id',$orden->clie)->get();
    $prove = $prove[0];
    $logo = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->get()[0]->logo64 ?? '';
?>
<!DOCTYPE html>
<html>
    <head>
        <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <title>Cotizaciones</title>
    </head>
    <body>
        <div class="container mt-5">
            <div class="row">
                <div class="text-start col-2">
                    <img src="{{$logo}}" alt="Logo" width="200px">
                </div>
                <div class="text-center col-7">
                    <h5>COTIZACION</h5>
                </div>
                <div class="content-end col-3">
                    <table class="table table-bordered table-striped" style="width:100%">
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Folio:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{ ($orden->serie ?? '') . ($orden->folio ?? '') }}  </td>
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
                        <tr>
                            <td><b>Moneda:</b></td>
                            <td>{{$orden->moneda ?? 'MXN'}}</td>
                        </tr>
                        @if(($orden->moneda ?? 'MXN') !== 'MXN')
                        <tr>
                            <td><b>Tipo de Cambio:</b></td>
                            <td>{{ number_format($orden->tcambio ?? 1, 6, '.') }}</td>
                        </tr>
                        @endif
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
            <div class="row" style="margin-top: 20px;">
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
