<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitudesResource\Pages;
use App\Filament\Resources\SolicitudesResource\RelationManagers;
use App\Models\Solicitudes;
use App\Models\Xmlfiles;
use App\Models\Almacencfdis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Http\Controllers\DescargaSAT;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use CfdiUtils;
use CfdiUtils\Cfdi;
class SolicitudesResource extends Resource
{
    protected static ?string $model = Solicitudes::class;
    protected static ?string $navigationGroup = 'Descargas CFDI';
    protected static ?string $pluralLabel = 'Descargas CFDI';
    protected static ?string $label = 'CFDI';

    public static function form(Form $form): Form
    {
        return $form;
            /*->schema([
                Forms\Components\TextInput::make('request_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('message')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('xml_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ini_date')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ini_hour')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('end_date')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('end_hour')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_tax')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('team_id')
                    ->required()
                    ->numeric(),
            ]);*/
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_id')
                    ->label('ID Solicitud')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable(),
                /*Tables\Columns\TextColumn::make('message')
                    ->searchable(),
                Tables\Columns\TextColumn::make('xml_type')
                    ->searchable(),*/
                Tables\Columns\TextColumn::make('ini_date')
                    ->label('Fecha Inicial')
                    ->dateTime('d-m-Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Final')
                    ->dateTime('d-m-Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('xml_type')
                    ->label('Tipo de Solicitud')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_tax')
                    ->label('Solicitante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Solicitud')
                    ->dateTime('d-m-Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('verificar1')
                ->visible(fn ($record) => $record->status == 'Procesando Descarga')
                ->label('Procesar')
                ->accessSelectedRecords()
                ->icon('fas-circle-down')
                ->action(function (Model $record) {
                    Self::procesa($record);
                }),
                Action::make('verificar2')
                ->visible(fn ($record) => $record->status == 'Archivo Descargado')
                ->label('Extraer Archivo')
                ->accessSelectedRecords()
                ->icon('fas-circle-down')
                ->action(function (Model $record) {
                    Self::extrae_archivos($record['request_id'],$record['request_id'],$record['xml_type']);
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ]);
    }

    public static function procesa($record)
    {
        $solicitud = $record['request_id'];
        $tiposol = $record['xml_type'];
        $solicita = Filament::getTenant()->taxid;
        $rutacer = \storage_path().'/app/public/'.Filament::getTenant()->archivocer;
        $rutakey = \storage_path().'/app/public/'.Filament::getTenant()->archivokey;
        $fielpass = Filament::getTenant()->fielpass;
        Self::revisa_solicitud($solicitud,$solicita,$rutacer,$rutakey,$fielpass,$tiposol);
    }

    public static function revisa_solicitud($solicitud,$solicita,$rutacer,$rutakey,$fielpass,$tiposol)
    {
        $request = new Request();
        $request->replace([
            'solicita' => $solicita,
            'rutacer' => $rutacer,
            'rutakey' => $rutakey,
            'fielpass' => $fielpass,
            'requestId' => $solicitud
        ]);
        error_log('Aqui si funciona 1');
        $resultado = app(DescargaSAT::class)->verifica_solicitud($request);
        error_log($resultado);
        list($codigo,$mensaje) = explode('|',$resultado);
        if($codigo == 'Exito')
        {
            Self::extrae_archivos($mensaje,$solicitud,$tiposol);
            Notification::make()
                ->title('Solicitud Descargada')
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
            if($codigo == 'Error')
            {
                Notification::make()
                ->title('Solicitud Erronea')
                ->body($mensaje)
                ->warning()
                ->send();
            }
        }
        //dd($resultado);
    }

    public static function extrae_archivos($zipfile,$solicitud,$tiposol)
    {
        $archivo = \storage_path().'/app/public/zipdescargas/'.strtoupper($zipfile).'_01.zip';
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
            Self::ProcesaArchivo2($desfile,$tiposol,$solicitud);
        } else {
            $msj = 'Error al extraer Archivos';
        }
        Notification::make()
            ->title('Extracción de Archivos')
            ->body($msj)
            ->info()
            ->send();
    }

    public static function ProcesaArchivo2($data,$tiposol,$solicitud)
    {
        $archivos = array_diff(scandir($data), array('.', '..'));
        $tipo = $tiposol;
        $team  = Filament::getTenant()->id;
        $taxid = Filament::getTenant()->taxid;
        $contador = 0;

        //for($i = 0;$i<$NoArchivos;$i++)
        foreach($archivos as $file)
        {
            $file =$data.'/'.$file;
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
                        'solicitud'=>$solicitud,
                        'team_id'=>$team
                    ]);
                    $contador++;
                }
            }
                if($tipo == 'Recibidos')
                {
                    if($receptor['Rfc'] == $taxid)
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
                            'user_tax'=>$receptor['Rfc'],
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
                            'solicitud'=>$solicitud,
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolicitudes::route('/'),
            //'create' => Pages\CreateSolicitudes::route('/create'),
            //'edit' => Pages\EditSolicitudes::route('/{record}/edit'),
        ];
    }
}
