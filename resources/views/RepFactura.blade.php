<?php
    require_once(resource_path('/views/qrcode.php'));
    use \League\Plates\Template\Template as Thos;
    $orden = DB::table('facturas')->where('id',$idorden)->get();
    $orden = $orden[0];
    $partidas = DB::table('facturas_partidas')->where('facturas_id',$idorden)->get();
    $prove = DB::table('clientes')->where('id',$orden->clie)->get();
    $prove = $prove[0];
    $dafis = DB::table('datos_fiscales')->where('id',1)->get();
    $dafis = $dafis[0];
    if(isset($cfdiData))
    {
        $comprobante = $cfdiData->comprobante();
        $emisor = $cfdiData->emisor();
        $receptor = $cfdiData->receptor();
        $tfd = $cfdiData->timbreFiscalDigital();
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.19.0/cdn/components/qr-code/qr-code.js"></script>
        <title>CFDI - Ingreso</title>
    </head>
    <body>
        <div class="container mt-5">
            <div class="row">
                <div class="text-start col-4">
                    <img src="{{asset('images/logoNCTR.png')}}" alt="NcPos" width="200px">
                </div>
                <div class="text-start col-5">
                    <center>
                        <h5>Comprobante Fiscal Digital Ingreso</h5>
                        <table>
                            <tr>
                                <td><b>Emisor:</b></td>
                                <td>{{$emisor['Nombre']}}</td>
                            </tr>
                            <tr>
                                <td><b>RFC:</b></td>
                                <td>{{$emisor['Rfc']}}</td>
                            </tr>
                            <tr>
                                <td><b>Regimen Fiscal:</b></td>
                                <td>{{$dafis->regimen}}</td>
                            </tr>
                            <tr>
                                <td><b>Expedido en:</b></td>
                                <td>{{$dafis->codigo}}</td>
                            </tr>
                        </table>
                    </center>
                </div>
            </div>
            <div class="border row">
                <div class="mt-2 col-7">
                    <table class="table table-bordered table-striped" style="width:100%">
                        <tr>
                            <td><b>Cliente:</b></td>
                            <td colspan="3">{{$orden->clie.'   '.$orden->nombre}}</td>
                        </tr>
                        <tr>
                            <td><b>RFC:</b></td>
                            <td>{{$prove->rfc}}</td>
                        </tr>
                        <tr>
                            <td><b>Regimen Fiscal:</b></td>
                            <td>{{$prove->regimen}}</td>
                        </tr>
                        <tr>
                            <td><b>Codigo Postal:</b></td>
                            <td>{{$prove->codigo}}</td>
                        </tr>
                        <tr>
                            <td><b>Contacto:</b></td>
                            <td colspan="3">{{$prove->contacto}}</td>
                        </tr>
                        <tr>
                            <td><b>Telefono:</b></td>
                            <td>{{$prove->telefono}}</td>
                        </tr>
                        <tr>
                            <td><b>Condiciones:</b></td>
                            <td>{{$orden->condiciones}}</td>
                        </tr>
                    </table>
                </div>
                <div class="content-end mt-2 col-5">
                    <table class="table table-bordered table-striped" style="width:100%">
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Serie y Folio:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$orden->serie.' '.$orden->folio}}  </td>
                        </tr>
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Fecha:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$comprobante['Fecha']}}  </td>
                        </tr>
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Certificado SAT:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$tfd['NoCertificadoSAT']}}  </td>
                        </tr>
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Certificado Emisor:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$comprobante['NoCertificado']}}  </td>
                        </tr>
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Forma de pago:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$orden->metodo}}  </td>
                        </tr>
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Metodo de Pago:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$orden->forma}}  </td>
                        </tr>
                        <tr>
                            <td class="pl-3 pr-3 text-start"><b>  Uso de CFDI:  </b></td>
                            <td class="pl-3 pr-3 text-end">  {{$orden->uso}}  </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!--Row1-->
            <div class="mt-2 border row">
                <center><h4>Conceptos</h4></center>
                <table class="table table-striped ps-2" style="font-size: 12px !important">
                    <tr>
                        <th class="ps-2"><b>Cantidad</b></th>
                        <th><b>Unidad</b></th>
                        <th><b>Clave SAT</b></th>
                        <th colspan="4"><b>Descripcion</b></th>
                        <th><b>Precio Unitario</b></th>
                        <th><b>Total</b></th>
                    </tr>
                    @foreach ($partidas as $part)
                    <tr>
                        <td class="ps-2">{{number_format(floatval($part->cant), 2, '.')}}</td>
                        <td>{{$part->unidad}}</td>
                        <td>{{$part->cvesat}}</td>
                        <td colspan="4">{{$part->item.'  '.$part->descripcion}}</td>
                        <td>{{'$ '.number_format(floatval($part->precio), 2, '.')}}</td>
                        <td>{{'$ '.number_format(floatval($part->subtotal), 2, '.')}}</td>
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
                <div class="pt-2 border col-4">
                    <table class="table table-striped" style="width: 100%">
                        <tr>
                            <th colspan="3" class="text-center"><b>TOTALES</b></th>
                        </tr>
                        <tr>
                            <td><b>Subtotal:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format(floatval($orden->subtotal), 2, '.')}}</td>
                        </tr>
                        @if($orden->iva > 0)
                        <tr>
                            <td><b>IVA:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format(floatval($orden->iva), 2, '.')}}</td>
                        </tr>
                        @endif
                        @if($orden->retiva > 0)
                        <tr>
                            <td><b>Retencion IVA:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format(floatval($orden->retiva), 2, '.')}}</td>
                        </tr>
                        @endif
                        @if($orden->retisr > 0)
                        <tr>
                            <td><b>Retencion ISR:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format(floatval($orden->retisr), 2, '.')}}</td>
                        </tr>
                        @endif
                        @if($orden->retisr > 0)
                        <tr>
                            <td><b>IEPS:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format(floatval($orden->ieps), 2, '.')}}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><b>Total:  </b></td>
                            <td colspan="2" class="text-end">{{'$ '.number_format(floatval($orden->total), 2, '.')}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="mt-4 text-justify border row" style="font-size: 10px !important">
                <div class="border col-6">
                    <label style="font-size: 12px !important"><b>Sello CFDI:</b></label><br>
                    {{chunk_split($tfd['SelloCFD'], 80)}}
                </div>
                <div class="border col-6">
                    <label style="font-size: 12px !important"><b>Sello SAT:</b></label><br>
                    {{chunk_split($tfd['SelloSAT'], 80)}}
                </div>
            </div>
            <div class="text-sm text-justify border row">
                <div class="border col-6" style="font-size: 10px !important">
                    <label style="font-size: 12px !important"><b>Cadena Original:</b></label><br>
                    {{chunk_split($cfdiData->tfdSourceString(), 80)}}
                </div>
                <div class="border col-6">
                    <center>
                        <label style="font-size: 10px !important"><b>UUID:  {{$tfd['UUID']}}</b></label>
                        <?php
                            $qr = QRCode::getMinimumQRCode(str_replace('?', "\n?", $cfdiData->qrUrl()), QR_ERROR_CORRECT_LEVEL_L);
                            $qr->make();
                            $qr->printHTML();
                        ?>
                    </center>
                </div>
            </div>
            <div class="text-sm text-justify border row">
             <center><label>Este documento es una representación impresa de un Comprobante Fiscal Digital a través de Internet versión 4.0</label></center>
            </div>
    </div>
    </body>
</html>
