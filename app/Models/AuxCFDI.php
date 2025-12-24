<?php

namespace App\Models;

use App\Http\Controllers\MainChartsController;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class AuxCFDI extends Model
{
    use Sushi;
    public function getRows()
    {
        $almacencfdis = Almacencfdis::where('team_id',Filament::getTenant()->id)
            ->where('TipoDeComprobante','I')
            ->where('xml_type','Recibidos')
            ->get();
        $datos = [];
        foreach ($almacencfdis as $item)
        {
            $xml = \CfdiUtils\Cfdi::newFromString($item->content);
            $CFDI = $xml->getQuickReader();
            $emisor = $CFDI->Emisor;
            $emisor_nombre = $emisor['Nombre'];
            $emisor_rfc = $emisor['Rfc'];
            $receptor = $CFDI->Receptor;
            $receptor_nombre = $receptor['Nombre'];
            $receptor_rfc = $receptor['Rfc'];
            $fecha = substr($CFDI['Fecha'],0,10);
            $fecha = Carbon::create($fecha)->format('Y-m-d');
            $subtotal = floatval($CFDI['SubTotal']);
            $descuento = floatval($CFDI['Descuento']);
            $total = floatval($CFDI['Total']);
            $tipo_comprobante = $CFDI['TipoDeComprobante'];
            $serie = $CFDI['Serie'];
            $folio = $CFDI['Folio'];
            $documento = $serie.$folio;
            $moneda = $CFDI['Moneda'];
            $forma_pago = $CFDI['FormaPago'];
            $metodo_pago = $CFDI['MetodoPago'];
            $tipo_cambio = max(floatval($CFDI['TipoCambio']),1);
            $conceptos = $CFDI->Conceptos;
            $impuestos = $CFDI->Impuestos;
            $traslados = $impuestos['TotalImpuestosTrasladados'];
            $retenciones = $impuestos['TotalImpuestosRetenidos'];
            $uuid = $CFDI->Complemento->TimbreFiscalDigital['UUID'];
            $ImpuestosTraslados = $impuestos->Traslados;
            $iva = 0;
            $ieps = 0;
            $otro_impuesto = 0;
            if(floatval($traslados) > 0) {
                foreach ($ImpuestosTraslados() as $traslado) {
                    if ($traslado['impuesto'] == '002') $iva = floatval($traslado['importe']);
                    if ($traslado['impuesto'] == '003') $ieps = floatval($traslado['importe']);
                    if ($traslado['impuesto'] != '003' && $traslado['impuesto'] != '002') $otro_impuesto = floatval($traslado['importe']);
                }
            }
            $ImpuestosRetenciones = $impuestos->Retenciones;
            $ret_isr = 0;
            $ret_iva = 0;
            $ret_ieps = 0;
            $otra_ret = 0;
            if(floatval($retenciones) > 0) {
                foreach ($ImpuestosRetenciones() as $retencion) {
                    if ($retencion['impuesto'] == '001') $ret_isr = floatval($retencion['importe']);
                    if ($retencion['impuesto'] == '002') $ret_iva = floatval($retencion['importe']);
                    if ($retencion['impuesto'] == '003') $ret_ieps = floatval($retencion['importe']);
                    if ($retencion['impuesto'] != '001' && $retencion['impuesto'] != '002' && $retencion['impuesto'] != '003') $otra_ret = floatval($retencion['importe']);
                }
            }
            $datos [] =[
                'uuid'=>$uuid,
                'serie'=>$serie,
                'folio'=>$folio,
                'documento'=>$documento,
                'tipo_comprobante'=>$tipo_comprobante,
                'emisor_nombre'=>$emisor_nombre,
                'emisor_rfc'=>$emisor_rfc,
                'receptor_nombre'=>$receptor_nombre,
                'receptor_rfc'=>$receptor_rfc,
                'fecha'=>$fecha,
                'moneda'=>$moneda,
                'forma_pago'=>$forma_pago,
                'metodo_pago'=>$metodo_pago,
                'tipo_cambio'=>$tipo_cambio,
                'subtotal'=>$subtotal,
                'descuento'=>$descuento,
                'iva'=>$iva,
                'ieps'=>$ieps,
                'otro_impuesto'=>$otro_impuesto,
                'ret_isr'=>$ret_isr,
                'ret_iva'=>$ret_iva,
                'ret_ieps'=>$ret_ieps,
                'otra_ret'=>$otra_ret,
                'total'=>$total
            ];
        }
        return $datos;
    }
}
