<?php

namespace App\Filament\Resources\SolicitudesResource\Pages;

use App\Filament\Resources\SolicitudesResource;
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
use App\Models\Solicitudes;
use App\Models\Xmlfiles;
use Filament\Notifications\Notification;
use App\Http\Controllers\DescargaSAT;
use Filament\Forms\Components\Hidden;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;


class ListSolicitudes extends ListRecords
{
    protected static string $resource = SolicitudesResource::class;

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
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(59)
                        ->default(0),
                    Select::make('Tipo')
                    ->options([
                        'Emitidos'=>'Emitidos',
                        'Recibidos'=>'Recibidos'
                    ])->default('Emitidos')
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
                       // ->acceptedFileTypes(['multipart/x-zip'])
                        //->storeFiles(false)
                        ->maxSize(8096)
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
        $tiposol = $data['Tipo'];
        $request = new Request();
        $request->replace([
            'inicio' => $data['Inicio'],
            'final' => $data['Final'],
            'version' => $data['Versión'],
            'tipo' => $tiposol,
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
                'xml_type'=>$data['Tipo'],
                'ini_date'=>$data['Inicio'],
                'ini_hour'=>'00:00:00',
                'end_date'=>$data['Final'],
                'end_hour'=>'00:00:00',
                'user_tax'=>$solicita,
                'team_id'=>Filament::getTenant()->id
            ]);
            $this->revisa_solicitud($mensaje,$solicita,$rutacer,$rutakey,$fielpass,$tiposol);
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

    public function revisa_solicitud($solicitud,$solicita,$rutacer,$rutakey,$fielpass,$tiposol)
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

            $this->extrae_archivos($mensaje,$solicitud,$tiposol);
            Notification::make()
                ->title('Solicitud Enviada')
                ->body('Solicitud '.$solicitud.' procesada Correctamente')
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

    public function extrae_archivos($zipfile,$solicitud,$tiposol)
    {
        $archivo = \storage_path().'/app/public/zipdescargas/'.$zipfile.'.zip';
        $desfile = \storage_path().'/app/public/zipdescargas/'.$solicitud.'/';
        $zip = new \ZipArchive;
        $msj = '';
        if ($zip->open($archivo) === TRUE) {
            $zip->extractTo($desfile);
            $zip->close();
            $msj = 'Archivos Extraidos';
            Solicitudes::where('request_id',$solicitud)->update([
                'status'=>'Solicitud Concluida'
            ]);
            $this->ProcesaArchivo2($desfile,$tiposol);
        } else {
            $msj = 'Error al extrae Archivos';
        }
        Notification::make()
            ->title('Extracción de Archivos')
            ->body($msj)
            ->info()
            ->send();
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
                if($emisor['Rfc'] == $taxid)
                {
                    Almacencfdis::firstOrCreate([
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
                        'periodo'=>intval($mescom),
                        'ejercicio'=>intval($aniocom),
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
            else
            {
                if($receptor['Rfc'] == $taxid)
                {
                    Almacencfdis::firstOrCreate([
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
                        'periodo'=>intval($mescom),
                        'ejercicio'=>intval($aniocom),
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
        Solicitudes::create([
            'request_id'=>'Importacion',
            'status'=>'Solicitud Terminada',
            'message'=>'',
            'xml_type'=>$tipo,
            'ini_date'=>(new \DateTime())->format( 'Y-m-d' ),
            'ini_hour'=>'00:00:00',
            'end_date'=>(new \DateTime())->format( 'Y-m-d' ),
            'end_hour'=>'00:00:00',
            'user_tax'=>$taxid,
            'team_id'=>Filament::getTenant()->id
        ]);
        Notification::make()
            ->title($contador .' Registros Procesados Correctamente')
            ->success()
            ->send();
    }

    public function ProcesaArchivo2($data,$tiposol)
    {
        $archivos = $data['archivos'];
        $tipo = $tiposol;
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

    public function ProcesaArchivoZ($data)
    {
        //------------------------------------------------
        $tiposol = $data['tipo'];
        $tipo = $data['tipo'];
        $archivos = $data['archivozip'];
        $ext = pathinfo($archivos, PATHINFO_EXTENSION);
        $archivo = \storage_path().'/app/public/'.$archivos;
        $desfile = \storage_path().'/app/public/zipimportas/';
        if($ext == 'zip')
        {
            $zip = new \ZipArchive;
            $msj = '';
            $ncount = 0;
            if ($zip->open($archivo) === TRUE) {
                $zip->extractTo($desfile);
                $zip->close();
                $ncount = $this->ProcesaArchivoZ_F($desfile,$tiposol);
                $msj = $ncount.' Archivos Procesados';
            } else {
                $msj = 'Error al extrae Archivos';
                Notification::make()
                    ->title('Extracción de Archivos')
                    ->body($msj)
                    ->info()
                    ->send();
                return;
            }
        }
        else{
            $msj = 'Tipo de Archivo NO valido';
                Notification::make()
                    ->title('Extracción de Archivos')
                    ->body($msj)
                    ->info()
                    ->send();
                return;
        }
        Solicitudes::create([
            'request_id'=>'Importacion',
            'status'=>'Solicitud Terminada',
            'message'=>'',
            'xml_type'=>$tipo,
            'ini_date'=>(new \DateTime())->format( 'Y-m-d' ),
            'ini_hour'=>'00:00:00',
            'end_date'=>(new \DateTime())->format( 'Y-m-d' ),
            'end_hour'=>'00:00:00',
            'user_tax'=>'',
            'team_id'=>Filament::getTenant()->id
        ]);
        Notification::make()
            ->title('Extracción de Archivos')
            ->body($msj)
            ->info()
            ->send();
        //------------------------------------------------
    }

    public function ProcesaArchivoZ_F($archivo,$tiposol)
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        $contador = 0;
        foreach($files as $desfile)
        {
            $file = \storage_path().'/app/public/zipimportas/'.$desfile;
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
                if(!isset($pagoscom)) {
					$pagostot = floatval(0.00);
					$subtotal = floatval(0.00);
					$traslado = floatval(0.00);
					$retencion = floatval(0.00);
					$total = floatval(0.00);
					$tipocambio = 1;
				}
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
            $taxid = Filament::getTenant()->taxid;
            $tipo = $tiposol;
            $team= Filament::getTenant()->id;
            if($tiposol == 'Emitidos')
            {
                if($emisor['Rfc'] == $taxid)
                {
                    Almacencfdis::firstOrCreate([
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
                        'xml_type'=>$tiposol,
                        'periodo'=>intval($mescom),
                        'ejercicio'=>intval($aniocom),
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
                }
            }
            else
            {
                if($receptor['Rfc'] == $taxid)
                {
                    Almacencfdis::firstOrCreate([
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
                        'periodo'=>intval($mescom),
                        'ejercicio'=>intval($aniocom),
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
                }
            }
            $contador++;
        }
        return $contador;
    }
}
