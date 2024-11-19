<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComprasResource\Pages;
use App\Filament\Resources\ComprasResource\RelationManagers;
use App\Models\Compras;
use App\Models\Productos;
use App\Models\Seriesfac;
use App\Models\Team;
use App\Models\Terceros;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use DateTime;
use DateTimeZone;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ComprasResource extends Resource
{
    protected static ?string $model = Compras::class;
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $label = 'Compra';
    protected static ?string $pluralLabel = 'Compras';
    protected static ?string $navigationIcon = 'fas-cart-shopping';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Tabs::make()->columnSpan('full')
                    ->tabs([
                        Tab::make('Datos Generales')->schema([
                        Forms\Components\Hidden::make('estado')->default('Activa'),
                        Forms\Components\Select::make('emisor')
                            ->label('Emisor')
                            ->required()
                            ->options(Team::all()->pluck('name','id'))
                            ->default(Filament::getTenant()->id)
                            ->dehydrated(false),
                        Forms\Components\Select::make('serie')
                            ->label('Serie')
                            ->required()
                            ->options(Seriesfac::where(['tipo'=>'Compra','team_id'=>Filament::getTenant()->id])->pluck('serie','id'))
                            ->live()
                            ->afterStateUpdated(function(Get $get, Set $set){
                                $series = Seriesfac::where('id',$get('serie'))->get();
                                $folio = $series[0]->folio;
                                $serie = $series[0]->serie;
                                $folio++;
                                $set('folio',$folio);
                                $set('clave_doc',$serie.''.$folio);
                            }),
                        Forms\Components\TextInput::make('folio')
                            ->label('Folio')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->readOnly(),
                        Forms\Components\DatePicker::make('fecha_doc')
                            ->label('Fecha de la Factura')
                            ->required()
                            ->afterStateHydrated(function (DatePicker $component, ?string $state) {
                                // if the value is empty in the database, set a default value, if not, just continue with the default component hydration
                                if (!$state) {
                                    $component->state(now()->toDateString());
                                }
                            }),
                        Forms\Components\TextInput::make('por_des')
                            ->label('Descuento')
                            ->required()
                            ->numeric()
                            ->default(0.00000000)
                            ->postfix('%'),
                        Forms\Components\Hidden::make('almacen')
                        ->default(0),
                        Forms\Components\Hidden::make('clave_doc'),
                        Forms\Components\Select::make('cve_clie')
                        ->label('Proveedor')
                        ->required()
                        ->columnspan(2)
                        ->options(Terceros::where('tipo','Proveedor')->pluck('nombre','id')),
                        Forms\Components\Hidden::make('metodo')
                            ->default(0),
                        Forms\Components\Hidden::make('forma')
                            ->default(0),
                        Forms\Components\Hidden::make('usocfdi')
                            ->default(0),
                        ])->columns(6),
                        Tab::make('Impuestos')
                        ->visible(false)
                        ->schema([
                            Section::make('IVA')->schema([
                                Forms\Components\TextInput::make('por_im1')
                                    ->label('Tasa de IVA')
                                    ->required()
                                    ->numeric()
                                    ->default(16)
                                    ->reactive()
                                    ->postfix('%'),
                                Forms\Components\TextInput::make('impuesto1')
                                    ->label('Importe de IVA')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly()
                                    ->prefix('$')
                            ])->columns(2)->columnspan(2),
                            Section::make('Retencion IVA')->schema([
                                Forms\Components\TextInput::make('por_im2')
                                    ->label('Tasa de Retencion')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    ->postfix('%'),
                                Forms\Components\TextInput::make('impuesto2')
                                    ->label('Importe de Retencion')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly()
                                    ->prefix('$')
                            ])->columns(2)->columnspan(2),
                            Section::make('Retencion ISR')->schema([
                                Forms\Components\TextInput::make('por_im3')
                                    ->label('Tasa de Retencion')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    ->postfix('%'),
                                Forms\Components\TextInput::make('impuesto3')
                                    ->label('Importe de Retencion')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly()
                                    ->prefix('$')
                            ])->columns(2)->columnspan(2),
                            Section::make('IEPS')->schema([
                                Forms\Components\TextInput::make('por_im4')
                                    ->label('Tasa')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->reactive()
                                    ->postfix('%'),
                                Forms\Components\TextInput::make('impuesto4')
                                    ->label('Importe')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly()
                                    ->prefix('$')
                            ])->columns(2)->columnspan(2)
                        ])->columns(4),
                    ]),
                    Section::make()->schema([
                    TableRepeater::make('Partidas')
                    ->emptyLabel('No existen Partidas')
                    ->headers([
                        Header::make('cant')->width('50px')->label('Cantidad'),
                        Header::make('id_prod')->width('200px')->label('Producto'),
                        Header::make('precio')->width('100px')->label('Precio Unitario'),
                        Header::make('impuesto1')->width('100px')->label('IVA'),
                        Header::make('impuesto2')->width('100px')->label('Retencion IVA'),
                        Header::make('impuesto3')->width('100px')->label('Retencion ISR'),
                        Header::make('impuesto4')->width('100px')->label('IEPS'),
                        Header::make('subtotal')->width('100px')->label('SubTotal'),
                    ])
                    ->label('Partidas')
                    ->relationship()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['descripcion'] ?? null)
                    ->schema([
                        Forms\Components\Hidden::make('serie'),
                        Forms\Components\Hidden::make('folio'),
                        Forms\Components\Hidden::make('clave_doc'),
                        Forms\Components\Hidden::make('cve_clie'),
                        Forms\Components\Hidden::make('fecha_doc'),
                        Forms\Components\Hidden::make('clave'),
                        Forms\Components\Hidden::make('descripcion'),
                        Forms\Components\Hidden::make('unidad'),
                        Forms\Components\Hidden::make('impuesto1'),
                        Forms\Components\Hidden::make('impuesto2'),
                        Forms\Components\Hidden::make('impuesto3'),
                        Forms\Components\Hidden::make('impuesto4'),
                        Forms\Components\Hidden::make('descuento'),
                        Forms\Components\Hidden::make('por_des')
                        ->default(0),
                        Forms\Components\Hidden::make('cvesat'),
                        Forms\Components\Hidden::make('unisat'),
                        Forms\Components\Hidden::make('total'),
                        Forms\Components\Hidden::make('team_id')
                            ->default(Filament::getTenant()->id),
                        Forms\Components\TextInput::make('cant')
                            ->label('Cantidad')
                            ->numeric()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function(Set $set, Get $get){
                                    Self::TotalPartida($get, $set);

                                }
                            ),
                        Forms\Components\Select::make('id_prod')
                            ->options(Productos::all()->pluck('descripcion','id'))
                            ->label('Descripcion')
                            ->live()
                            ->columnspan(2)
                            ->afterStateUpdated(
                                function(Set $set, Get $get){
                                    $prod = Productos::where('id',$get('id_prod'))->get();
                                    $cant = $get('cant');
                                    $set('precio',$prod[0]->costo_u);
                                    $set('cvesat',$prod[0]->cvesat);
                                    $set('unisat',$prod[0]->unisat);
                                    $set('unidad',$prod[0]->unisat);
                                    $set('descripcion',$prod[0]->descripcion);
                                    $set('clave',$prod[0]->clave);
                                    $set('serie',$get('../../serie'));
                                    $set('folio',$get('../../folio'));
                                    $set('clave_doc',$get('../../clave_doc'));
                                    $set('cve_clie',$get('../../cve_clie'));
                                    $set('fecha_doc',$get('../../fecha_doc'));
                                    Self::TotalPartida($get, $set);
                                }
                            ),
                        Forms\Components\TextInput::make('precio')
                            ->label('Costo Unitario')
                            ->numeric()
                            ->live(onBlur: true)
                            ->prefix('$')
                            ->afterStateUpdated(
                                function(Set $set, Get $get){
                                    Self::TotalPartida($get, $set);
                                }
                            ),
                        Forms\Components\TextInput::make('por_im1')
                            ->label('IVA')
                            ->numeric()
                            ->suffix('%')
                            ->default(16)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function(Set $set, Get $get){
                                    Self::TotalPartida($get, $set);
                                }
                            ),
                        Forms\Components\TextInput::make('por_im2')
                            ->label('Retencion IVA')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function(Set $set, Get $get){
                                    Self::TotalPartida($get, $set);
                                }
                            ),
                        Forms\Components\TextInput::make('por_im3')
                            ->label('Retencion ISR')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function(Set $set, Get $get){
                                    Self::TotalPartida($get, $set);
                                }
                            ),
                        Forms\Components\TextInput::make('por_im4')
                            ->label('IEPS')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)->live(onBlur: true)
                            ->afterStateUpdated(
                                function(Set $set, Get $get){
                                    Self::TotalPartida($get, $set);
                                }
                            ),
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->reactive()
                            ->prefix('$')
                            ->numeric(),
                    ])->columnspanfull()->defaultItems(0)->columns(5)->addActionLabel('Agregar Partida')
                ])->columnspanfull(),
                Split::make([
                        Section::make('Totales')
                        ->schema([
                            Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->readOnly()
                            ->prefix('$')
                            ->placeholder(function (Get $get,Set $set) {
                                $valor = collect($get('Partidas'))->pluck('subtotal')->sum();
                                Self::updateTotals($get,$set);
                                return floatval($valor);
                            }),
                        Forms\Components\TextInput::make('traslados')
                            ->label('Traslados')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->readOnly()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('retenciones')
                            ->label('Retenciones')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->readOnly()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('descuento')
                            ->label('Descuento')
                            ->required()
                            ->numeric()
                            ->default(0.00000000)
                            ->readOnly()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('total')
                            ->label('Importe Total')
                            ->required()
                            ->numeric()
                            ->default(0.00000000)
                            ->readOnly()
                            ->prefix('$'),
                        Forms\Components\Hidden::make('estado')
                            ->default('Activa'),
                        Forms\Components\Hidden::make('team_id')
                            ->default(Filament::getTenant()->id)
                        ])->columns(3),
                    Section::make('Adicionales')
                    ->schema([
                        Forms\Components\TextInput::make('condiciones')
                            ->maxLength(1000),
                        Forms\Components\TextInput::make('observaciones')
                            ->maxLength(1000),
                    ])
                ])->columnSpanFull()
        ])
        ->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(function (Builder $query) {
            return $query->where('estado', '!=', 'Cancelada');
        })
        ->columns([
            Tables\Columns\TextColumn::make('clave_doc')->label('Factura')
                ->searchable(),
            Tables\Columns\TextColumn::make('cve_clie')->label('Cliente')
                ->searchable()
                ->state(function($record):string {
                    $clientes = Terceros::where('id',$record->cve_clie)->get();
                    return $clientes[0]->nombre;
                }),
            Tables\Columns\TextColumn::make('fecha_doc')->label('Fecha')
                ->dateTime('d-m-Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('subtotal')->label('Subtotal')
                ->toggleable(isToggledHiddenByDefault: true)
                ->prefix('$')
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('impuesto1')->label('IVA')
                ->toggleable(isToggledHiddenByDefault: true)
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('impuesto2')->label('Retencion IVA')
                ->toggleable(isToggledHiddenByDefault: true)
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('impuesto3')->label('Retencion ISR')
                ->toggleable(isToggledHiddenByDefault: true)
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('impuesto4')->label('IEPS')
                ->toggleable(isToggledHiddenByDefault: true)
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('descuento')->label('Descuento')
            ->toggleable(isToggledHiddenByDefault: true)
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('total')->label('Importe')
                ->prefix('$')
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('estado')->label('Estado')
                ->searchable(),
        ])
        ->filters([
            //
        ])
        ->striped()->defaultPaginationPageOption(8)
        ->paginated([8, 'all'])
        ->actions([
            ActionGroup::make([
            Tables\Actions\ViewAction::make()
            ->label('Consultar')
            ->modalWidth('xl2'),
            Tables\Actions\Action::make('Cancelar')
                ->icon('fas-ban')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Compras $record)
                {
                    $record['estado'] = 'Cancelada';
                    $record->update();
                })->close(),
            Tables\Actions\Action::make('Imprimir')
                ->label('Descarga PDF')
                ->icon('fas-file-pdf')
                ->color('green')
                ->visible(function(Model $record){
                    if($record->estado == 'Timbrada') return true;
                    else return false;
                   })
                ->action(function (Model $record){
                    //dd($record->id);
                    if (file_exists($record->uuid.'.pdf')) {
                        unlink($record->uuid.'.pdf');
                    }
                    file_put_contents($record->uuid.'.pdf',base64_decode($record->pdf_file));
                    return response()->download($record->uuid.'.pdf');
                    //echo file_get_contents('tmpprint.pdf');
                }),
            Tables\Actions\Action::make('Des XML')
                ->label('Descarga XML')
                ->icon('fas-file-code')
                ->color('warning')
                ->visible(function(Model $record){
                    if($record->estado == 'Timbrada') return true;
                    else return false;
                   })
                ->action(function (Model $record){
                    //dd($record->id);
                    if (file_exists($record->uuid.'.xml')) {
                        unlink($record->uuid.'.xml');
                    }
                    file_put_contents($record->uuid.'.xml',$record->xml);
                    return response()->download($record->uuid.'.xml');
                    //echo file_get_contents('tmpprint.pdf');
                }),

        Tables\Actions\Action::make('Enviar por Correo')
                ->icon('fas-envelope-square')
                ->visible(function(Model $record){
                    if($record->estado == 'Timbrada') return true;
                    else return false;
                   })
                ->action(function(Compras $record){
                        Self::envia_correo($record->clave_doc,
                        $record->emisor,$record->cve_clie);

                        Notification::make('Enviar por Correo')
                        ->title('Envio de Correo')
                        ->body('Correo Enviado Correctamente')
                        ->success()
                        ->send();
                    })->close(),
            Tables\Actions\Action::make('Timbrar')
                ->visible(function(Model $record){
                 if($record->estado != 'Timbrada') return true;
                 else return false;
                })
                ->icon('fas-bell')
                ->action(function(Compras $record){
                    $data = $record;
                    $estado = $data->estado;
                    /*if($estado != 'Pendiente')
                    {
                        Notification::make()
                            ->warning()
                            ->title('Error al Timbrar el Documento')
                            ->body('El Documento No se puede Timbrar')
                            ->persistent()
                            ->send();
                            return;
                    }*/
                    $factura = $data->id;
                    $receptor = $data->cve_clie;
                    $emisor = $data->emisor;
                    $serie = $data->serie;
                    //$res = app(TimbradoController::class)->TimbrarFactura($factura,$emisor,$receptor,"F");
                    $res = 0;
                    $resultado = json_decode($res);
                    $codigores = $resultado->codigo;
                    if($codigores == "200")
                    {
                        //$pdf_file = app(TimbradoController::class)->genera_pdf($resultado->cfdi);
                        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
                        $facturamodel = Compras::find($factura);
                        $facturamodel->timbrado = 'SI';
                        $facturamodel->xml = $resultado->cfdi;
                        $facturamodel->fecha_tim = $date;
                        //$facturamodel->pdf_file = $pdf_file;
                        $facturamodel->save();
                        //$res2 = app(TimbradoController::class)->actualiza_fac_tim($factura,$resultado->cfdi,"F");
                        $res2 = 0;
                        $mensaje_tipo = "1";
                        $mensaje_graba = 'Factura Timbrada Se genero el CFDI UUID: '.$res2;
                        Notification::make()
                            ->success()
                            ->title('Factura Timbrada Correctamente')
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
                    })->close()
                    ])
                ])->actionsPosition(ActionsPosition::BeforeColumns);
    }

    public static function TotalPartida(Get $get, Set $set): void
    {
        $imp1 = $get('por_im1');
        $imp2 = $get('por_im2');
        $imp3 = $get('por_im3');
        $imp4 = $get('por_im4');
        $desc = $get('por_des');
        /*$set('por_im1',$imp1);
        $set('por_im2',$imp2);
        $set('por_im3',$imp3);
        $set('por_im4',$imp4);
        $set('por_des',$desc);*/
        $cant = floatval($get('cant'));
        $prec = floatval($get('precio'));
        $sub = $cant*$prec;
        $set('subtotal',$sub);
        $descuento = $sub * ($desc*0.01);
        $subt = $sub - $descuento;
        $impuesto1 = $subt * ($imp1*0.01);
        $impuesto2 = $subt * ($imp2*0.01);
        $impuesto3 = $subt * ($imp3*0.01);
        $impuesto4 = $subt * ($imp4*0.01);
        $set('impuesto1',$impuesto1);
        $set('impuesto2',$impuesto2);
        $set('impuesto3',$impuesto3);
        $set('impuesto4',$impuesto4);
        $set('descuento',$descuento);
        $total = $subt + $impuesto1 - $impuesto2 - $impuesto3 + $impuesto4;
        $set('total',$total);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $subtotal = collect($get('Partidas'))->pluck('subtotal')->sum();
        $impuesto1 = collect($get('Partidas'))->pluck('impuesto1')->sum();
        $impuesto2 = collect($get('Partidas'))->pluck('impuesto2')->sum();
        $impuesto3 = collect($get('Partidas'))->pluck('impuesto3')->sum();
        $impuesto4 = collect($get('Partidas'))->pluck('impuesto4')->sum();
        $descuento = collect($get('Partidas'))->pluck('descuento')->sum();
        $total = collect($get('Partidas'))->pluck('total')->sum();
        $set('subtotal',$subtotal);
        $set('impuesto1',$impuesto1);
        $set('impuesto2',$impuesto2);
        $set('impuesto3',$impuesto3);
        $set('impuesto4',$impuesto4);
        $set('descuento',$descuento);
        $traslados = floatval($impuesto1) + floatval($impuesto4);
        $retenciones = floatval($impuesto2) + floatval($impuesto3);
        $set('traslados',$traslados);
        $set('retenciones',$retenciones);
        $set('total',$total);
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
            'index' => Pages\ListCompras::route('/'),
            //'create' => Pages\CreateCompras::route('/create'),
            //'edit' => Pages\EditCompras::route('/{record}/edit'),
        ];
    }
}
