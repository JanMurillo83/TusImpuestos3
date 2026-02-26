<?php

namespace App\Filament\Resources\XmlfilesResource\Pages;

use App\Filament\Resources\XmlfilesResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\Action;
use App\Filament\Resources\Pages\ListRecords;
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
use App\Models\Solicitudes;
use App\Models\Xmlfiles;
use App\Support\CfdiPagosHelper;
use Filament\Notifications\Notification;
use App\Http\Controllers\DescargaSAT;
use Illuminate\Http\Request;

class ListXmlfiles extends ListRecords
{
    use HasResizableColumn;
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
                ])->modalWidth(MaxWidth::ExtraSmall)
                ->action(function($livewire,$data){
                    $livewire->GeneraSolicitud($data);
                }),
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
                Action::make('importar')
                ->label('Importacion ZIP')
                ->form([
                    Select::make('tipo')
                        ->label('Tipo de CFDI')
                        ->options([
                            'Emitidos'=>'Emitidos',
                            'Recibidos'=>'Recibidos'
                            ])
                        ->default('Recibidos'),
                    FileUpload::make('archivozip')
                        ->label('Seleccione el Archivo a Importar')
                        ->acceptedFileTypes(['application/zip'])
                        ->storeFiles(false)
                ])->modalWidth(MaxWidth::ExtraSmall)
                ->action(function($livewire,$data){

                    $livewire->ProcesaArchivoZ($data);
                })
        ];
    }
    public function GeneraSolicitud($data)
    {
        $solicita = Filament::getTenant()->taxid;
        $rutacer = \storage_path().'/app/public/'.Filament::getTenant()->archivocer;
        $rutakey = \storage_path().'/app/public/'.Filament::getTenant()->archivokey;
        $fielpass = Filament::getTenant()->fielpass;
        $request = new Request();
        $request->replace([
            'inicio' => $data['Inicio'],
            'final' => $data['Final'],
            'version' => $data['Versión'],
            'solicita' => $solicita,
            'rutacer' => $rutacer,
            'rutakey' => $rutakey,
            'fielpass' => $fielpass

        ]);
        $resultado = app(DescargaSAT::class)->solicitud($request);
        list($codigo,$mensaje) = explode('|',$resultado);
        if($codigo == 'Exito')
        {
            Solicitudes::create([
                'request_id'=>$mensaje,
                'status'=>'Pendiente',
                'message'=>'',
                'xml_type'=>'',
                'ini_date'=>$data['Inicio'],
                'ini_hour'=>'00:00:00',
                'end_date'=>$data['Final'],
                'end_hour'=>'00:00:00',
                'user_tax'=>$solicita,
                'team_id'=>Filament::getTenant()->id
            ]);
            $this->revisa_solicitud($mensaje,$solicita,$rutacer,$rutakey,$fielpass);
            Notification::make()
                ->title('Solicitud Enviada')
                ->body($resultado)
                ->success()
                ->send();
        }
        else
        {
            Notification::make()
            ->title('Solicitud Erronea')
            ->body($resultado)
            ->warning()
            ->send();
        }

    }

    public function revisa_solicitud($solicitud,$solicita,$rutacer,$rutakey,$fielpass)
    {
        $request = new Request();
        $request->replace([
            'solicita' => $solicita,
            'rutacer' => $rutacer,
            'rutakey' => $rutakey,
            'fielpass' => $fielpass,
            'requestId' => $solicitud
        ]);
        $resultado = app(DescargaSAT::class)->verifica_solicitud($request);
        list($codigo,$mensaje) = explode('|',$resultado);
        if($codigo == 'Exito')
        {

            //$this->revisa_solicitud($mensaje,$solicita,$rutacer,$rutakey,$fielpass);
            Notification::make()
                ->title('Solicitud Enviada')
                ->body($mensaje)
                ->success()
                ->send();
        }
        else
        {
            if($codigo == 'Proceso')
            {
                Notification::make()
                    ->title('Solicitud En Proceso')
                    ->body($mensaje)
                    ->info()
                    ->send();
            }
            else
            {
                Notification::make()
                ->title('Solicitud Erronea')
                ->body($mensaje)
                ->warning()
                ->send();
            }
        }
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
            $pagoscom = CfdiPagosHelper::findPagosComplement($comprobante);
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
                $pagostot = CfdiPagosHelper::calculatePagosTotales($pagoscom);
                $subtotal = $pagostot['subtotal'];
                $traslado = $pagostot['traslado'];
                $retencion = $pagostot['retencion'];
                $total = $pagostot['total'];
                $tipocambio = $pagostot['tipocambio'];
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

    public function ProcesaArchivoZ($data)
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
            $pagoscom = CfdiPagosHelper::findPagosComplement($comprobante);
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
                $pagostot = CfdiPagosHelper::calculatePagosTotales($pagoscom);
                $subtotal = $pagostot['subtotal'];
                $traslado = $pagostot['traslado'];
                $retencion = $pagostot['retencion'];
                $total = $pagostot['total'];
                $tipocambio = $pagostot['tipocambio'];
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
