<?php
    require_once(resource_path('/views/qrcode.php'));
    use \League\Plates\Template\Template as Thos;
    use Filament\Facades\Filament;
    $orden = DB::table('facturas')->where('id',$idorden)->first();
    $partidas = DB::table('facturas_partidas')->where('facturas_id',$idorden)->get();
    $prove = DB::table('clientes')->where('id',$orden->clie)->first();
    $dafis = DB::table('datos_fiscales')->where('team_id',$id_empresa)->first();
    $emisor = \App\Models\DatosFiscales::where('team_id',$id_empresa)->first();
    $a_xml = $orden->xml;
    if($a_xml != null&&$a_xml!='')
    {
        $cfdi = \CfdiUtils\Cfdi::newFromString($a_xml);
        $tfd = $cfdi->getNode()->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
        $tfdXmlString = \CfdiUtils\Nodes\XmlNodeUtils::nodeToXmlString($tfd);
        $parameters = \CfdiUtils\ConsultaCfdiSat\RequestParameters::createFromCfdi($cfdi);
        $resolver = new \CfdiUtils\XmlResolver\XmlResolver();
        $location = $resolver->resolveCadenaOrigenLocation('4.0');
        $builder = new \CfdiUtils\CadenaOrigen\DOMBuilder();
        $cadenaOrigen = $builder->build($a_xml, $location);

        $cfdiData = \CfdiUtils\Cfdi::newFromString($a_xml);
        $comprobante = $cfdiData->getQuickReader();
        $emisor = $comprobante->emisor;
        $receptor = $comprobante->receptor;
        $tfd = $comprobante->complemento->TimbreFiscalDigital;
    }
    $logo = $emisor->logo64 ?? '';
?>
<!DOCTYPE html>
<html>
    <head>
        <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.19.0/cdn/components/qr-code/qr-code.js"></script>
        <title>CFDI - Ingreso</title>
    </head>
    <body>
        <div class="container mt-5">
            <div class="row">
                <div class="col-6">
                    <h6>ESTE DOCUMENTO ES UNA REPRESENTACIÓN IMPRESA DE UN CFDI.</h6>
                    <h6>EMISOR <b>{{$dafis->nombre}}</b></h6>
                </div>
                <div class="col-4"></div>
                <div class="col-2">
                    <h4>{{$orden->serie.$orden->folio}}</h4>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-2">
                    <img src="{{$logo}}" alt="NcPos" width="200px">
                </div>
                <div class="col-4">
                    <b>{{$dafis->nombre}}</b><br>
                    <label>{{$dafis->rfc}}</label><br>
                    <label><b>REGIMEN FISCAL</b></label><br>
                    <label>601 - General de Ley Personas Morales</label>
                    <br>
                    <br>
                    <label><b>CONDICIONES DE PAGO:</b></label><label>CONTADO</label><br>
                    <label><b>FORMA DE PAGO:</b></label><label>{{$orden->metodo}}</label><br>
                    <label><b>METODO DE PAGO:</b></label><label>{{$orden->forma}}</label>
                </div>
                <div class="col-4">
                    <label><b>TIPO DE COMPROBANTE:</b></label><label> I - Ingreso</label><br>
                    <label><b>FOLIO FISCAL:</b></label><label>{{$tfd['UUID']?? ''}}</label><br>
                    <label><b>NUMERO DE SERIE DEL CERTIFICADO DEL SAT:</b></label><label>{{$tfd['NoCertificadoSAT'] ?? ''}}</label><br>
                    <label><b>FECHA Y HORA DE CERTIFICACIÓN:</b></label><label>{{$comprobante['Fecha'] ?? ''}}</label><br>
                    <label><b>NUMERO DE SERIE DEL CSD DEL EMISOR:</b></label><label>{{$comprobante['NoCertificado'] ?? ''}}</label><br>
                    <label><b>CLAVE CONFIRMACIÓN:</b></label>
                </div>
                <div class="col-2">
                    <label><b>FACTURA FOLIO:</b></label> <label>{{$orden->serie.$orden->folio}}</label><br>
                    <label><b>FECHA:</b></label> <label>{{$comprobante['Fecha'] ?? ''}}</label><br>
                    <label><b>LUGAR DE EXPEDICION:</b></label> <label>{{$dafis->codigo}}</label><br>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-4">
                    <?php
                        $qr = QRCode::getMinimumQRCode(str_replace('?', "\n?", $parameters->expression() ?? ''), QR_ERROR_CORRECT_LEVEL_L);
                        $qr->make();
                        $qr->printHTML();
                    ?>
                </div>
                <div class="col-6">
                    <label><b>PARA:</b></label><br>
                    <label>{{$orden->clie.'   '.$orden->nombre}}</label><br>
                    <label>{{$prove->rfc}}</label><br>
                    <label><b>RESIDENCIA FISCAL:</b></label><label>MEX</label><br>
                    <label><b>NumRegIdTrib:</b></label><br>
                    <label><b>USO CFDI:</b></label><label>{{$orden->uso}}</label><br>
                </div>
                <div class="col-2">
                    <h6>NOTAS:</h6>
                    <label>{{$orden->observa}}</label>
                </div>
            </div>
            <!--Row1-->
            <div class="mt-2 border row">
                <center><h4>Conceptos</h4></center>
                <table class="table ps-2" style="font-size: 12px !important">
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
                    <table class="table" style="width: 100%">
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
            <div class="row border">
                <label style="font-size: 12px !important"><b>Sello CFDI:</b></label><br>
                {{chunk_split($tfd['SelloCFD'] ?? '', 80)}}
            </div>
            <div class="row border">
                <label style="font-size: 12px !important"><b>Sello SAT:</b></label><br>
                {{chunk_split($tfd['SelloSAT'] ?? '', 80)}}
            </div>
            <div class="row border">
                <label style="font-size: 12px !important"><b>Cadena Original:</b></label><br>
                {{chunk_split($cadenaOrigen ?? '', 80)}}
            </div>
    </div>
    </body>
</html>
