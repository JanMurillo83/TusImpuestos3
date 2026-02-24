<?php

namespace App\Filament\Clusters\tiadmin\Resources\FacturasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\FacturasResource;
use App\Http\Controllers\TimbradoController;
use App\Models\Clientes;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\DoctosRelacionados;
use App\Models\Facturas;
use App\Models\FacturasPartidas;
use App\Models\Pedidos;
use App\Models\PedidosPartidas;
use App\Models\Remisiones;
use App\Models\SeriesFacturas;
use App\Models\SurtidoInve;
use App\Models\Team;
use Carbon\Carbon;
use CfdiUtils\Cleaner\Cleaner;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class CreateFacturas extends CreateRecord
{
    protected static string $resource = FacturasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Obtener siguiente folio de forma segura justo antes de crear
        $serieId = intval($data['sel_serie']);
        $folioData = SeriesFacturas::obtenerSiguienteFolio($serieId);

        $data['serie'] = $folioData['serie'];
        $data['folio'] = $folioData['folio'];
        $data['docto'] = $folioData['docto'];

        return $data;
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Guardar')
            ->color(Color::Green)
            ->icon('fas-save');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->visible(false);
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cerrar')
            ->color(Color::Red)
            ->icon('fas-ban');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $record->refresh();
        $record->recalculatePartidasFromItemSchema();
        $record->recalculateTotalsFromPartidas();

        $cliente_d = Clientes::where('id', $record->clie)->first();
        $record->pendiente_pago = $record->total;
        $record->save();

        // Actualizar pendientes si viene de Cotización o Pedido
        $factura_partidas = FacturasPartidas::where('facturas_id', $record->id)->get();
        foreach ($factura_partidas as $fpartida) {
            if ($fpartida->cotizacion_partida_id) {
                $cpartida = CotizacionesPartidas::find($fpartida->cotizacion_partida_id);
                if ($cpartida) {
                    $cpartida->decrement('pendientes', $fpartida->cant);
                }
            }
            if ($fpartida->pedido_partida_id) {
                $ppartida = PedidosPartidas::find($fpartida->pedido_partida_id);
                if ($ppartida) {
                    $ppartida->decrement('pendientes', $fpartida->cant);
                }
            }
        }

        if ($record->cotizacion_id) {
            $pendientesTotales = CotizacionesPartidas::where('cotizaciones_id', $record->cotizacion_id)->sum('pendientes');
            $nuevoEstado = $pendientesTotales <= 0 ? 'Cerrada' : 'Parcial';
            Cotizaciones::where('id', $record->cotizacion_id)->update(['estado' => $nuevoEstado]);
        }
        if ($record->pedido_id) {
            $pendientesTotales = PedidosPartidas::where('pedidos_id', $record->pedido_id)->sum('pendientes');
            $nuevoEstado = $pendientesTotales <= 0 ? 'Cerrado' : 'Parcial';
            Pedidos::where('id', $record->pedido_id)->update(['estado' => $nuevoEstado]);
        }
        // Actualizar estado de remisión si existe
        if ($record->remision_id) {
            Remisiones::where('id', $record->remision_id)->update(['estado' => 'Facturada']);
        }

        // Crear documento relacionado si existe
        if ($record->docto_rela != '') {
            DoctosRelacionados::create([
                'docto_type' => 'F',
                'docto_id' => $record->id,
                'rel_id' => $record->docto_rela,
                'rel_type' => 'F',
                'rel_cause' => $record->tipo_rela,
                'team_id' => Filament::getTenant()->id
            ]);
        }

        // Timbrado automático
        $factura = $record->id;
        $receptor = $record->clie;
        $emp = Team::where('id', Filament::getTenant()->id)->first();

        if ($emp->archivokey != null && $emp->archivokey != '') {
            $res = app(TimbradoController::class)->TimbrarFactura($factura, $receptor);
            $resultado = json_decode($res);
            $codigores = $resultado->codigo;
            if ($codigores == "200") {
                $date = Carbon::now();
                $facturamodel = Facturas::find($factura);
                $facturamodel->timbrado = 'SI';
                $facturamodel->xml = $resultado->cfdi;
                $facturamodel->fecha_tim = $date;

                // Si es PPD, llenar pendiente_pago con el total
                if ($facturamodel->forma === 'PPD') {
                    $facturamodel->pendiente_pago = $facturamodel->total * ($facturamodel->tcambio ?? 1);
                }

                $facturamodel->save();
                // Grabar automáticamente en almacén de CFDIs
                $res2 = app(TimbradoController::class)->grabar_almacen_cfdi($factura, $receptor, $resultado->cfdi);
                $mensaje_graba = 'Factura Timbrada Se genero el CFDI UUID: ' . $res2;
                $cli = Clientes::where('id', $record->clie)->first();
                $archivo_pdf = $emp->taxid . '_FACTURA_CFDI_' . $record->serie . $record->folio . '_' . $cli->rfc . '.pdf';
                $ruta = public_path() . '/TMPCFDI/' . $archivo_pdf;
                if (File::exists($ruta)) File::delete($ruta);
                $data = ['idorden' => $record->id, 'id_empresa' => Filament::getTenant()->id];
                $html = View::make('RepFactura', $data)->render();
                Browsershot::html($html)->format('Letter')
                    ->setIncludePath('$PATH:/opt/plesk/node/22/bin')
                    ->setEnvironmentOptions(["XDG_CONFIG_HOME" => "/tmp/google-chrome-for-testing", "XDG_CACHE_HOME" => "/tmp/google-chrome-for-testing"])
                    ->noSandbox()
                    ->scale(0.8)->savePdf($ruta);
                $nombre = $emp->taxid . '_FACTURA_CFDI_' . $record->serie . $record->folio . '_' . $cli->rfc . '.xml';
                $archivo_xml = public_path() . '/TMPCFDI/' . $nombre;
                if (File::exists($archivo_xml)) unlink($archivo_xml);
                $xml = $resultado->cfdi;
                $xml = Cleaner::staticClean($xml);
                File::put($archivo_xml, $xml);

                // Crear ZIP con PDF y XML
                $zip = new \ZipArchive();
                $zipPath = public_path() . '/TMPCFDI/';
                $zipFileName = $emp->taxid . '_FACTURA_CFDI_' . $record->serie . $record->folio . '_' . $cli->rfc . '.zip';
                $zipFile = $zipPath . $zipFileName;
                if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
                    $zip->addFile($archivo_xml, $nombre);
                    $zip->addFile($ruta, $archivo_pdf);
                    $zip->close();
                }
                $docto = $record->serie . $record->folio;
                FacturasResource::EnvioCorreo($record->clie, $ruta, $archivo_xml, $docto, $archivo_pdf, $nombre);
                FacturasResource::MsjTimbrado($mensaje_graba);
            } else {
                $mensaje_graba = $resultado->mensaje;
                $record->error_timbrado = $resultado->mensaje;
                $record->save();
                Notification::make()
                    ->warning()
                    ->title('Error al Timbrar el Documento')
                    ->body($mensaje_graba)
                    ->persistent()
                    ->send();
            }

            // Crear registros de SurtidoInve
            $par_sur = FacturasPartidas::where('facturas_id', $record->id)->get();
            foreach ($par_sur as $par) {
                SurtidoInve::create([
                    'factura_id' => $record->id,
                    'factura_partida_id' => $par->id,
                    'item_id' => $par->item,
                    'descr' => $par->descripcion,
                    'cant' => $par->cant,
                    'precio_u' => $par->precio,
                    'costo_u' => $par->costo,
                    'precio_total' => $par->subtotal,
                    'costo_total' => $par->costo * $par->cant,
                    'team_id' => Filament::getTenant()->id
                ]);
            }
        }
    }
}
