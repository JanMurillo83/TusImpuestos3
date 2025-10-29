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
    $parameters = null;
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

    //$logo = $dafis->logo64 ?? '';
    $logo = asset('storage/'.$dafis->logo);
?>
<!DOCTYPE html>
<html>
    <head>
        <script src="{{public_path('js/jquery-3.7.1.js')}}"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.19.0/cdn/components/qr-code/qr-code.js"></script>
        <meta name="viewport" content="width=device-width, initial-scale=0.8" />
        <title>CFDI - Ingreso</title>
    </head>
    <body>
        <div class="container mt-5" style="margin-left: 1rem !important; margin-right: 1rem !important;">
            <div class="row">
                <div class="col-6" style="font-size: 9px !important;">
                    <h6>ESTE DOCUMENTO ES UNA REPRESENTACIÓN IMPRESA DE UN CFDI.</h6>
                    <h6>EMISOR <b>{{$dafis->nombre}}</b></h6>
                </div>
                <div class="col-4"></div>
                <div class="col-2" style="font-size: 10px !important;">
                    <h4>{{$orden->serie.$orden->folio}}</h4>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-3">
                    <img src="{{$logo}}" alt="Tus Impuestos" width="200px">
                </div>
                <div class="col-3" style="font-size: 10px !important;">
                    <b>{{$dafis->nombre}}</b><br>
                    <label>{{$dafis->rfc}}</label><br>
                    <label><b>REGIMEN FISCAL</b></label><br>
                    <label>601 - General de Ley Personas Morales</label>
                    <br>
                    <br>
                    <label><b>CONDICIONES DE PAGO:</b></label><label>{{$orden->condiciones}}</label><br>
                    <label><b>FORMA DE PAGO:</b></label><label>{{$orden->metodo}}</label><br>
                    <label><b>METODO DE PAGO:</b></label><label>{{$orden->forma}}</label>
                </div>
                <div class="col-4" style="font-size: 10px !important;">
                    <label><b>TIPO DE COMPROBANTE:</b></label><label> I - Ingreso</label><br>
                    <label><b>FOLIO FISCAL:</b></label><label>{{$tfd['UUID']?? ''}}</label><br>
                    <label><b>NUMERO DE SERIE DEL CERTIFICADO DEL SAT:</b></label><label>{{$tfd['NoCertificadoSAT'] ?? ''}}</label><br>
                    <label><b>FECHA Y HORA DE CERTIFICACIÓN:</b></label><label>{{$comprobante['Fecha'] ?? ''}}</label><br>
                    <label><b>NUMERO DE SERIE DEL CSD DEL EMISOR:</b></label><label>{{$comprobante['NoCertificado'] ?? ''}}</label><br>
                    <label><b>CLAVE CONFIRMACIÓN:</b></label>
                </div>
                <div class="col-2" style="font-size: 10px !important;">
                    <label><b>FACTURA FOLIO:</b></label> <label>{{$orden->serie.$orden->folio}}</label><br>
                    <label><b>FECHA:</b></label> <label>{{$comprobante['Fecha'] ?? ''}}</label><br>
                    <label><b>LUGAR DE EXPEDICION:</b></label> <label>{{$dafis->codigo}}</label><br>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-4">
                    <?php
                        if($parameters) {
                            $qr = QRCode::getMinimumQRCode(str_replace('?', "\n?", $parameters->expression() ?? ''), QR_ERROR_CORRECT_LEVEL_L);
                            $qr->make();
                            $qr->printHTML();
                        }
                    ?>
                </div>
                <div class="col-6" style="font-size: 10px !important;">
                    <label><b>PARA:</b></label><br>
                    <label>{{$orden->nombre}}</label><br>
                    <label>{{$prove->rfc}}</label><br>
                    <label><b>RESIDENCIA FISCAL:</b></label><label>MEX</label><br>
                    <label><b>NumRegIdTrib:</b></label><br>
                    <label><b>USO CFDI:</b></label><label>{{$orden->uso}}</label><br>
                </div>
                <div class="col-2" style="font-size: 10px !important;">
                    <h6>NOTAS:</h6>
                    <label>{{$orden->observa}}</label>
                </div>
            </div>
            <!--Row1-->
            <hr>
            <div class="row" style="font-size: 10px !important;">
                <table style="width: 98% !important;margin-bottom: 0.2rem !important;">
                    <tr>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">CANTIDAD</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">CLAVE PROD/SERV</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">CLAVE UNIDAD</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;" colspan="2">DESCRIPCION</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">VALOR UNITARIO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">DESCUENTO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">IMPORTE</th>
                    </tr>
                    @foreach ($partidas as $part)
                        <tr style="font-size: 7px !important;margin-bottom: 0.2rem !important;">
                            <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">{{number_format(floatval($part->cant), 2, '.')}}</td>
                            <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">{{$part->cvesat}}</td>
                            <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">{{$part->unidad}}</td>
                            <td style="border-style: solid; border-width: 1px; border-color: #1a1e21; text-align: center; align-content: center; font-size: 7px !important;vertical-align: top !important;" colspan="2">{{$part->descripcion}}</td>
                            <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">{{'$ '.number_format(floatval($part->precio), 2, '.')}}</td>
                            <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">{{'$ '.number_format(0)}}</td>
                            <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">{{'$ '.number_format(floatval($part->subtotal), 2, '.')}}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="row" style="margin-top: 5rem; margin-left: -2rem !important">
                <div class="col-8" style="font-size: 10px !important;">
                    <?php
                    $tiporel = '';
                    $doctore = '';
                    if(count(\App\Models\DoctosRelacionados::where('docto_type','F')->where('docto_id',$orden->id)->get())>0){
                        $doctorel = \App\Models\DoctosRelacionados::where('docto_type','F')->where('docto_id',$orden->id)->first();
                        //dd($doctorel);
                        $facrel = \App\Models\Facturas::where('id',$doctorel->rel_id)->first();
                        $doctore = $facrel->uuid;
                        switch ($doctorel->rel_cause){
                            case '01': $tiporel = '01-Nota de crédito de los documentos relacionados'; break;
                            case '02': $tiporel = '02-Nota de débito de los documentos relacionados'; break;
                            case '03': $tiporel = '03-Devolución de mercancía sobre facturas o traslados previos'; break;
                            case '04': $tiporel = '04-Sustitución de los CFDI previos'; break;
                            case '05': $tiporel = '05-Traslados de mercancias facturados previamente'; break;
                            case '06': $tiporel = '06-Factura generada por los traslados previos'; break;
                            case '07': $tiporel = '07-CFDI por aplicación de anticipo'; break;
                        }
                    }
                    ?>
                    <label><b>TIPO RELACIÓN: </b>{{$tiporel}}</label><br>
                    <label><b>CFDI RELACIONADO: </b>{{$doctore}}</label>
                </div>
                <div class="col-4" style="font-size: 10px !important;margin-left: -2rem !important;">
                    <table style="padding-right: 2rem !important; width: 100% !important;font-size: 15px !important; margin-right: 1rem !important;">
                        <tr>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end">SUBTOTAL:</td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end; margin-left: 2rem">{{number_format(floatval($orden->subtotal), 2, '.')}}</td>
                        </tr>
                        <tr>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end">DESCUENTO:</td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end; margin-left: 2rem">{{number_format(floatval(0), 2, '.')}}</td>
                        </tr>
                        <tr>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end">IMPUESTOS TRASLADADOS:</td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end; margin-left: 2rem">{{number_format(floatval($orden->iva)+floatval($orden->ieps), 2, '.')}}</td>
                        </tr>
                        <tr>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end">IMPUESTOS RETENIDOS:</td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end; margin-left: 2rem">{{number_format(floatval($orden->retiva)+floatval($orden->retisr), 2, '.')}}</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <hr>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end">TOTAL:</td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td style="font-size: 10px !important;font-weight: bold;text-align: end; align-content: end; margin-left: 2rem">{{number_format(floatval($orden->total), 2, '.')}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-7"></div>
                <div class="col-5" style="font-size: 10px !important;">
                <?php
                    $formatter = new \Luecano\NumeroALetras\NumeroALetras();
                    $cant_letras = '';

                    if($orden->moneda == 'MXN'){
                        $formatter->conector = 'PESOS';
                        $cant_letras = $formatter->toInvoice($orden->total, 2, 'M.N.');
                    }else{
                        $formatter->conector = 'DOLARES';
                        $cant_letras = $formatter->toInvoice($orden->total, 2, 'USD');
                    }
                ?>
                    <h6 style="font-weight: bold; margin-right: 10px !important; ">{{$cant_letras}}</h6>
                    <h6>MONEDA: <b>{{$orden->moneda}}</b>   TIPO DE CAMBIO: <b>{{number_format($orden->tcambio,2)}}</b></h6>
                </div>

            </div>
            <div class="row" style="font-size: 10px !important;">
                <table style="width: 98% !important;">
                    <tr style="margin-bottom: 0.2rem !important;">
                        <th style="font-weight: bold; border-style: solid; border-width: 1px; border-color: #1a1e21; text-align: center; align-content: center; vertical-align: top !important;">RETENCIONES LOCALES</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 1px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">TRASLADOS LOCALES</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 1px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">RETENCIONES FEDERALES</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 1px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">TRASLADOS FEDERALES</th>
                    </tr>
                    <tr style="margin-bottom: 0.2rem !important;">
                        <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">ninguna</td>
                        <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">ninguna</td>
                        <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">
                            <?php
                            $ret = '';
                            if($orden->retiva > 0) $ret.=' IVA RETENIDO'.' '.number_format($orden->retiva,2);
                            if($orden->retisr > 0) $ret.=' ISR RETENIDO'.' '.number_format($orden->retisr,2);
                            if($ret == '') $ret.='0';
                            echo $ret;
                            ?>
                        </td>
                        <td style="border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;vertical-align: top !important;">
                            <?php
                            $ret = '';
                            if($orden->iva > 0) $ret.=' IVA'.' '.number_format($orden->iva,2);
                            if($orden->ieps > 0) $ret.=' IEPS'.' '.number_format($orden->ieps,2);
                            if($ret == '') $ret.='0';
                            echo $ret;
                            ?>
                        </td>

                    </tr>
                </table>
            </div>
            <div class="row" style="font-size: 7px !important; width: 98% !important;">
                <label><b>Sello CFDI:</b></label>
                <p style="text-align: justify;text-justify: inter-word;">{{chunk_split($tfd['SelloCFD'] ?? '', 150)}}</p>
            </div>
            <hr style="margin-top: -0.5rem !important;">
            <div class="row" style="font-size: 7px !important; width: 98% !important;margin-top: -1rem !important;">
                <label><b>Sello SAT:</b></label>
                {{chunk_split($tfd['SelloSAT'] ?? '', 150)}}
            </div>
            <hr>
            <div class="row" style="font-size: 7px !important; width: 98% !important;margin-top: -1rem !important;">
                <label><b>Cadena Original:</b></label>
                {{chunk_split($cadenaOrigen ?? '', 150)}}
            </div>
            <hr>
    </div>
    </body>
</html>
