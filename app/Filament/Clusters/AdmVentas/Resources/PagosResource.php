<?php

namespace App\Filament\Clusters\AdmVentas\Resources;

use App\Filament\Clusters\AdmVentas;
use App\Filament\Clusters\AdmVentas\Resources\PagosResource\Pages;
use App\Filament\Clusters\AdmVentas\Resources\PagosResource\RelationManagers;
use App\Http\Controllers\TimbradoController;
use App\Models\Clientes;
use App\Models\CuentasCobrar;
use App\Models\DatosFiscales;
use App\Models\Facturas;
use App\Models\Pagos;
use App\Models\ParPagos;
use Carbon\Carbon;
use CfdiUtils\Cleaner\Cleaner;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PagosResource extends Resource
{
    protected static ?string $model = Pagos::class;
    protected static ?string $pluralLabel = "Comprobantes de Pago";
    protected static ?string $Label = "Comprobantes de Pago";
    protected static ?string $navigationIcon = 'fas-money-bill-transfer';
    protected static ?string $cluster = AdmVentas::class;
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                        Forms\Components\Fieldset::make('Datos Generales')->schema([
                            Forms\Components\Hidden::make('estado')->default('Activa'),
                            Forms\Components\Hidden::make('dat_fiscal')
                                ->default(DatosFiscales::where('team_id', Filament::getTenant()->id)->first()->id),
                            Forms\Components\TextInput::make('dat_fiscal_em')
                                ->label('Emisor')->readOnly()
                                ->formatStateUsing(function (){
                                    return DatosFiscales::where('team_id', Filament::getTenant()->id)->first()->rfc;
                                }),
                            Forms\Components\Hidden::make('serie')->default('P'),
                            Forms\Components\TextInput::make('folio')
                                ->label('Folio')
                                ->required()
                                ->numeric()
                                ->default(Pagos::where('team_id', Filament::getTenant()->id)->max('folio') + 1)
                                ->readOnly(),
                            Forms\Components\DatePicker::make('fecha_doc')
                                ->label('Fecha Comprobante')
                                ->required()->readOnly()
                                ->afterStateHydrated(function (Forms\Components\DatePicker $component, ?string $state) {
                                    // if the value is empty in the database, set a default value, if not, just continue with the default component hydration
                                    if (!$state) {
                                        $component->state(now()->toDateString());
                                    }
                                }),
                            Forms\Components\DatePicker::make('fechapago')
                                ->label('Fecha del Pago')
                                ->required()
                                ->afterStateHydrated(function (Forms\Components\DatePicker $component, ?string $state) {
                                    // if the value is empty in the database, set a default value, if not, just continue with the default component hydration
                                    if (!$state) {
                                        $component->state(now()->toDateString());
                                    }
                                }),
                            Forms\Components\Hidden::make('clave_doc'),
                            Forms\Components\Select::make('cve_clie')
                                ->label('Cliente')
                                ->required()
                                ->columnspan(2)
                                ->live()
                                ->options(Clientes::all()->pluck('nombre', 'id')),
                            Forms\Components\Hidden::make('team_id')
                                ->default(Filament::getTenant()->id),
                            Forms\Components\Select::make('forma')
                                ->label('Forma de Pago')->required()
                                ->options(DB::table('metodos')->pluck('mostrar', 'clave')),
                            Forms\Components\TextInput::make('subtotal')
                                ->required()
                                ->numeric()
                                //->default(0.00000000)
                                ->readOnly()->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:2)
                                ->placeholder(function (Forms\Get $get, Forms\Set $set) {
                                    $valor = collect($get('Partidas'))->pluck('baseiva')->sum();
                                    $set('subtotal', $valor);
                                    return floatval($valor);
                                }),
                            Forms\Components\TextInput::make('iva')
                                ->required()
                                ->numeric()->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:2)
                                //->default(0.00000000)
                                ->readOnly()
                                ->placeholder(function (Forms\Get $get, Forms\Set $set) {
                                    $valor = collect($get('Partidas'))->pluck('montoiva')->sum();
                                    $set('iva', $valor);
                                    return floatval($valor);
                                }),
                            Forms\Components\TextInput::make('total')
                                ->required()
                                ->numeric()->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:2)
                                //->default(0.00000000)
                                ->readOnly()
                                ->placeholder(function (Forms\Get $get, Forms\Set $set) {
                                    $valor = collect($get('Partidas'))->pluck('imppagado')->sum();
                                    $set('total', $valor);
                                    return floatval($valor);
                                }),
                            Forms\Components\Select::make('moneda')
                                ->options(['XXX'=>'MXN','USD'=>'USD'])
                                ->default('XXX')->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                if($get('moneda') == 'XXX') $set('tcambio', 1);
                            }),
                            Forms\Components\TextInput::make('tcambio')
                                ->label('Tipo de Cambio')
                                ->required()
                                ->numeric()->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:4)
                                ->default(1)
                                ->readOnly(function (Forms\Get $get) {
                                    if($get('moneda') == 'XXX') return true;
                                    else return false;
                                }),
                            Forms\Components\Hidden::make('usocfdi')
                                ->default('CP01'),

                        ])->columns(6),
                        Forms\Components\Fieldset::make('Pagos')->schema([
                            Forms\Components\Repeater::make('Partidas')
                                ->label('Partidas')
                                ->relationship()
                                ->collapsible()
                                ->itemLabel(fn(array $state): ?string => $state['uuidrel'] ?? null)
                                ->schema([
                                    Forms\Components\Select::make('uuidrel')
                                        ->label('Factura')
                                        ->options(fn(Forms\Get $get): Collection => Facturas::query()
                                            ->select(DB::raw("id,CONCAT(serie,folio) as folio"))
                                            ->where('clie', $get('../../cve_clie'))
                                            ->where('estado','Timbrada')
                                            ->where('forma','PPD')
                                            ->where('pendiente_pago','>',0)
                                            ->pluck('folio', 'id'))
                                        ->live()
                                        ->afterStateUpdated(
                                            function (Forms\Get $get, Forms\Set $set) {
                                                $facturas = Facturas::where('id', $get('uuidrel'))->get();
                                                //dd($facturas);
                                                $total = $facturas[0]->pendiente_pago;
                                                $set('tasaiva', 0.16);
                                                $set('unitario', $total);
                                                $set('importe', $total);
                                                $set('saldoant', $total);
                                                $set('imppagado', $total);
                                                $subt = $total / (1 + $get('tasaiva'));
                                                $iva = $subt * 0.16;
                                                $set('baseiva', $subt);
                                                $set('montoiva', $iva);
                                                $set('insoluto', 0);
                                                $set('tasaiva', 0.16);
                                                $set('moneda', $facturas[0]->moneda);
                                            }
                                        ),
                                    Forms\Components\Hidden::make('unitario')
                                        ->default(0),
                                    Forms\Components\Hidden::make('importe')
                                        ->default(0),
                                    Forms\Components\Select::make('moneda')
                                        ->options(['MXN'=>'MXN','USD'=>'USD'])
                                        ->default('MXN'),
                                    Forms\Components\TextInput::make('saldoant')
                                        ->label('Saldo Anterior')
                                        ->numeric()
                                        ->default(0)->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:6),
                                    Forms\Components\TextInput::make('imppagado')
                                        ->label('Monto del Pago')
                                        ->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:6)
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                            $ante = $get('saldoant');
                                            $imp = $get('imppagado');
                                            $subt = $ante - $imp;
                                            $iva = (($imp / 1.16) * 0.16);
                                            $set('baseiva', round(($imp / 1.16),6));
                                            $set('montoiva', round($iva,6));
                                            $set('insoluto', $subt);
                                            $set('tasaiva', 0.16);
                                        }),
                                    Forms\Components\TextInput::make('insoluto')
                                        ->label('Saldo Insoluto')
                                        ->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:2)
                                        ->default(0),
                                    Forms\Components\TextInput::make('equivalencia')
                                        ->default(1)->numeric()->prefix('$')->currencyMask(decimalSeparator:'.',precision:2),
                                    Forms\Components\TextInput::make('parcialidad')
                                        ->default(1)->numeric(),
                                    Forms\Components\Hidden::make('objeto')
                                        ->default('02'),
                                    Forms\Components\Hidden::make('tasaiva')
                                        ->default(16),
                                    Forms\Components\Hidden::make('baseiva')
                                        ->default(0),
                                    Forms\Components\Hidden::make('montoiva')
                                        ->default(0),
                                    Forms\Components\Hidden::make('team_id')
                                        ->default(Filament::getTenant()->id),
                                ])->columns(7)->columnSpanFull()
                        ])->columnspanfull(),
                Select::make('tipo_compro')->label('Tipo de comprobante')
                ->options(['MUL'=>'Multi Nodo','UNI'=>'Unico Nodo'])
                ->default('UNI')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serie')
                    ->label('Documento')
                    ->searchable()
                ->formatStateUsing(function ($record){
                    return $record->serie.$record->folio;
                }),
                Tables\Columns\TextColumn::make('cve_clie')
                    ->label('Cliente')
                    ->searchable()
                    ->state(function ($record): string {
                        $clientes = Clientes::where('id', $record->cve_clie)->get();
                        return $clientes[0]->nombre;
                    }),
                Tables\Columns\TextColumn::make('fecha_doc')
                    ->label('Fecha')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->numeric()->currency('USD',true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('iva')
                    ->numeric()->currency('USD',true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()->currency('USD',true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Consultar')
                    ->modalWidth('7xl'),
                    Tables\Actions\Action::make('Cancelar')
                        ->icon('fas-ban')
                        ->color('danger')
                        ->form([
                            Select::make('motivo')
                                ->label('Motivo')->options([
                                    '01'=>'01 - Comprobante emitido con errores con relación',
                                    '02'=>'02 - Comprobante emitido con errores sin relación',
                                    '03'=>'03 - No se llevó a cabo la operación ',
                                    '04'=>'04 - Operación nominativa relacionada en una factura global'
                                ])->live(onBlur: true)
                                ->default('02'),
                            Select::make('Folio')
                                ->disabled(fn(Get $get) => $get('motivo') != '01')
                                ->label('Folio Sustituye')->options(
                                    Pagos::where('estado','Timbrada')
                                        ->where('team_id',Filament::getTenant()->id)
                                        ->select(DB::raw("concat(serie,folio) as Folio,uuid"))
                                        ->pluck('Folio','uuid')
                                )
                        ])
                        ->action(function (Model $record,$data) {
                            $est = $record->estado;
                            $factura = $record->id;
                            $receptor = $record->cve_clie;
                            $folio = $data['Folio'] ?? null;
                            if($est == 'Activa'||$est == 'Timbrada') {

                                if ($est == 'Timbrada') {
                                    $res = app(TimbradoController::class)->CancelarFactura($factura, $receptor, $data['motivo'], $folio);
                                    $resultado = json_decode($res);

                                    if ($resultado->codigo == 201) {
                                        Pagos::where('id', $record->id)->update([
                                            'fecha_cancela' => Carbon::now(),
                                            'motivo' => $data['motivo'],
                                            'sustituye' => $folio,
                                            'xml_cancela' => $resultado->acuse,
                                        ]);
                                        $partidas_pagos = ParPagos::where('pagos_id',$factura)->get();
                                        foreach($partidas_pagos as $partida){
                                            Facturas::where('id',$partida->uuidrel)->increment('pendiente_pago', $partida->imppagado);
                                        }
                                        Notification::make()
                                            ->title($resultado->mensaje)
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title($resultado->mensaje)
                                            ->warning()
                                            ->send();
                                    }
                                }
                                Pagos::where('id', $record->id)->update([
                                    'estado' => 'Cancelada'
                                ]);
                                Notification::make()
                                    ->title('Comprobante Cancelado')
                                    ->success()
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('Imprimir')
                        ->icon('fas-print')
                        ->color('warning')
                        ->action(function ($record,$livewire) {
                            $livewire->idorden = $record->id;
                            $livewire->id_empresa = Filament::getTenant()->id;
                            $livewire->getAction('Imprimir_Doc_P')->visible(true);
                            $livewire->replaceMountedAction('Imprimir_Doc_P');
                            $livewire->getAction('Imprimir_Doc_P')->visible(false);
                        }),
                    Tables\Actions\Action::make('Timbrar')
                    ->icon('fas-bell-concierge')
                    ->disabled(fn($record) => $record->estado != 'Activa')
                    ->action(function (Pagos $record) {
                        $data = $record;
                        $factura = $record->id;
                        $receptor = $data->cve_clie;
                        $emisor = $data->dat_fiscal;
                        $serie = $data->serie;
                        //DB::statement("UPDATE series_facs SET folio = folio + 1 WHERE id = $serie");
                        if($record->tipo_compro == 'MUL')
                            $res = app(TimbradoController::class)->TimbrarPagos($factura,$emisor,$receptor);
                        else
                            $res = app(TimbradoController::class)->TimbrarPagos_Uni($factura,$emisor,$receptor);
                        $resultado = json_decode($res);
                        $codigores = $resultado->codigo;
                        if($codigores == "200")
                        {
                            $partidas_pagos = ParPagos::where('pagos_id',$factura)->get();
                            //$pdf_file = app(TimbradoController::class)->genera_pdf($resultado->cfdi);
                            $date = new \DateTime('now', new \DateTimeZone('America/Mexico_City'));
                            $facturamodel = Pagos::where('id',$factura)->first();
                            $facturamodel->timbrado = 'SI';
                            $facturamodel->xml = $resultado->cfdi;
                            $facturamodel->fecha_tim = $date;
                            //$facturamodel->pdf_file = $pdf_file;
                            $facturamodel->save();
                            $res2 = app(TimbradoController::class)->actualiza_pag_tim($factura,$resultado->cfdi,"P");
                            $mensaje_tipo = "1";
                            $mensaje_graba = 'Comprobante Timbrado Se genero el CFDI UUID: '.$res2;
                            foreach($partidas_pagos as $partida){
                                Facturas::where('id',$partida->uuidrel)->decrement('pendiente_pago', $partida->imppagado);
                            }
                            Notification::make()
                                ->success()
                                ->title('Pago Timbrado Correctamente')
                                ->body($mensaje_graba)
                                ->duration(2000)
                                ->send();
                        }
                        else{
                            $mensaje_tipo = "2";
                            $mensaje_graba = $resultado->mensaje;
                            dd($resultado);
                            Notification::make()
                                ->warning()
                                ->title('Error al Timbrar el Documento '.$resultado->mensaje)
                                ->body($mensaje_graba)
                                ->persistent()
                                ->send();
                        }
                    }),
                    Tables\Actions\Action::make('Descargar  XML')
                        ->icon('fas-download')
                        ->color('warning')
                        ->action(function (Pagos $record) {
                            $emp = DatosFiscales::where('team_id',$record->team_id)->first();
                            $cli = Clientes::where('id',$record->cve_clie)->first();
                            $nombre = $emp->rfc.'_COMPROBANTE_CFDI_'.$record->serie.$record->folio.'_'.$cli->rfc.'.xml';
                            $archivo = $_SERVER["DOCUMENT_ROOT"].'/storage/TMPXMLFiles/'.$nombre;
                            if (file_exists($archivo)) {
                                unlink($archivo);
                            }
                            $xml = $record->xml;
                            $xml = Cleaner::staticClean($xml);
                            File::put($archivo,$xml);
                            return response()->download($archivo);
                        }),
                    /*Tables\Actions\Action::make('Enviar por Correo')
                        ->icon('fas-envelope-square')
                        ->action(function (Pagos $record) {
                            Self::envia_correo($record->clave_doc,
                                $record->emisor, $record->cve_clie);

                            Notification::make('Enviar por Correo')
                                ->title('Envio de Correo')
                                ->body('Correo Enviado Correctamente')
                                ->success()
                                ->send();
                        })->close()*/
                ])->dropdownPlacement('top-start')
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            //->recordUrl(fn(Pagos $record): string => Pages\ViewPagos::getUrl([$record->id]))
        ->headerActions([
            Tables\Actions\CreateAction::make('Agregar')
            ->icon('fas-circle-plus')
            ->label('Agregar')->modalSubmitActionLabel('Grabar')
            ->modalCancelActionLabel('Cerrar')
            ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color('success')->icon('fas-save'))
            ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color('danger')->icon('fas-ban'))
            ->createAnother(false)
            ->modalWidth('7xl')
            ->after(function($record,$data){
                $data = $record;
                $factura = $record->getKey();
                $receptor = $data->cve_clie;
                $emisor = $data->dat_fiscal;
                $serie = $data->serie;
                //DB::statement("UPDATE series_facs SET folio = folio + 1 WHERE id = $serie");
                if($data['tipo_compro'] == 'MUL')
                    $res = app(TimbradoController::class)->TimbrarPagos($factura,$emisor,$receptor);
                else
                    $res = app(TimbradoController::class)->TimbrarPagos_Uni($factura,$emisor,$receptor);
                $resultado = json_decode($res);
                $codigores = $resultado->codigo;
                if($codigores == "200")
                {
                    $partidas_pagos = ParPagos::where('pagos_id',$factura)->get();
                    foreach($partidas_pagos as $partida){
                        $fact_pag = Facturas::where('id',$partida->uuidrel)->first();
                        Facturas::where('id',$partida->uuidrel)->decrement('pendiente_pago', $partida->imppagado);
                        CuentasCobrar::where('team_id',Filament::getTenant()->id)->where('concepto',1)->where('documento',$fact_pag->docto)->decrement('saldo',$partida->imppagado);
                        CuentasCobrar::create([
                            'cliente'=>$record->cve_clie,
                            'concepto'=>9,
                            'descripcion'=>'Pago Factura',
                            'documento'=>$record->serie.$record->folio,
                            'fecha'=>Carbon::now(),
                            'vencimiento'=>Carbon::now(),
                            'importe'=>$partida->imppagado,
                            'saldo'=> 0,
                            'team_id'=>Filament::getTenant()->id,
                            'refer'=>$fact_pag->id
                        ]);
                    }
                    $pdf_file = app(TimbradoController::class)->genera_pdf($resultado->cfdi);
                    $date = new \DateTime('now', new \DateTimeZone('America/Mexico_City'));
                    $facturamodel = Pagos::where('id',$factura)->first();
                    $facturamodel->timbrado = 'SI';
                    $facturamodel->xml = $resultado->cfdi;
                    $facturamodel->fecha_tim = $date;
                    $facturamodel->pdf_file = $pdf_file;
                    $facturamodel->save();
                    $res2 = app(TimbradoController::class)->actualiza_pag_tim($factura,$resultado->cfdi,"P");
                    $mensaje_tipo = "1";
                    $mensaje_graba = 'Comprobante Timbrado Se genero el CFDI UUID: '.$res2;
                    Notification::make()
                        ->success()
                        ->title('Pago Timbrado Correctamente')
                        ->body($mensaje_graba)
                        ->duration(2000)
                        ->send();
                }
                else{
                    $mensaje_tipo = "2";
                    $mensaje_graba = $resultado->mensaje;
                    Notification::make()
                        ->warning()
                        ->title('Error al Timbrar el Documento')
                        ->body($mensaje_graba)
                        ->persistent()
                        ->send();
                }
            })
        ],Tables\Actions\HeaderActionsPosition::Bottom);
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
            'index' => Pages\ListPagos::route('/'),
            //'create' => Pages\CreatePagos::route('/create'),
            //'edit' => Pages\EditPagos::route('/{record}/edit'),
        ];
    }
}
