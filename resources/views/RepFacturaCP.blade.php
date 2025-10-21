<?php
    require_once(resource_path('/views/qrcode.php'));
    use \League\Plates\Template\Template as Thos;
    use Filament\Facades\Filament;
    $orden = DB::table('pagos')->where('id',$idorden)->first();
    $partidas = DB::table('par_pagos')->where('pagos_id',$idorden)->get();
    $prove = DB::table('clientes')->where('id',$orden->cve_clie)->first();
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
        $ComPagos = $comprobante->complemento->Pagos;
        $PagosTotales = $ComPagos->Totales;
        $DetPagos = $ComPagos('Pago');
        /*dd($Det_Pagos);
        $DetPagos = $Det_Pagos('DoctoRelacionado');
        $iterator = new RecursiveArrayIterator($DetPagos);
        foreach ($iterator->getChildren() as $key => $value) {
            dd("$key : $value\n");
        }
        dd($DetPagos);*/
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

                    <!--<label><b>FORMA DE PAGO:</b></label><label></label><br>
                    <label><b>METODO DE PAGO:</b></label><label></label>-->
                </div>
                <div class="col-4" style="font-size: 10px !important;">
                    <label><b>TIPO DE COMPROBANTE:</b></label><label> P - Pagos</label><br>
                    <label><b>FOLIO FISCAL:</b></label><label>{{$tfd['UUID']?? ''}}</label><br>
                    <label><b>NUMERO DE SERIE DEL CERTIFICADO DEL SAT:</b></label><label>{{$tfd['NoCertificadoSAT'] ?? ''}}</label><br>
                    <label><b>FECHA Y HORA DE CERTIFICACIÓN:</b></label><label>{{$comprobante['Fecha'] ?? ''}}</label><br>
                    <label><b>NUMERO DE SERIE DEL CSD DEL EMISOR:</b></label><label>{{$comprobante['NoCertificado'] ?? ''}}</label><br>
                    <label><b>CLAVE CONFIRMACIÓN:</b></label>
                </div>
                <div class="col-2" style="font-size: 10px !important;">
                    <label><b>COMPROBANTE FOLIO:</b></label> <label>{{$orden->serie.$orden->folio}}</label><br>
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
                    <label>{{$prove->nombre}}</label><br>
                    <label>{{$prove->rfc}}</label><br>
                    <label><b>RESIDENCIA FISCAL:</b></label><label>MEX</label><br>
                    <label><b>NumRegIdTrib:</b></label><br>
                    <label><b>USO CFDI:</b></label><label>{{$orden->usocfdi}}</label><br>
                </div>
                <!--<div class="col-2" style="font-size: 10px !important;">
                    <h6>NOTAS:</h6>
                    <label></label>
                </div>-->
            </div>
            <!--Row1-->
            <hr>
            <div class="row" style="font-size: 10px !important;">
                <h4>Totales</h4>
                <table style="width: 98% !important;">
                    <tr>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">MONTO PAGADO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{'$ '.number_format($PagosTotales['MontoTotalPagos'],2)}}</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">BASE IVA 16%</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{'$ '.number_format($PagosTotales['TotalTrasladosBaseIVA16'],2)}}</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">IVA TRASLADADO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{'$ '.number_format($PagosTotales['TotalTrasladosImpuestoIVA16'],2)}}</th>
                    </tr>
                </table>
            </div>
            <hr>
            <div class="row" style="font-size: 10px !important;">
                <h4>Pagos</h4>
                <table style="width: 98% !important;">
                    <tr>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">FOLIO DOCUMENTO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">UUID DOCUMENTO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">MONEDA</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">T. DE CAMBIO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">FORMA DE PAGO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">SALDO ANTERIOR</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">IMPORTE PAGADO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">SALDO INSOLUTO</th>
                        <th style="font-weight: bold; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">FECHA DE PAGO</th>
                    </tr>
                    @foreach ($DetPagos as $DetallePago)
                        <?php
                            $doc_ants = $DetallePago('DoctoRelacionado');
                        ?>
                        @foreach($doc_ants as $doc_ant)
                        <?php
                            $factura = \App\Models\Facturas::where('uuid',$doc_ant['IdDocumento'])->first();
                            //dd($doc_ant);
                        ?>
                        <tr>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{$factura->docto ?? ''}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;">{{substr($doc_ant['IdDocumento'],0,14)}}<br>{{substr($doc_ant['IdDocumento'],14,10)}}<br>{{substr($doc_ant['IdDocumento'],24,strlen($doc_ant['IdDocumento']))}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{$doc_ant['MonedaDR']}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{$DetallePago['TipoCambioP']}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{$DetallePago['FormaDePagoP']}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{'$'.number_format($doc_ant['ImpSaldoAnt'],2)}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{'$'.number_format($doc_ant['ImpPagado'],2)}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center">{{'$'.number_format($doc_ant['ImpSaldoInsoluto'],2)}}</th>
                            <th style="font-weight: normal; border-style: solid; border-width: 2px; border-color: #1a1e21; text-align: center; align-content: center;">{{substr($DetallePago['FechaPago'],0,10)}}</th>
                        </tr>
                        @endforeach
                    @endforeach

                </table>
            </div>
            <div class="row" style="font-size: 8px !important; width: 98% !important; margin-top: 1rem !important;">
                <label><b>Sello CFDI:</b></label><br>
                <p style="text-align: justify;text-justify: inter-word;">{{chunk_split($tfd['SelloCFD'] ?? '', 150)}}</p>
            </div>
            <hr style="margin-top: -1rem !important;">
            <div class="row" style="font-size: 8px !important; width: 98% !important;margin-top: -1rem !important;">
                <label style="margin-top: -0.1rem !important;"><b>Sello SAT:</b></label>
                <p style="text-align: justify;text-justify: inter-word;">{{chunk_split($tfd['SelloSAT'] ?? '', 150)}}</p>
            </div>
            <hr style="margin-top: -1rem !important;">
            <div class="row" style="font-size: 8px !important; width: 98% !important; margin-top: -1rem !important;">
                <label style="margin-top: -0.1rem !important;"><b>Cadena Original:</b></label>
                <p style="font-size: 7px !important;">{{chunk_split($cadenaOrigen ?? '', 180)}}</p>
            </div>
            <hr style="margin-top: -1rem !important;">
    </div>
    </body>
</html>
