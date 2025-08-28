<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimbradoController extends Controller
{
    //public string $url = 'https://dev.facturaloplus.com/ws/servicio.do?wsdl';
    public string $url = 'https://app.facturaloplus.com/ws/servicio.do?wsdl';
    public function TimbrarFactura($factura,$receptor):string
    {
	    $objConexion = new ConexionController($this->url);
        $tipodoc = 'F';
        //-------------------------------------------
        $datetime = new DateTime();
        $mex_zone = new DateTimeZone('America/Mexico_City');
        $datetime->setTimezone($mex_zone);
        $fecha = $datetime->format('Y-m-d');
        $hora = $datetime->format('H:i:s');
        $fechahora = $fecha.'T'.$hora;

        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $emidata = DB::table('datos_fiscales')->where('team_id',Filament::getTenant()->id)->first();
        $recdata = DB::table('clientes')->where('id',$receptor)->get();
        $facdata = DB::table('facturas')->where('id',$factura)->get();
        $pardata = DB::table('facturas_partidas')->where('facturas_id',$factura)->get();

        $nopardata = count($pardata);
        $tido = "I";
        $csdpass = $emidata->csdpass;
        $apikey = '18b88997a6d3461b82b7786e8a6c05ac';
        $cerFile = public_path('storage/'.$emidata->cer);
        $keyFile = public_path('storage/'.$emidata->key);
        $keyPEM = public_path('storage/'.$emidata->rfc.'.key.pem');
        $tmpxml =public_path('storage/TMPXMLFiles/'.$recdata[0]->rfc.'.xml');
        if (file_exists($keyPEM)) {
            unlink($keyPEM);
        }
        if (file_exists($tmpxml)) {
            unlink($tmpxml);
        }
        //-------------------------------------------------------        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        //dd($keyFile, $csdpass, $keyPEM, $csdpass);
        $openssl->derKeyProtect($keyFile, $csdpass, $keyPEM, $csdpass);
        $keyForFinkOk = $openssl->pemKeyProtectOut($keyPEM, $csdpass, $csdpass);
        //-------------------------------------------
        $certificado = new \CfdiUtils\Certificado\Certificado($cerFile);
        $comprobanteAtributos = [
            'Serie' => 'F',
            'Folio' => $facdata[0]->folio,
            'CondicionesDePago'=>$facdata[0]->condiciones ?? "CONTADO",
            'SubTotal'=>$facdata[0]->subtotal,
            'Moneda'=>"MXN",
            'TipoCambio'=>"1",
            'Total'=>$facdata[0]->total,
            'TipoDeComprobante'=>$tido,
            'Exportacion'=>"01",
            'MetodoPago'=>$facdata[0]->forma,
            'LugarExpedicion'=>$emidata->codigo,
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
            'Rfc'=>$emidata->rfc,
            'Nombre'=>$emidata->nombre,
            'RegimenFiscal'=>$emidata->regimen
        ]);

        $comprobante->addReceptor([
            'Rfc'=>$recdata[0]->rfc,
            'Nombre'=>$recdata[0]->nombre,
            'RegimenFiscalReceptor'=>$recdata[0]->regimen,
            'DomicilioFiscalReceptor'=>$recdata[0]->codigo,
            'UsoCFDI'=>$facdata[0]->uso
        ]);



        for($i=0;$i<$nopardata;$i++)
        {
            $concepto1 = $comprobante->addConcepto([
                'ClaveProdServ'=>$pardata[$i]->cvesat,
                'Cantidad'=>number_format($pardata[$i]->cant, 2, '.', ''),
                'ClaveUnidad'=>$pardata[$i]->unidad,
                'ObjetoImp'=>"02",
                'Descripcion'=>$pardata[$i]->descripcion,
                'ValorUnitario'=>number_format($pardata[$i]->precio, 2, '.', ''),
                'Importe'=>number_format($pardata[$i]->subtotal, 2, '.', '')
            ]);
            $concepto1->addTraslado([
                'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                'Impuesto'=>"002",
                'TipoFactor'=>"Tasa",
                'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp1)*0.01, 6, '.', ''),
                'Importe'=>number_format($pardata[$i]->iva, 6, '.', '')
            ]);
            if($pardata[$i]->por_imp4 != 0)
            {
                $concepto1->addTraslado([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"003",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp4)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->ieps, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_imp2 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"002",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp2)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->retiva, 6, '.', '')
                ]);
            }
            if($pardata[$i]->por_imp3 != 0)
            {
                $concepto1->addRetencion([
                    'Base'=>number_format($pardata[$i]->subtotal, 6, '.', ''),
                    'Impuesto'=>"001",
                    'TipoFactor'=>"Tasa",
                    'TasaOCuota'=>number_format(floatval($pardata[$i]->por_imp3)*0.01, 6, '.', ''),
                    'Importe'=>number_format($pardata[$i]->retisr, 6, '.', '')
                ]);
            }

        }
        $creator->addSumasConceptos(null, 2);
        $creator->addSello($keyForFinkOk, $csdpass);
        $creator->moveSatDefinitionsToComprobante();
        $creator->saveXml($tmpxml);
        $cfdi = $creator->asXml();
        $resultado = $objConexion->operacion_timbrar($apikey, $cfdi);
        return $resultado;
    }

    public function actualiza_fac_tim($factura,$cfdi_con):string
    {
        $tipodoc = 'F';
        $idfactura = $factura;
        $datos_xml = $cfdi_con;
        $uuid = "";
        $cfdi = \CfdiUtils\Cfdi::newFromString($datos_xml);
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
        return $uuid;
    }
}
