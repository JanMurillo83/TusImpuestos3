<?php

namespace App\Filament\Resources\XmlfilesResource\Pages;

use App\Filament\Resources\XmlfilesResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Get;
use CfdiUtils;
use CfdiUtils\Cfdi;
use Filament\Facades\Filament;
use App\Models\Almacencfdis;
use App\Models\Xmlfiles;
use Filament\Notifications\Notification;

class ListXmlfiles extends ListRecords
{
    protected static string $resource = XmlfilesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descarga')
                ->label('Descarga SAT')
                ->form([
                    DatePicker::make('Inicio')
                        ->label('Fecha Inicial'),
                    DatePicker::make('Final')
                        ->label('Fecha Final'),
                    TextInput::make('Versión')
                        ->label('Versión de la Solicitud')
                        ->numeric()
                        ->default(0),
                ])->modalWidth(MaxWidth::ExtraSmall),
            Action::make('importacion')
                ->label('Importacion XML')
                ->form([
                    Select::make('tipo')
                        ->label('Tipo de CFDI')
                        ->options([
                            'Emitidos'=>'Emitidos',
                            'Recibidos'=>'Recibidos'
                            ])
                        ->default('Recibidos'),
                    FileUpload::make('archivos')
                        ->label('Seleccione los Archivos XML a Importar')
                        ->multiple()
                        ->acceptedFileTypes(['text/xml'])
                        ->storeFiles(false)
                ])->modalWidth(MaxWidth::ExtraSmall)
                ->action(function($livewire,$data){

                    $livewire->ProcesaArchivo($data);
                }),
        ];
    }

    public function ProcesaArchivo($data)
    {
        $archivos = $data['archivos'];
        $tipo = $data['tipo'];
        $NoArchivos = count($archivos);
        $team  = Filament::getTenant()->id;
        $taxid = Filament::getTenant()->taxid;
        $contador = 0;
        for($i = 0;$i<$NoArchivos;$i++)
        {
            $file =$archivos[$i]->path();
            $xmlContents = \file_get_contents($file);
            $cfdi = Cfdi::newFromString($xmlContents);
            $comprobante = $cfdi->getNode();
            $emisor = $comprobante->searchNode('cfdi:Emisor');
            $receptor = $comprobante->searchNode('cfdi:Receptor');
            $tfd = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
            $pagoscom = $comprobante->searchNode('cfdi:Complemento', 'pago20:Pagos');
            $impuestos = $comprobante->searchNode('cfdi:Impuestos');
            $tipocom = $comprobante['TipoDeComprobante'];
            $subtotal = 0;
            $traslado = 0;
            $retencion = 0;
            $total = 0;
            $tipocambio = 0;
            if($tipocom != 'P')
            {
                $subtotal = floatval($comprobante['SubTotal']);
                if(isset($impuestos['TotalImpuestosTrasladados']))$traslado = floatval($impuestos['TotalImpuestosTrasladados']);
                if(isset($impuestos['TotalImpuestosRetenidos'])) $retencion = floatval($impuestos['TotalImpuestosRetenidos']);
                $total = floatval($comprobante['Total']);
                $tipocambio = floatval($comprobante['TipoCambio']);
            }
            else
            {
                $pagostot = $pagoscom->searchNode('pago20:Totales');
                $subtotal = floatval($pagostot['TotalTrasladosBaseIVA16']);
                $traslado = floatval($pagostot['TotalTrasladosImpuestoIVA16']);
                $retencion = floatval(0.00);
                $total = floatval($pagostot['MontoTotalPagos']);
                $tipocambio = 1;
            }
            $xmlContenido = \file_get_contents($file,false);
            //dd($xmlContenido);
            $fech = $comprobante['Fecha'];
            list($fechacom,$horacom) = explode('T',$fech);
            list($aniocom,$mescom,$diacom) =explode('-',$fechacom);
            if($tipo == 'Emitidos')
            {
                //dd($emisor['Rfc'] .'-'. $taxid);
                if($emisor['Rfc'] == $taxid)
                {
                    $almcfdi = Almacencfdis::firstOrCreate([
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
                        'Total'=>$total,
                        'SubTotal'=>$subtotal,
                        'TipoCambio'=> $tipocambio,
                        'TotalImpuestosTrasladados'=>$traslado,
                        'TotalImpuestosRetenidos'=>$retencion,
                        'content'=>$xmlContenido,
                        'user_tax'=>$emisor['Rfc'],
                        'used'=>'NO',
                        'xml_type'=>$tipo,
                        'periodo'=>$mescom,
                        'ejercicio'=>$aniocom,
                        'team_id'=>$team
                    ]);
                    Xmlfiles::firstOrCreate([
                        'taxid'=>$emisor['Rfc'],
                        'uuid'=>$tfd['UUID'],
                        'content'=>$xmlContenido,
                        'periodo'=>$mescom,
                        'ejercicio'=>$aniocom,
                        'tipo'=>$tipo,
                        'solicitud'=>'Importacion',
                        'team_id'=>$team
                    ]);
                    $contador++;
                }
            }

        }

        Notification::make()
            ->title($contador .' Registros Procesados Correctamente')
            ->success()
            ->send();

    }
}
