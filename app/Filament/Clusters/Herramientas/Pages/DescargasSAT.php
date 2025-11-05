<?php

namespace App\Filament\Clusters\Herramientas\Pages;

use App\Filament\Clusters\Herramientas;
use App\Models\Almacencfdis;
use App\Models\DescargasArchivosSat;
use App\Models\DescargasSolicitudesSat;
use App\Models\Solicitudes;
use App\Models\Team;
use App\Models\Xmlfiles;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;

class DescargasSAT extends Page implements HasTable,HasForms
{
    use InteractsWithTable,InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.clusters.herramientas.pages.descargas-s-a-t';
    protected static ?string $cluster = Herramientas::class;
    protected static ?string $title = 'Descargas SAT';

    public function table(Table $table): Table
    {
        return $table
            ->query(Team::query())
            ->striped()
            ->columns([
                TextColumn::make('id')->label('Registro'),
                TextColumn::make('taxid')->label('RFC')->searchable(),
                TextColumn::make('name')->label('Razón Social')->searchable(),
                TextColumn::make('archivocer')->label('FIEL CER'),
                TextColumn::make('archivokey')->label('FIEL KEY'),
            ])
            ->actions([
                ActionGroup::make([
                \Filament\Tables\Actions\EditAction::make()
                ->label('Editar')
                ->icon('fas-edit')
                ->form(function ($record,Form $form) {
                    return $form->schema([
                        TextInput::make('taxid')->label('RFC')->required()->maxLength(14)->default($record->taxid),
                        TextInput::make('name')->label('RFC')->required()->default($record->name)->columnSpan(3),
                        FileUpload::make('archivocer')->label('FIEL CER')->required()->disk('public')->visibility('public')->columnSpan(2),
                        FileUpload::make('archivokey')->label('FIEL KEY')->required()->disk('public')->visibility('public')->columnSpan(2),
                        TextInput::make('fielpass')->label('Contraseña FIEL')->required()->password()->default($record->fielpass)->revealable(),
                    ])->columns(4);
                }),
                Action::make('Limpiar')
                ->icon('fas-trash')
                ->label('Limpiar')
                ->action(function($record){
                    DB::statement("START TRANSACTION;");
                    DB::statement("DELETE t1 FROM almacencfdis t1
                        INNER JOIN almacencfdis t2 WHERE t1.id < t2.id
                        AND t1.team_id = t2.team_id AND t1.UUID = t2.UUID
                        AND t1.team_id = $record->id;");
                    DB::statement("COMMIT;");
                    Notification::make()->title('Proceso Completado')->success()->send();
                }),
                Action::make('Descargar')
                ->icon('fas-download')
                ->label('Descargar')
                ->form([
                    DatePicker::make('fecha_inicial')
                        ->label('Fecha Inicial')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                    DatePicker::make('fecha_final')
                        ->label('Fecha Final')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                ])
                ->action(function($record,$data){
                    $team = $record;
                    $fecha_inicial = $data['fecha_inicial'];
                    $fecha_final = $data['fecha_final'];
                    if($team->archivocer != '' && $team->archivokey != '') {
                        $request = [
                            'solicita' => $team->taxid,
                            'rutacer' => storage_path().'/app/public/'.$team->archivocer,
                            'rutakey' => storage_path().'/app/public/'.$team->archivokey,
                            'inicio' => $fecha_inicial,
                            'final' => $fecha_final,
                            'version' => 1,
                            'fielpass' => $team->fielpass
                        ];
                        $resultado = $this->solicitud($request);
                        list($codigo,$mensaje) = explode('|',$resultado);
                        if($codigo == 'Exito'){
                            DescargasSolicitudesSat::create([
                                'id_sat'=>$mensaje,
                                'estatus'=>'Pendiente',
                                'estado'=>'Solicitud',
                                'team_id'=>$team->id,
                                'fecha_inicial'=>Carbon::create( $fecha_inicial),
                                'fecha_final'=>Carbon::create( $fecha_final),
                                'fecha'=>Carbon::now(),
                            ]);
                        }else{
                            DescargasSolicitudesSat::create([
                                'id_sat'=>0,
                                'estatus'=>$mensaje,
                                'estado'=>'Error',
                                'team_id'=>$team->id,
                                'fecha_inicial'=>Carbon::create( $fecha_inicial),
                                'fecha_final'=>Carbon::create( $fecha_final),
                                'fecha'=>Carbon::now(),
                            ]);
                        }
                        $solicitudes = DescargasSolicitudesSat::where('estatus','Pendiente')
                        ->where('team_id',$record->id)->get();
                        $codigos = [];
                        foreach ($solicitudes as $solicitud)
                        {
                            $team = Team::where('id',$solicitud->team_id)->first();
                            $request = [
                                'solicita' => $team->taxid,
                                'rutacer' => storage_path().'/app/public/'.$team->archivocer,
                                'rutakey' => storage_path().'/app/public/'.$team->archivokey,
                                'inicio' => $fecha_inicial,
                                'final' => $fecha_final,
                                'version' => 1,
                                'requestId'=>$solicitud->id_sat,
                                'fielpass' => $team->fielpass,
                                'team_id'=>$team->id,
                            ];
                            $resultado = $this->verifica_solicitud($request);
                            list($codigo,$mensaje) = explode('|',$resultado);
                            $codigos[] = [
                                'codigo'=>$codigo,
                                'mensaje'=>$mensaje,
                                'solicitud'=>$solicitud->id_sat,
                            ];
                        }
                        $archivos = DescargasArchivosSat::where('estado','Pendiente')
                            ->where('team_id',$record->id)->get();
                        foreach ($archivos as $archivo)
                        {
                            $team_ = Team::where('id',$archivo->team_id)->first();
                            $team = $team_->id;
                            $taxid = $team_->taxid;
                            $this->extrae_archivos($archivo->archivo,$archivo->id_sat,$archivo->id,$team,$taxid);
                        }
                        //dd($codigos);
                        Notification::make()->title('Proceso Completado')->success()->send();
                    }
                 })
                ])
            ],ActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('Descarga')
                ->label('Descargar')
                ->icon('fas-download')
                ->form([
                    DatePicker::make('fecha_inicial')
                    ->label('Fecha Inicial')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                    DatePicker::make('fecha_final')
                        ->label('Fecha Final')->default(Carbon::now()->subDays(1)->format('Y-m-d')),
                ])
                ->action(function($data){
                    $teams = Team::all();
                    $fecha_inicial = $data['fecha_inicial'];
                    $fecha_final = $data['fecha_final'];
                    //dd($fecha_inicial,$fecha_final);
                    foreach ($teams as $team)
                    {
                        if($team->archivocer != '' && $team->archivokey != '') {
                            $request = [
                                'solicita' => $team->taxid,
                                'rutacer' => storage_path().'/app/public/'.$team->archivocer,
                                'rutakey' => storage_path().'/app/public/'.$team->archivokey,
                                'inicio' => $fecha_inicial,
                                'final' => $fecha_final,
                                'version' => 1,
                                'fielpass' => $team->fielpass
                            ];
                            $resultado = $this->solicitud($request);
                            list($codigo,$mensaje) = explode('|',$resultado);
                            if($codigo == 'Exito'){
                                DescargasSolicitudesSat::create([
                                    'id_sat'=>$mensaje,
                                    'estatus'=>'Pendiente',
                                    'estado'=>'Solicitud',
                                    'team_id'=>$team->id,
                                    'fecha_inicial'=>Carbon::create( $fecha_inicial),
                                    'fecha_final'=>Carbon::create( $fecha_final),
                                    'fecha'=>Carbon::now(),
                                ]);
                            }else{
                                DescargasSolicitudesSat::create([
                                    'id_sat'=>0,
                                    'estatus'=>$mensaje,
                                    'estado'=>'Error',
                                    'team_id'=>$team->id,
                                    'fecha_inicial'=>Carbon::create( $fecha_inicial),
                                    'fecha_final'=>Carbon::create( $fecha_final),
                                    'fecha'=>Carbon::now(),
                                ]);
                            }
                            //dd($resultado);
                        }
                    }
                    $solicitudes = DescargasSolicitudesSat::where('estatus','Pendiente')->get();
                    $codigos = [];
                    foreach ($solicitudes as $solicitud)
                    {
                        $team = Team::where('id',$solicitud->team_id)->first();
                        $request = [
                            'solicita' => $team->taxid,
                            'rutacer' => storage_path().'/app/public/'.$team->archivocer,
                            'rutakey' => storage_path().'/app/public/'.$team->archivokey,
                            'inicio' => $fecha_inicial,
                            'final' => $fecha_final,
                            'version' => 1,
                            'requestId'=>$solicitud->id_sat,
                            'fielpass' => $team->fielpass,
                            'team_id'=>$team->id,
                        ];
                        $resultado = $this->verifica_solicitud($request);
                        list($codigo,$mensaje) = explode('|',$resultado);
                        $codigos[] = [
                            'codigo'=>$codigo,
                            'mensaje'=>$mensaje,
                            'solicitud'=>$solicitud->id_sat,
                        ];
                    }
                    $archivos = DescargasArchivosSat::where('estado','Pendiente')->get();
                    foreach ($archivos as $archivo)
                    {
                        $team_ = Team::where('id',$archivo->team_id)->first();
                        $team = $team_->id;
                        $taxid = $team_->taxid;
                        $this->extrae_archivos($archivo->archivo,$archivo->id_sat,$archivo->id,$team,$taxid);
                    }
                    //dd($codigos);
                    Notification::make()->title('Proceso Completado')->success()->send();
                }),
                Action::make('Verificar')
                ->label('Verificar')
                ->icon('fas-check')
                ->action(function(){
                    $solicitudes = DescargasSolicitudesSat::where('estatus','Pendiente')->get();
                    $codigos = [];
                    foreach ($solicitudes as $solicitud)
                    {
                        $team = Team::where('id',$solicitud->team_id)->first();
                        $request = [
                            'solicita' => $team->taxid,
                            'rutacer' => storage_path().'/app/public/'.$team->archivocer,
                            'rutakey' => storage_path().'/app/public/'.$team->archivokey,
                            'inicio' => $solicitud->fecha_inicial,
                            'final' => $solicitud->fecha_final,
                            'version' => 1,
                            'requestId'=>$solicitud->id_sat,
                            'fielpass' => $team->fielpass,
                            'team_id'=>$team->id,
                        ];
                        $resultado = $this->verifica_solicitud($request);
                        //dd($resultado);
                        list($codigo,$mensaje) = explode('|',$resultado);
                        $codigos[] = [
                            'codigo'=>$codigo,
                            'mensaje'=>$mensaje,
                            'solicitud'=>$solicitud->id_sat,
                            'id_sat'=>$solicitud->id,
                        ];

                    }
                    //dd($codigos);
                    Notification::make()->title('Proceso Completado')->success()->send();
                }),
                Action::make('Extraer')
                ->icon('fas-file-archive')
                ->label('Extraer')
                ->action(function(){
                    $archivos = DescargasArchivosSat::where('estado','Pendiente')->get();
                    foreach ($archivos as $archivo)
                    {
                        $team_ = Team::where('id',$archivo->team_id)->first();
                        $team = $team_->id;
                        $taxid = $team_->taxid;
                        $this->extrae_archivos($archivo->archivo,$archivo->id_sat,$archivo->id,$team,$taxid);
                    }
                    Notification::make()->title('Proceso Completado')->success()->send();
                }),
                Action::make('Limpiar DB')
                ->icon('fas-trash')
                ->label('Limpiar DB')
                ->action(function(){
                    $teams = Team::all();
                    foreach ($teams as $team) {
                        DB::statement("START TRANSACTION;");
                        DB::statement("DELETE t1 FROM almacencfdis t1
                        INNER JOIN almacencfdis t2 WHERE t1.id < t2.id
                        AND t1.team_id = t2.team_id AND t1.UUID = t2.UUID
                        AND t1.id > 0 AND t1.team_id = $team->id;");
                        DB::statement("COMMIT;");
                    }
                    Notification::make()->title('Proceso Completado')->success()->send();
                })
            ]);
    }

    public function solicitud(array $request)
    {
        try {
            $solicita = $request['solicita'];
            $rutacer = $request['rutacer'];
            $rutakey = $request['rutakey'];
            $inicial = $request['inicio'];
            $final = $request['final'];
            $version = $request['version'];
            $fielpass = $request['fielpass'];
            //$tiposol = $request['tipo'];
            $fiel = Fiel::create(
                file_get_contents($rutacer),
                file_get_contents($rutakey),
                $fielpass
            );
            $webClient = new GuzzleWebClient();
            $requestBuilder = new FielRequestBuilder($fiel);
            $service = new Service($requestBuilder, $webClient);
            $fechainicial = $inicial . ' ' . '00:00:00';
            $fechafinal = $final . ' ' . '23:59:' . str_pad($version, 2, "0", STR_PAD_LEFT);
            $requestsol = QueryParameters::create()
                ->withPeriod(DateTimePeriod::createFromValues($fechainicial, $fechafinal))
                ->withRequestType(RequestType::xml())
                ->withDocumentStatus(DocumentStatus::active());
            $query = $service->query($requestsol);
            if (!$query->getStatus()->isAccepted()) {
                //dd("Fallo al presentar la consulta: {$query->getStatus()->getMessage()}");
                return 'Error|' . $query->getStatus()->getMessage();
            }
            return 'Exito|' . $query->getRequestId();
        }catch (\Exception $e){
            return 'Error|' . $e->getMessage();
        }
    }

    public function verifica_solicitud(array $request)
    {
        $solicita = $request['solicita'];
        $rutacer = $request['rutacer'];
        $rutakey = $request['rutakey'];
        $requestId = $request['requestId'];
        $fielpass = $request['fielpass'];
        $team_id = $request['team_id'];
        $fiel = Fiel::create(
            file_get_contents($rutacer),
            file_get_contents($rutakey),
            $fielpass
        );
        $webClient = new GuzzleWebClient();
        $requestBuilder = new FielRequestBuilder($fiel);
        $service = new Service($requestBuilder, $webClient);
        $verify = $service->verify($requestId);
        if (! $verify->getStatus()->isAccepted()) {
            DescargasSolicitudesSat::where('id_sat',$requestId)->update([
                'estado'=>$verify->getStatus()->getMessage(),
                'estatus'=>'Terminado'
            ]);
            return 'Error|'.$verify->getStatus()->getMessage();
        }

        if (! $verify->getCodeRequest()->isAccepted()) {
            DescargasSolicitudesSat::where('id_sat',$requestId)->update([
                'estado'=>$verify->getCodeRequest()->getMessage(),
                'estatus'=>'Terminado'
            ]);
            return 'Error|'.$verify->getCodeRequest()->getMessage();
        }

        $statusRequest = $verify->getStatusRequest();
        if ($statusRequest->isExpired() || $statusRequest->isFailure() || $statusRequest->isRejected()) {
        DescargasSolicitudesSat::where('id_sat',$requestId)->update([
                'estado'=>'Error, Solicitud Invalida',
                'estatus'=>'Terminado'
            ]);
            return 'Error|La Solicitud No se pudo completar';
        }
        if ($statusRequest->isInProgress() || $statusRequest->isAccepted()) {
            DescargasSolicitudesSat::where('id_sat',$requestId)->update([
                'estado'=>'Procesando Descarga'
            ]);
            return 'Proceso|La Solicitud se sigue Procesando';
        }

        foreach ($verify->getPackagesIds() as $packageId) {
            $download = $service->download($packageId);
            if (! $download->getStatus()->isAccepted()) {
                continue;
            }
            $zipfile = \storage_path().'/app/public/zipdescargas/'."$packageId.zip";
            file_put_contents($zipfile, $download->getPackageContent());
            DescargasArchivosSat::create([
                'id_sat'=>$requestId,
                'team_id'=>$team_id,
                'fecha'=>Carbon::now(),
                'archivo'=>$zipfile,
                'estado'=>'Pendiente'
            ]);
        }
        DescargasSolicitudesSat::where('id_sat',$requestId)->update([
            'estado'=>'Archivo Descargado',
            'estatus'=>'Terminado'
        ]);
        return 'Exito|Terminado';
    }

    public function extrae_archivos($zipfile,$solicitud,$id_zip,$team,$taxid): void
    {
        $archivo = $zipfile;
        $desfile = \storage_path().'/app/public/zipdescargas/'.$solicitud.'/';
        $zip = new \ZipArchive;
        $msj = '';
        if ($zip->open($archivo) === TRUE) {
            $zip->extractTo($desfile);
            $zip->close();
            $msj = 'Extraido';
            DescargasArchivosSat::where('id',$id_zip)->update([
                'estado'=>$msj
            ]);
            $this->ProcesaArchivoZ_F($desfile,$team,$taxid);
        } else {
            $msj = 'Error al extraer';
            DescargasArchivosSat::where('id',$id_zip)->update([
                'estado'=> $msj
            ]);
        }
    }

    public function ProcesaArchivoZ_F($archivo,$team,$taxid): int
    {
        $files = array_diff(scandir($archivo), array('.', '..'));
        $contador = 0;
        foreach($files as $desfile)
        {
            $file = $archivo.$desfile;
            try {
                $xmlContents = \file_get_contents($file);
                $cfdi = Cfdi::newFromString($xmlContents);
                $comprobante = $cfdi->getNode();
                //dd($comprobante);
                $emisor = $comprobante->searchNode('cfdi:Emisor');
                $receptor = $comprobante->searchNode('cfdi:Receptor');
                $tfd = $comprobante->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
                $pagoscom = $comprobante->searchNode('cfdi:Complemento', 'pago20:Pagos');
                $impuestos = $comprobante->searchNode('cfdi:Impuestos');
                $tipocom = $comprobante['TipoDeComprobante'];
                $subtotal = 0;
                $descuento = 0;
                $traslado = 0;
                $retencion = 0;
                $total = 0;
                $tipocambio = 0;
                    if ($tipocom != 'P') {
                        $subtotal = floatval($comprobante['SubTotal']);
                        $descuento = floatval($comprobante['Descuento']);
                        if (isset($impuestos['TotalImpuestosTrasladados'])) $traslado = floatval($impuestos['TotalImpuestosTrasladados']);
                        if (isset($impuestos['TotalImpuestosRetenidos'])) $retencion = floatval($impuestos['TotalImpuestosRetenidos']);
                        $total = floatval($comprobante['Total']);
                        $tipocambio = floatval($comprobante['TipoCambio']);
                    }
                    else
                    {
                        if (!isset($pagoscom)) {
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
                $xmlContenido = \file_get_contents($file, false);
                //dd($xmlContenido);
                $fech = $comprobante['Fecha'];
                list($fechacom, $horacom) = explode('T', $fech);
                list($aniocom, $mescom, $diacom) = explode('-', $fechacom);
                $tiposol = "NO IDENTIFICADO";
                if ($emisor['Rfc'] == $taxid) $tiposol = "Emitidos";
                else if ($receptor['Rfc'] == $taxid) $tiposol = "Recibidos";
                if ($tiposol == 'Emitidos') {
                    if ($emisor['Rfc'] == $taxid) {
                        //$uuidno = count(Almacencfdis::where(['UUID' => $tfd['UUID'], 'team_id' => Filament::getTenant()->id])->get() ?? 0);
                        $uuid_v = Almacencfdis::where('UUID',$tfd['UUID'])->where('team_id',$team)->exists();
                        if (!$uuid_v) {
                            Almacencfdis::firstOrCreate([
                                'Serie' => $comprobante['Serie'],
                                'Folio' => $comprobante['Folio'],
                                'Version' => $comprobante['Version'],
                                'Fecha' => $comprobante['Fecha'],
                                'Moneda' => $comprobante['Moneda'],
                                'TipoDeComprobante' => $comprobante['TipoDeComprobante'],
                                'MetodoPago' => $comprobante['MetodoPago'],
                                'Emisor_Rfc' => $emisor['Rfc'],
                                'Emisor_Nombre' => $emisor['Nombre'],
                                'Emisor_RegimenFiscal' => $emisor['RegimenFiscal'],
                                'Receptor_Rfc' => $receptor['Rfc'],
                                'Receptor_Nombre' => $receptor['Nombre'],
                                'Receptor_RegimenFiscal' => $receptor['RegimenFiscal'],
                                'UUID' => $tfd['UUID'],
                                'Total' => $total,
                                'SubTotal' => $subtotal,
                                'Descuento' => $descuento,
                                'TipoCambio' => $tipocambio,
                                'TotalImpuestosTrasladados' => $traslado,
                                'TotalImpuestosRetenidos' => $retencion,
                                'content' => $xmlContenido,
                                'user_tax' => $emisor['Rfc'],
                                'used' => 'NO',
                                'xml_type' => $tiposol,
                                'periodo' => intval($mescom),
                                'ejercicio' => intval($aniocom),
                                'team_id' => $team
                            ]);
                            Xmlfiles::firstOrCreate([
                                'taxid' => $emisor['Rfc'],
                                'uuid' => $tfd['UUID'],
                                'content' => $xmlContenido,
                                'periodo' => $mescom,
                                'ejercicio' => $aniocom,
                                'tipo' => $tiposol,
                                'solicitud' => 'Importacion',
                                'team_id' => $team
                            ]);
                        }
                    }
                } else {
                    if ($receptor['Rfc'] == $taxid) {
                        //$uuidno = count(Almacencfdis::where(['UUID' => $tfd['UUID'], 'team_id' => Filament::getTenant()->id])->get() ?? 0);
                        $uuid_v = Almacencfdis::where('UUID',$tfd['UUID'])->where('team_id',$team)->exists();
                        if (!$uuid_v){
                            Almacencfdis::firstOrCreate([
                                'Serie' => $comprobante['Serie'],
                                'Folio' => $comprobante['Folio'],
                                'Version' => $comprobante['Version'],
                                'Fecha' => $comprobante['Fecha'],
                                'Moneda' => $comprobante['Moneda'],
                                'TipoDeComprobante' => $comprobante['TipoDeComprobante'],
                                'MetodoPago' => $comprobante['MetodoPago'],
                                'Emisor_Rfc' => $emisor['Rfc'],
                                'Emisor_Nombre' => $emisor['Nombre'],
                                'Emisor_RegimenFiscal' => $emisor['RegimenFiscal'],
                                'Receptor_Rfc' => $receptor['Rfc'],
                                'Receptor_Nombre' => $receptor['Nombre'],
                                'Receptor_RegimenFiscal' => $receptor['RegimenFiscal'],
                                'UUID' => $tfd['UUID'],
                                'Total' => $total,
                                'SubTotal' => $subtotal,
                                'Descuento' => $descuento,
                                'TipoCambio' => $tipocambio,
                                'TotalImpuestosTrasladados' => $traslado,
                                'TotalImpuestosRetenidos' => $retencion,
                                'content' => $xmlContenido,
                                'user_tax' => $emisor['Rfc'],
                                'used' => 'NO',
                                'xml_type' => $tiposol,
                                'periodo' => intval($mescom),
                                'ejercicio' => intval($aniocom),
                                'team_id' => $team
                            ]);
                            Xmlfiles::firstOrCreate([
                                'taxid' => $emisor['Rfc'],
                                'uuid' => $tfd['UUID'],
                                'content' => $xmlContenido,
                                'periodo' => $mescom,
                                'ejercicio' => $aniocom,
                                'tipo' => $tiposol,
                                'solicitud' => 'Importacion',
                                'team_id' => $team
                            ]);
                        }
                    }
                }
                $contador++;
            }
            catch (\Exception $e) {
                dd($file,$e->getMessage());
            }
            //-----------------------------------------
        }
        return $contador;
    }
}
