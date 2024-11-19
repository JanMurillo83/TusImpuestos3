<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ConexionController;
use App\Models\Almacencfdis;
use Illuminate\Support\Facades\Storage;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use \CfdiUtils\SumasPagos20\Calculator;
use \CfdiUtils\SumasPagos20\Currencies;
use \CfdiUtils\SumasPagos20\PagosWriter;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use NahidulHasan\Html2pdf\Facades\Pdf;
use phpDocumentor\Reflection\Types\This;

class TimbradoController extends Controller
{
    public string $url = 'https://dev.facturaloplus.com/ws/servicio.do?wsdl';
    //public string $url = 'https://app.facturaloplus.com/ws/servicio.do?wsdl';
    public function TimbrarFactura($factura,$emisor,$receptor,$tipodoc):string
    {
	    $objConexion = new ConexionController($this->url);
        //-------------------------------------------
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('teams')->where('id',$emisor)->get();
        $recdata = DB::table('terceros')->where('id',$receptor)->get();

        if($tipodoc == "F")$facdata = DB::table('facturas')->where('id',$factura)->get();
        else $facdata = DB::table('notascreds')->where('id',$factura)->get();


        if($tipodoc == "F")$pardata = DB::table('par_facturas')->where('facturas_id',$factura)->get();
        else $pardata = DB::table('par_notascreds')->where('notascred_id',$factura)->get();

        $nopardata = count($pardata);
        $tido = "I";
        if($tipodoc == "F") $tido = "I";
        else $tido = "E";
        $csdpass = $emidata[0]->csdpass;
        $apikey = 'd653c0eee6664e099ead4a76d0f0e15d';
        $cerFile = storage_path('/app/public/'.$emidata[0]->csdcer);
        $keyFile = storage_path('/app/public/'.$emidata[0]->csdkey);
        $keyPEM = storage_path('/app/public/CSD/'.$emidata[0]->taxid.'.key.pem');
        $tmpxml =storage_path('/app/public/CSD/'.$recdata[0]->rfc.'.xml');
        $tmpxml1 =storage_path('/app/public/CSD/'.$recdata[0]->rfc.'_Tim1.xml');
        $tmpxml2 =storage_path('/app/public/CSD/'.$recdata[0]->rfc.'_Tim2.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        if (file_exists($tmpxml1)) {
            unlink($tmpxml1);
        }
        if (file_exists($tmpxml2)) {
            unlink($tmpxml2);
        }
        //file_put_contents($cerFile,$cerFileData);
        //file_put_contents($keyFile,$keyFileData);
        //-------------------------------------------------------
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $serie_d = DB::table('seriesfacs')->where('id',$facdata[0]->serie)->get();
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $comprobanteAtributos = [
            'Serie' => $serie_d[0]->serie,
            'Folio' => $facdata[0]->folio,
            'CondicionesDePago'=>$facdata[0]->condiciones ?? "CONTADO",
            'SubTotal'=>$facdata[0]->subtotal,
            'Moneda'=>"MXN",
            'TipoCambio'=>"1",
            'Total'=>$facdata[0]->total,
            'TipoDeComprobante'=>$tido,
            'Exportacion'=>"01",
            'MetodoPago'=>$facdata[0]->forma,
            'LugarExpedicion'=>$emidata[0]->codigopos,
            'Fecha'=>$fechahora,
            'FormaPago'=>$facdata[0]->metodo
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();
        if($tipodoc == "N"){
            $atrib = ['TipoRelacion'=>"01"];
            $rel01 = $comprobante->addCfdiRelacionados($atrib);
            $rel01->addCfdiRelacionado([
                'UUID'=> $facdata[0]->relacionado
            ]);
        }
        $comprobante->addEmisor([
            'Rfc'=>$emidata[0]->taxid,
            'Nombre'=>$emidata[0]->name,
            'RegimenFiscal'=>$emidata[0]->regimen
        ]);

        $comprobante->addReceptor([
            'Rfc'=>$recdata[0]->rfc,
            'Nombre'=>$recdata[0]->nombre,
            'RegimenFiscalReceptor'=>$recdata[0]->regimen,
            'DomicilioFiscalReceptor'=>$recdata[0]->codigopos,
            'UsoCFDI'=>$facdata[0]->usocfdi
        ]);



        for($i=0;$i<$nopardata;$i++)
        {
            $concepto1 = $comprobante->addConcepto([
                'ClaveProdServ'=>$pardata[$i]->cvesat,
                'Cantidad'=>number_format($pardata[$i]->cant, 2, '.', ''),
                'ClaveUnidad'=>$pardata[$i]->unisat,
                'ObjetoImp'=>"02",
                'Descripcion'=>$pardata[$i]->descripcion,
                'ValorUnitario'=>number_format($pardata[$i]->precio, 2, '.', ''),
                'Importe'=>number_format($pardata[$i]->subtotal, 2, '.', '')
            ]);
            $concepto1->addTraslado([
                'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                'Impuesto'=>"002",
                'TipoFactor'=>"Tasa",
                'TasaOCuota'=>number_format(floatval($pardata[$i]->por_im1)*0.01, 6, '.', ''),
                'Importe'=>number_format($pardata[$i]->impuesto1, 6, '.', '')
            ]);
            if($pardata[$i]->por_im4 != 0)
            {
                $concepto1->addTraslado([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"003",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_im4)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->impuesto4, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_im2 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"002",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_im2)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->impuesto2, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_im3 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"001",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_im3)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->impuesto3, 6, '.', '')
                ]);
            }

        }
        $creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml1);
        $asserts = $creator->validate();
        /*dd($asserts->hasErrors());
        if ($asserts->hasErrors()) {
            $var1 = $asserts->get('XSD01')->getExplanation();
            $ress = [
                'codigo'=>"300",
                'mensaje'=>$var1
            ];
            dd($var1);
            return json_encode($ress);
        }*/
        $creator->saveXml($tmpxml2);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }

    public function TimbrarTraslado($factura,$emisor,$receptor,$tipodoc):string
    {

	    $objConexion = new ConexionController($this->url);
        //-------------------------------------------
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('id',$emisor)->get();
        $recdata = DB::table('clientes')->where('id',$receptor)->get();
        $facdata = DB::table('traslados')->where('id',$factura)->get();
        $pardata = DB::table('par_traslados')->where('traslados_id',$factura)->get();
        $ubidata = DB::table('par_ubi_traslados')->where('traslados_id',$factura)->get();
        $unidata = DB::table('par_uni_traslados')->where('traslados_id',$factura)->get();
        $opedata = DB::table('par_ope_traslados')->where('traslados_id',$factura)->get();
        $merdata = DB::table('par_mer_traslados')->where('traslados_id',$factura)->get();

        $nopardata = count($pardata);
        $tido = "T";
        $csdpass = $emidata[0]->csdpass;
        $apikey = $emidata[0]->apik;
        $cerFile = storage_path('/app/public/'.$emidata[0]->cer);
        $keyFile = storage_path('/app/public/'.$emidata[0]->key);
        $keyPEM = storage_path('/app/public/'.$emidata[0]->rfc.'.key.pem');
        $tmpxml = storage_path('/app/public/'.$recdata[0]->rfc.'.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //file_put_contents($cerFile,$cerFileData);
        //file_put_contents($keyFile,$keyFileData);
        //-------------------------------------------------------
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $serie_d = DB::table('series_facs')->where('id',$facdata[0]->serie)->get();
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $comprobanteAtributos = [
            'Serie' => $serie_d[0]->serie,
            'Folio' => $facdata[0]->folio,
            //'CondicionesDePago'=>$facdata[0]->condiciones ?? "CONTADO",
            'SubTotal'=>0,
            'Moneda'=>"XXX",
            //'TipoCambio'=>"1",
            'Total'=>0,
            'TipoDeComprobante'=>$tido,
            'Exportacion'=>"01",
            //'MetodoPago'=>$facdata[0]->forma,
            'LugarExpedicion'=>$emidata[0]->codigo,
            'Fecha'=>$fechahora,
            //'FormaPago'=>$facdata[0]->metodo
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();

        $comprobante->addEmisor([
            'Rfc'=>$emidata[0]->rfc,
            'Nombre'=>$emidata[0]->razon,
            'RegimenFiscal'=>$emidata[0]->regimen
        ]);

        $comprobante->addReceptor([
            'Rfc'=>$emidata[0]->rfc,
            'Nombre'=>$emidata[0]->razon,
            'RegimenFiscalReceptor'=>$emidata[0]->regimen,
            'DomicilioFiscalReceptor'=>$emidata[0]->codigo,
            'UsoCFDI'=>'S01'
        ]);



        for($i=0;$i<$nopardata;$i++)
        {
            $comprobante->addConcepto([
                'ClaveProdServ'=>$pardata[$i]->cvesat,
                'Cantidad'=>number_format($pardata[$i]->cant, 2, '.', ''),
                'ClaveUnidad'=>$pardata[$i]->unisat,
                'ObjetoImp'=>"01",
                'Descripcion'=>$pardata[$i]->descripcion,
                'ValorUnitario'=>number_format($pardata[$i]->precio, 2, '.', ''),
                'Importe'=>number_format($pardata[$i]->subtotal, 2, '.', '')
            ])/*->addTraslado([
                'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                'Impuesto'=>"002",
                'TipoFactor'=>"Tasa",
                'TasaOCuota'=>"0.160000",
                'Importe'=>number_format($pardata[$i]->impuesto1, 6, '.', '')
            ])*/;
        }
        //-----------------------------------------------------------------------
        $salida = new DateTime($ubidata[0]->horasalida);
        $llegada = new DateTime($ubidata[0]->horallegada);
        $fesa = $salida->format('Y-m-d');
        $hosa = $salida->format('H:i:s');
        $fehosa = $fesa.'T'.$hosa;
        $fell = $llegada->format('Y-m-d');
        $holl = $llegada->format('H:i:s');
        $feholl = $fell.'T'.$holl;

        $cartaPorte = new \CfdiUtils\Elements\CartaPorte31\CartaPorte([
           'Version'=>"3.1",'TranspInternac'=>"No",
           'TotalDistRec'=>"1",'IdCCP'=>'CCCa'.Self::generateUuidv4()
        ]);
        $ubicaciones = $cartaPorte->getUbicaciones();
        $mercancias = $cartaPorte->getMercancias();
        $figuras = $cartaPorte->getFiguraTransporte();
        //$cartaPorte->addMercancias(['PesoBrutoTotal'=>"1.0",'UnidadPeso'=>"XBX",'NumTotalMercancias'=>"1"]);
        $ubicaciones->addUbicacion([
           'TipoUbicacion'=>'Origen',
           'IDUbicacion'=>$ubidata[0]->clave_or,
           'RFCRemitenteDestinatario'=>$ubidata[0]->rfc_or,
           'NombreRemitenteDestinatario'=>$ubidata[0]->nombre_or,
           'FechaHoraSalidaLlegada'=>$fehosa
        ])->addDomicilio([
           'Calle'=>$ubidata[0]->calle_or,
           'NumeroExterior'=>$ubidata[0]->exte_or,
           'NumeroInterior'=>$ubidata[0]->inte_or,
           'Colonia'=>$ubidata[0]->colonia_or,
           'Municipio'=>$ubidata[0]->municipio_or,
           'Estado'=>$ubidata[0]->estado_or,
           'Pais'=>$ubidata[0]->pais_or,
           'CodigoPostal'=>$ubidata[0]->codigo_or
        ]);
        $ubicaciones->addUbicacion([
           'TipoUbicacion'=>'Destino',
           'IDUbicacion'=>$ubidata[0]->clave_de,
           'RFCRemitenteDestinatario'=>$ubidata[0]->rfc_de,
           'NombreRemitenteDestinatario'=>$ubidata[0]->nombre_de,
           'FechaHoraSalidaLlegada'=>$feholl,
           'DistanciaRecorrida'=>'1'
        ])->addDomicilio([
           'Calle'=>$ubidata[0]->calle_de,
           'NumeroExterior'=>$ubidata[0]->exte_de,
           'NumeroInterior'=>$ubidata[0]->inte_de,
           'Colonia'=>$ubidata[0]->colonia_de,
           'Municipio'=>$ubidata[0]->municipio_de,
           'Estado'=>$ubidata[0]->estado_de,
           'Pais'=>$ubidata[0]->pais_de,
           'CodigoPostal'=>$ubidata[0]->codigo_de
        ]);
        $cartaPorte->addMercancias(['PesoBrutoTotal'=>intval($merdata[0]->peso),'UnidadPeso'=>"XBX",
        'NumTotalMercancias'=>"1"])->addMercancia([
           'BienesTransp'=>$merdata[0]->clave,
           'Descripcion'=>$merdata[0]->descripcion,
           'Cantidad'=>floatval($merdata[0]->peso),
           'ClaveUnidad'=>$merdata[0]->unidad,
           //'MaterialPeligroso'=>'No',
           'PesoEnKg'=>intval($merdata[0]->peso)
        ])->addCantidadTransporta([
           'Cantidad'=>intval($merdata[0]->peso),
           'IDOrigen'=>$ubidata[0]->clave_or,
           'IDDestino'=>$ubidata[0]->clave_de
        ]);
        $autotransporte = $mercancias->getAutotransporte();
        $mercancias->addAutotransporte([
           'PermSCT'=>$unidata[0]->permstc,
           'NumPermisoSCT'=>$unidata[0]->numperm
        ])->addIdentificacionVehicular([
           'ConfigVehicular'=>$unidata[0]->config,
           'PesoBrutoVehicular'=>$unidata[0]->peso,
           'PlacaVM'=>$unidata[0]->placa,
           'AnioModeloVM'=>$unidata[0]->anio
        ]);
        $autotransporte->addSeguros([
           'AseguraRespCivil'=>$unidata[0]->seguro,
           'PolizaRespCivil'=>$unidata[0]->poliza
        ]);
        if($unidata[0]->placas_rem != '')
        {
           $autotransporte->addRemolques([
               'SubTipoRem'=>$unidata[0]->tipo_rem,
               'Placa'=>$unidata[0]->placas_rem
           ]);
        }
        $figuras->addTiposFigura([
           'TipoFigura'=>$opedata[0]->tipo,
           'RFCFigura'=>$opedata[0]->rfc,
           'NumLicencia'=>$opedata[0]->licencia,
           'NombreFigura'=>$opedata[0]->nombre
        ]);
        $comprobante->addComplemento($cartaPorte);
        //-----------------------------------------------------------------------
        //$creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $asserts = $creator->validate();
        if ($asserts->hasErrors()) {
            $var1 = $asserts->get('XSD01')->getExplanation();
            $ress = [
                'codigo'=>"300",
                'mensaje'=>$var1
            ];
            return json_encode($ress);
        }
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }

    public function TimbrarIngreso($factura,$emisor,$receptor,$tipodoc):string
    {

	    $objConexion = new ConexionController($this->url);
        //-------------------------------------------
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('id',$emisor)->get();
        $recdata = DB::table('clientes')->where('id',$receptor)->get();
        $facdata = DB::table('cartas')->where('id',$factura)->get();
        $pardata = DB::table('par_cartas')->where('cartas_id',$factura)->get();
        $ubidata = DB::table('par_ubi_cartas')->where('cartas_id',$factura)->get();
        $unidata = DB::table('par_uni_cartas')->where('cartas_id',$factura)->get();
        $opedata = DB::table('par_ope_cartas')->where('cartas_id',$factura)->get();
        $merdata = DB::table('par_mer_cartas')->where('cartas_id',$factura)->get();

        $nopardata = count($pardata);
        $tido = "I";
        $csdpass = $emidata[0]->csdpass;
        $apikey = $emidata[0]->apik;
        $cerFile = storage_path('/app/public/'.$emidata[0]->cer);
        $keyFile = storage_path('/app/public/'.$emidata[0]->key);
        $keyPEM = storage_path('/app/public/'.$emidata[0]->rfc.'.key.pem');
        $tmpxml = storage_path('/app/public/'.$recdata[0]->rfc.'.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //file_put_contents($cerFile,$cerFileData);
        //file_put_contents($keyFile,$keyFileData);
        //-------------------------------------------------------
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $serie_d = DB::table('series_facs')->where('id',$facdata[0]->serie)->get();
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $comprobanteAtributos = [
            'Serie' => $serie_d[0]->serie,
            'Folio' => $facdata[0]->folio,
            'CondicionesDePago'=>$facdata[0]->condiciones ?? "CONTADO",
            'SubTotal'=>$facdata[0]->subtotal,
            'Moneda'=>"MXN",
            'TipoCambio'=>"1",
            'Total'=>$facdata[0]->total,
            'TipoDeComprobante'=>$tido,
            'Exportacion'=>"01",
            'MetodoPago'=>$facdata[0]->forma,
            'LugarExpedicion'=>$emidata[0]->codigo,
            'Fecha'=>$fechahora,
            'FormaPago'=>$facdata[0]->metodo
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();

        $comprobante->addEmisor([
            'Rfc'=>$emidata[0]->rfc,
            'Nombre'=>$emidata[0]->razon,
            'RegimenFiscal'=>$emidata[0]->regimen
        ]);

        $comprobante->addReceptor([
            'Rfc'=>$recdata[0]->rfc,
            'Nombre'=>$recdata[0]->razon,
            'RegimenFiscalReceptor'=>$recdata[0]->regimen,
            'DomicilioFiscalReceptor'=>$recdata[0]->codigo,
            'UsoCFDI'=>$facdata[0]->usocfdi
        ]);

        for($i=0;$i<$nopardata;$i++)
        {
            $comprobante->addConcepto([
                'ClaveProdServ'=>$pardata[$i]->cvesat,
                'Cantidad'=>number_format($pardata[$i]->cant, 2, '.', ''),
                'ClaveUnidad'=>$pardata[$i]->unisat,
                'ObjetoImp'=>"02",
                'Descripcion'=>$pardata[$i]->descripcion,
                'ValorUnitario'=>number_format($pardata[$i]->precio, 2, '.', ''),
                'Importe'=>number_format($pardata[$i]->subtotal, 2, '.', '')
            ])->addTraslado([
                'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                'Impuesto'=>"002",
                'TipoFactor'=>"Tasa",
                'TasaOCuota'=>"0.160000",
                'Importe'=>number_format($pardata[$i]->impuesto1, 6, '.', '')
            ]);
        }
         //-----------------------------------------------------------------------
         $salida = new DateTime($ubidata[0]->horasalida);
         $llegada = new DateTime($ubidata[0]->horallegada);
         $fesa = $salida->format('Y-m-d');
         $hosa = $salida->format('H:i:s');
         $fehosa = $fesa.'T'.$hosa;
         $fell = $llegada->format('Y-m-d');
         $holl = $llegada->format('H:i:s');
         $feholl = $fell.'T'.$holl;

         $cartaPorte = new \CfdiUtils\Elements\CartaPorte31\CartaPorte([
            'Version'=>"3.1",'TranspInternac'=>"No",
            'TotalDistRec'=>"1",'IdCCP'=>'CCCa'.Self::generateUuidv4()
         ]);
         $ubicaciones = $cartaPorte->getUbicaciones();
         $mercancias = $cartaPorte->getMercancias();
         $figuras = $cartaPorte->getFiguraTransporte();
         //$cartaPorte->addMercancias(['PesoBrutoTotal'=>"1.0",'UnidadPeso'=>"XBX",'NumTotalMercancias'=>"1"]);
         $ubicaciones->addUbicacion([
            'TipoUbicacion'=>'Origen',
            'IDUbicacion'=>$ubidata[0]->clave_or,
            'RFCRemitenteDestinatario'=>$ubidata[0]->rfc_or,
            'NombreRemitenteDestinatario'=>$ubidata[0]->nombre_or,
            'FechaHoraSalidaLlegada'=>$fehosa
         ])->addDomicilio([
            'Calle'=>$ubidata[0]->calle_or,
            'NumeroExterior'=>$ubidata[0]->exte_or,
            'NumeroInterior'=>$ubidata[0]->inte_or,
            'Colonia'=>$ubidata[0]->colonia_or,
            'Municipio'=>$ubidata[0]->municipio_or,
            'Estado'=>$ubidata[0]->estado_or,
            'Pais'=>$ubidata[0]->pais_or,
            'CodigoPostal'=>$ubidata[0]->codigo_or
         ]);
         $ubicaciones->addUbicacion([
            'TipoUbicacion'=>'Destino',
            'IDUbicacion'=>$ubidata[0]->clave_de,
            'RFCRemitenteDestinatario'=>$ubidata[0]->rfc_de,
            'NombreRemitenteDestinatario'=>$ubidata[0]->nombre_de,
            'FechaHoraSalidaLlegada'=>$feholl,
            'DistanciaRecorrida'=>'1'
         ])->addDomicilio([
            'Calle'=>$ubidata[0]->calle_de,
            'NumeroExterior'=>$ubidata[0]->exte_de,
            'NumeroInterior'=>$ubidata[0]->inte_de,
            'Colonia'=>$ubidata[0]->colonia_de,
            'Municipio'=>$ubidata[0]->municipio_de,
            'Estado'=>$ubidata[0]->estado_de,
            'Pais'=>$ubidata[0]->pais_de,
            'CodigoPostal'=>$ubidata[0]->codigo_de
         ]);
         $cartaPorte->addMercancias(['PesoBrutoTotal'=>intval($merdata[0]->peso),'UnidadPeso'=>"XBX",
         'NumTotalMercancias'=>"1"])->addMercancia([
            'BienesTransp'=>$merdata[0]->clave,
            'Descripcion'=>$merdata[0]->descripcion,
            'Cantidad'=>floatval($merdata[0]->peso),
            'ClaveUnidad'=>$merdata[0]->unidad,
            //'MaterialPeligroso'=>'No',
            'PesoEnKg'=>intval($merdata[0]->peso)
         ])->addCantidadTransporta([
            'Cantidad'=>intval($merdata[0]->peso),
            'IDOrigen'=>$ubidata[0]->clave_or,
            'IDDestino'=>$ubidata[0]->clave_de
         ]);
         $autotransporte = $mercancias->getAutotransporte();
         $mercancias->addAutotransporte([
            'PermSCT'=>$unidata[0]->permstc,
            'NumPermisoSCT'=>$unidata[0]->numperm
         ])->addIdentificacionVehicular([
            'ConfigVehicular'=>$unidata[0]->config,
            'PesoBrutoVehicular'=>$unidata[0]->peso,
            'PlacaVM'=>$unidata[0]->placa,
            'AnioModeloVM'=>$unidata[0]->anio
         ]);
         $autotransporte->addSeguros([
            'AseguraRespCivil'=>$unidata[0]->seguro,
            'PolizaRespCivil'=>$unidata[0]->poliza
         ]);
         if($unidata[0]->placas_rem != '')
         {
            $autotransporte->addRemolques([
                'SubTipoRem'=>$unidata[0]->tipo_rem,
                'Placa'=>$unidata[0]->placas_rem
            ]);
         }
         $figuras->addTiposFigura([
            'TipoFigura'=>$opedata[0]->tipo,
            'RFCFigura'=>$opedata[0]->rfc,
            'NumLicencia'=>$opedata[0]->licencia,
            'NombreFigura'=>$opedata[0]->nombre
         ]);
         $comprobante->addComplemento($cartaPorte);
         //-----------------------------------------------------------------------
        $creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $asserts = $creator->validate();
        if ($asserts->hasErrors()) {
            $var1 = $asserts->get('XSD01')->getExplanation();
            $ress = [
                'codigo'=>"300",
                'mensaje'=>$var1
            ];
            return json_encode($ress);
        }
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }

    public function actualiza_fac_tim($factura,$cfdi_con,$tipodoc):string
    {
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $idfactura = $factura;
        $datos_xml = $cfdi_con;
        $uuid = "";
        $cfdi = \CfdiUtils\Cfdi::newFromString($datos_xml);
        $comprobante = $cfdi->getNode();
        $emisor = $comprobante->searchNode('cfdi:Emisor');
        $receptor = $comprobante->searchNode('cfdi:Receptor');
        $impuestos = $comprobante->searchNode('cfdi:Impuestos');
        //dd($comprobante->searchNode('cfdi:Emisor'));
        $complemento = $cfdi->getNode();
        $tfd = $complemento->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
        if (null === $tfd) {
            echo 'No existe el timbre fiscal digital';
        } else {
            $uuid= $tfd['UUID'];
        }
        if($tipodoc == "F"){
            DB::table('facturas')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "N"){
            DB::table('notascreds')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "P"){
            DB::table('pagos')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "R"){
            DB::table('retenciones')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "C"){
            DB::table('cartas')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        if($tipodoc == "T"){
            DB::table('traslados')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        //----------------------------------------------------------------
        Almacencfdis::insert([
            'Serie' =>$comprobante['Serie'],
            'Folio'=>$comprobante['Folio'],
            'Version'=>$comprobante['Version'],
            'Fecha'=>$comprobante['Fecha'],
            'Moneda'=>$comprobante['Moneda'],
            'TipoDeComprobante'=>$comprobante['TipoDeComprobante'],
            'MetodoPago'=>$comprobante['MetodoPago'],
            'Emisor_Rfc'=>$emisor['Rfc'],
            'Emisor_Nombre'=>$emisor['Nombre'],
            'Emisor_RegimenFiscal'=>$emisor['RegimenFiscal'],
            'Receptor_Rfc'=>$receptor['Rfc'],
            'Receptor_Nombre'=>$receptor['Nombre'],
            'Receptor_RegimenFiscal'=>$receptor['RegimenFiscal'],
            'UUID'=>$tfd['UUID'],
            'Total'=>$comprobante['Total'],
            'SubTotal'=>$comprobante['SubTotal'],
            'TipoCambio'=> $comprobante['TipoCambio'],
            'TotalImpuestosTrasladados'=>$impuestos['TotalImpuestosTrasladados'] ?? 0,
            'TotalImpuestosRetenidos'=>$impuestos['TotalImpuestosRetenidos'] ?? 0,
            'content'=>$datos_xml,
            'user_tax'=>$emisor['Rfc'],
            'used'=>'SI',
            'xml_type'=>'Emitidos',
            'periodo'=>Filament::getTenant()->periodo,
            'ejercicio'=>Filament::getTenant()->ejercicio,
            'team_id'=>Filament::getTenant()->id
        ]);
        //----------------------------------------------------------------
        return $uuid;
    }

    public function generateUuidv4()
    {
        return sprintf('%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,
        // 48 bits for "node"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }


    public function actualiza_fac_tim_ret($factura,$cfdi_con,$tipodoc):string
    {
        $idfactura = $factura;
        $datos_xml = $cfdi_con;
        $uuid = "";
        $cfdi = \CfdiUtils\Retenciones\Retenciones::newFromString($datos_xml);
        $complemento = $cfdi->getNode();
        $tfd = $complemento->searchNode('retenciones:Complemento', 'tfd:TimbreFiscalDigital');
        if (null === $tfd) {
            echo 'No existe el timbre fiscal digital';
        } else {
            $uuid= $tfd['UUID'];
        }
        if($tipodoc == "R"){
            DB::table('retenciones')->where('id',$idfactura)->update([
                'uuid'=>$uuid,
                'estado' => 'Timbrada'
            ]);
        }
        return $uuid;
    }

    public function genera_pdf($factura):string
    {
        $xml = $factura;
        if (file_exists('output.pdf')) {
            unlink('output.pdf');
        }
        $xml = \PhpCfdi\CfdiCleaner\Cleaner::staticClean($xml);
        $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xml);
        $cfdiData = (new \PhpCfdi\CfdiToPdf\CfdiDataBuilder())->build($comprobante);
        $converter = new \PhpCfdi\CfdiToPdf\Converter(
            new \PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder()
        );
        $converter->createPdfAs($cfdiData, 'output.pdf');
        $datos_cfdi = base64_encode(file_get_contents('output.pdf'));
        return $datos_cfdi;
    }

    public function genera_pdf_ret($factura):string
    {
        $xml = Pdf::generatePdf($factura);
        if (file_exists('output.pdf')) {
            unlink('output.pdf');
        }
        file_put_contents('output.pdf', $xml);
        $datos_cfdi = base64_encode(file_get_contents('output.pdf'));
        return $datos_cfdi;
    }

    public function TimbrarPagos($factura,$emisor,$receptor):string
    {

	    $objConexion = new ConexionController($this->url);
        //-------------------------------------------
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('id',$emisor)->get();
        $recdata = DB::table('clientes')->where('id',$receptor)->get();
        $facdata = DB::table('pagos')->where('id',$factura)->get();
        $pardata = DB::table('par_pagos')->where('pagos_id',$factura)->get();
        $fac_id = $pardata[0]->uuidrel;
        $antdata = DB::table('facturas')->where('id',$fac_id)->get();
        $tido = "P";
        $csdpass = $emidata[0]->csdpass;
        $apikey = $emidata[0]->apik;
        $cerFile = storage_path('/app/public/'.$emidata[0]->cer);
        $keyFile = storage_path('/app/public/'.$emidata[0]->key);
        $keyPEM = storage_path('/app/public/'.$emidata[0]->rfc.'.key.pem');
        $tmpxml = storage_path('/app/public/'.$recdata[0]->rfc.'.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //-------------------------------------------------------
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $serie_d = DB::table('series_facs')->where('id',$facdata[0]->serie)->get();
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $comprobanteAtributos = [
            'Serie' => $serie_d[0]->serie,
            'Folio' => $facdata[0]->folio,
            'SubTotal'=>0,
            'Moneda'=>"XXX",
            'Total'=>0,
            'TipoDeComprobante'=>$tido,
            'Exportacion'=>"01",
            'LugarExpedicion'=>$emidata[0]->codigo,
            'Fecha'=>$fechahora
        ];

        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();
        $comprobante->addEmisor([
            'Rfc'=>$emidata[0]->rfc,
            'Nombre'=>$emidata[0]->razon,
            'RegimenFiscal'=>$emidata[0]->regimen
        ]);
        $comprobante->addReceptor([
            'Rfc'=>$recdata[0]->rfc,
            'Nombre'=>$recdata[0]->razon,
            'RegimenFiscalReceptor'=>$recdata[0]->regimen,
            'DomicilioFiscalReceptor'=>$recdata[0]->codigo,
            'UsoCFDI'=>$facdata[0]->usocfdi
        ]);

        $comprobante->addConcepto([
            'ClaveProdServ'=>'84111506',
            'Cantidad'=>1,
            'ClaveUnidad'=>'ACT',
            'ObjetoImp'=>"01",
            'Descripcion'=>'Pago',
            'ValorUnitario'=>0,
            'Importe'=>0
        ]);

        $Pagos = new \CfdiUtils\Elements\Pagos20\Pagos();
        $Pagos->addTotales([
            'TotalTrasladosBaseIVA16' => $facdata[0]->subtotal,
            'TotalTrasladosImpuestoIVA16' => $facdata[0]->iva,
            'MontoTotalPagos'=>$facdata[0]->total
        ]);
        $Pagos->addPago([
            'FechaPago'=>$fechahora,
            'FormaDePagoP'=>$facdata[0]->forma,
            'MonedaP'=>"MXN",
            'TipoCambioP'=>"1",
            'Monto'=>floatval($facdata[0]->total)
        ])->addDoctoRelacionado([
            'IdDocumento'=>$antdata[0]->uuid,
            'MonedaDR'=>"MXN",
            'EquivalenciaDR'=>"1",
            'NumParcialidad'=>"1",
            'ImpSaldoAnt'=>floatval($pardata[0]->saldoant),
            'ImpPagado'=>floatval($pardata[0]->imppagado),
            'ImpSaldoInsoluto'=>floatval($pardata[0]->insoluto),
            'ObjetoImpDR'=>"02"
        ])->addImpuestosDR()
        ->addTrasladosDR()
        ->addTrasladoDR([
            'BaseDR'=>floatval($antdata[0]->subtotal),
            'ImpuestoDR'=>"002",
            'TipoFactorDR'=>"Tasa",
            'TasaOCuotaDR'=>"0.160000",
            'ImporteDR'=> floatval($antdata[0]->impuesto1)
        ]);
        PagosWriter::calculateAndPut($Pagos);
        $comprobante->addComplemento($Pagos);

        //$creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $asserts = $creator->validate();
        if ($asserts->hasErrors()) {
            $var1 = $asserts->get('XSD01')->getExplanation();
            $ress = [
                'codigo'=>"300",
                'mensaje'=>$var1
            ];
            return json_encode($ress);
        }
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }

    public function TimbrarRetenciones($factura,$emisor,$receptor):string
    {

	    $objConexion = new ConexionController($this->url);
        //-------------------------------------------
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('id',$emisor)->get();
        $recdata = DB::table('clientes')->where('id',$receptor)->get();
        $facdata = DB::table('retenciones')->where('id',$factura)->get();
        $csdpass = $emidata[0]->csdpass;
        $apikey = $emidata[0]->apik;
        $cerFile = storage_path('/app/public/'.$emidata[0]->cer);
        $keyFile = storage_path('/app/public/'.$emidata[0]->key);
        $keyPEM = storage_path('/app/public/'.$emidata[0]->rfc.'.key.pem');
        $tmpxml = storage_path('/app/public/'.$recdata[0]->rfc.'.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //-------------------------------------------------------
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $serie_d = DB::table('series_facs')->where('id',$facdata[0]->serie)->get();
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $creator = new \CfdiUtils\Retenciones\RetencionesCreator20([
            'FechaExp' => $fechahora,
            'CveRetenc' => $facdata[0]->cve_reten, // Dividendos o utilidades distribuidos
            'LugarExpRetenc' => $emidata[0]->codigo,
        ]);

        // retenciones es un objeto de ayuda, similar a Comprobante
        $retenciones = $creator->retenciones();

        /*$retenciones->addCfdiRetenRelacionados([
            'TipoRelacion' => '01',
            'UUID' => '1474b7d3-61fc-41c4-a8b8-3f22e1161bb4',
        ]);*/
        $retenciones->addEmisor([
            'RfcE' => $emidata[0]->rfc,
            'NomDenRazSocE' => $emidata[0]->razon,
            'RegimenFiscalE' => $emidata[0]->regimen
        ]);

        if($facdata[0]->recepnac == 'Nacional')
        {
            $retenciones->getReceptor()->addNacional([
                'RfcR' => $recdata[0]->rfc,
                'NomDenRazSocR' => $recdata[0]->razon,
                'DomicilioFiscalR' => $recdata[0]->codigo
            ]);
        }else{
            $retenciones->getReceptor()->addExtranjero([
                'NumRegIdTribR' => $recdata[0]->rfc,
                'NomDenRazSocR' => $recdata[0]->razon,
            ]);
        }
        $retenciones->addPeriodo(['MesIni' => $facdata[0]->mes_ini, 'MesFin' => $facdata[0]->mes_fin, 'Ejercicio' => $facdata[0]->ejercicio]);
        $retenciones->addTotales([
            'MontoTotOperacion' => $facdata[0]->monto,
            'MontoTotGrav' => $facdata[0]->gravable,
            'MontoTotExent' => $facdata[0]->exento,
            'MontoTotRet' => $facdata[0]->retencion
        ]);
        $retenciones->addImpRetenidos([
            'BaseRet' => $facdata[0]->gravable,
            'ImpuestoRet' => $facdata[0]->impuesto, // same as CFDI
            'TipoPagoRet' => $facdata[0]->tiporet,
            'MontoRet' => $facdata[0]->retencion,
        ]);

        /*$dividendos = new \CfdiUtils\Elements\Dividendos10\Dividendos();
        $dividendos->addDividOUtil([
            'CveTipDivOUtil' => '06', // 06 - Proviene de CUFIN al 31 de diciembre 2013
            'MontISRAcredRetMexico' => '0',
            'MontISRAcredRetExtranjero' => '0',
            'MontRetExtDivExt' => '0',
            'TipoSocDistrDiv' => 'Sociedad Nacional',
            'MontISRAcredNal' => '0',
            'MontDivAcumNal' => '0',
            'MontDivAcumExt' => '0',
        ]);
        $retenciones->addComplemento($dividendos);*/

        // poner certificado y sellar el precfdi, después de sellar no debes hacer cambios
        $creator->putCertificado($certificado);
        $creator->addSello($keyForFinkOk, $csdpass);

        // método de ayuda para mover las declaraciones de espacios de nombre al nodo raíz
        $creator->moveSatDefinitionsToRetenciones();
        $cfdi = $creator->asXml();
        file_put_contents($tmpxml, $creator->asXml());
        // Asserts contendrá el resultado de la validación
        $asserts = $creator->validate();
        if ($asserts->hasErrors()) {
            $var1 = $asserts->get('XSD01')->getExplanation();
            $ress = [
                'codigo'=>"300",
                'mensaje'=>$var1
            ];
            return json_encode($ress);
        }
        $resultado = $objConexion->operacion_timbrarRet($apikey, $cfdi);
        return $resultado;

    }
}
