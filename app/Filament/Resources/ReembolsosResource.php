<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReembolsosResource\Pages;
use App\Filament\Resources\ReembolsosResource\RelationManagers;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\Facturas;
use App\Models\Metodos;
use App\Models\Movbancos;
use App\Models\Reembolsos;
use App\Models\ReembolsosDetalles;
use App\Models\Terceros;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ReembolsosResource extends Resource
{
    protected static ?string $model = Reembolsos::class;
    protected static ?string $navigationIcon = 'fas-arrow-right-arrow-left';
    protected static ?string $navigationGroup = 'Registro CFDI';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Generales')
            ->schema([
                Forms\Components\DatePicker::make('fecha')
                    ->required()->default(Carbon::now()),
                Forms\Components\TextInput::make('periodo')
                    ->required()
                    ->numeric()->default(Filament::getTenant()->periodo),
                Forms\Components\TextInput::make('ejercicio')
                    ->required()
                    ->numeric()->default(Filament::getTenant()->ejercicio),
                Forms\Components\TextInput::make('estado')
                    ->required()->readOnly()
                    ->maxLength(255)->default('Activo'),
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(255)->columnSpan(3)
            ])->columns(7),
                Forms\Components\Fieldset::make('Movimiento Bancario')
            ->schema([
                Forms\Components\Select::make('movbanco')
                    ->label('Movimiento')
                    ->required()
                    ->options(Movbancos::where('contabilizada','no')->pluck('concepto','id'))
                    ->columnSpan(3)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get,Set $set) {
                        $set('importe',Movbancos::where('id',$get('movbanco'))->get()[0]->importe ?? 0);
                    }),
                Forms\Components\TextInput::make('importe')
                    ->required()
                    ->readOnly()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00000000)
                    ->columnSpan(2),
                Forms\Components\TextInput::make('importe_comp')
                    ->label('Comprobantes')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00000000)
                    ->columnSpan(2),
                Forms\Components\Select::make('idtercero')
                    ->label('Tercero')
                    ->required()
                    ->options(Terceros::where('team_id',Filament::getTenant()->id)->pluck('nombre','id'))
                    ->default(0)->columnSpan(3)->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get,Set $set) {
                        $set('nombre',Terceros::where('id',$get('idtercero'))->get()[0]->nombre ?? '');
                    }),
                Forms\Components\Hidden::make('nombre'),
                Forms\Components\Select::make('formapago')
                    ->label('Forma de Pago')
                    ->options(Metodos::all()->pluck('mostrar','id'))
                    ->live(onBlur: true)
                    ->columnSpan(2)->afterStateUpdated(function (Get $get,Set $set) {
                        $set('descrfpago',Metodos::where('id',$get('formapago'))->get()[0]->descripcion ?? '');
                    }),
                Forms\Components\Hidden::make('descrfpago')
                ])->columns(12),
                Forms\Components\Fieldset::make('')
            ->schema([
                TableRepeater::make('Detalles')
                ->relationship()
                ->streamlined()
                ->headers([
                    Header::make('Comprobante'),
                    Header::make('Referencia'),
                    Header::make('Fecha'),
                    Header::make('Moneda'),
                    Header::make('Importe'),
                    Header::make('Rubro del Gasto'),
                    Header::make('Tipo de Gasto'),
                ])
                ->schema([
                    Forms\Components\Select::make('comprobante')
                        ->options(DB::table('almacencfdis')->where(['team_id'=>Filament::getTenant()->id,'xml_type'=>'Recibidos','used'=>'SI'])
                        ->select('id',DB::Raw("concat('Factura: ',serie,folio,'  Fecha: ',
                            DATE_FORMAT(fecha,'%d-%m-%Y'),'  Emisor: ',Emisor_Nombre,
                            '  Importe: $',FORMAT(Total,2)) CFDI"))->pluck('CFDI','id'))
                        ->afterStateUpdated(function(Get $get,Set $set){
                            $factura = $get('comprobante');
                            $facts = DB::table('almacencfdis')->where('id',$factura)->get();
                            $fac = $facts[0];
                            $set('Emisor',$fac->Emisor_Rfc);
                            $set('importe',number_format($fac->Total,2));
                            $set('moneda',$fac->Moneda);
                            $set('fecha',Carbon::parse($fac->Fecha)->format('Y-m-d'));
                            $set('referencia',$fac->Serie.$fac->Folio);
                            self::suma1($get,$set);
                        })->live(onBlur:true),
                    Forms\Components\TextInput::make('referencia'),
                    Forms\Components\DatePicker::make('fecha'),
                    Forms\Components\TextInput::make('moneda'),
                    Forms\Components\TextInput::make('importe'),
                    Forms\Components\Hidden::make('notas'),
                    Forms\Components\Select::make('rubro')
                    ->live()
                    ->default('')
                    ->options([
                        '50100000' => 'Costo de Ventas',
                        '60200000' => 'Gastos de Venta',
                        '60300000' => 'Gastos de Administracion',
                        '70100000' => 'Gastos Financieros',
                        '70200000' => 'Productos Financieros'
                    ]),
                    Forms\Components\Select::make('gasto')
                    ->disabled(function (Get $get) {
                        if($get('rubro') !='') return false; else return true;
                    })
                    ->options(function(Get $get) {
                        return CatCuentas::where('acumula',$get('rubro'))->pluck('nombre','codigo');
                    }),

                ])
                ->columnSpanFull()
            ]),
                Forms\Components\Fieldset::make('Notas')
            ->schema([
                Forms\Components\TextInput::make('notas')
                    ->maxLength(255)->columnSpanFull(),
                Forms\Components\Hidden::make('team_id')
                   ->default(Filament::getTenant()->id)
            ])
            ]);
    }

    public static function suma1(Get $get,Set $set)
    {
        $importes = collect($get('../../Detalles'));
        $importe = 0;
        foreach ($importes as $import) {
            $importe += $import['importe'];
        }
        $set('../../importe_comp',number_format($importe,2));
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe')
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2,decimalSeparator: '.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe_comp')
                    ->label('Comprobantes')
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2,decimalSeparator: '.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Tercero')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descrfpago')
                    ->label('Forma de Pago')
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make()
                ->icon('fas-edit')
                ->label('Editar')
                ->closeModalByEscaping(false)
                ->modalWidth('8xl')
                ->modalSubmitActionLabel('Aceptar')
                ->modalCancelActionLabel('Cancelar')
                ->visible(function ($record) {
                        if($record->estado == 'Contabilizado') return false; else return true;
                }),
                Tables\Actions\ViewAction::make()
                ->icon('fas-eye')
                ->label('Consultar')
                ->closeModalByEscaping(false)
                ->modalWidth('8xl')
                ->visible(function ($record) {
                    if($record->estado != 'Contabilizado') return false; else return true;
                }),
                Tables\Actions\Action::make('close')
                ->label('Contabilizar y Cerrar')
                ->icon('far-window-close')
                ->requiresConfirmation()
                ->visible(function ($record) {
                    if($record->estado == 'Contabilizado') return false; else return true;
                })
                ->action(function ($record) {
                    $detalles = ReembolsosDetalles::where('reembolsos_id',$record->id)->get();
                    $tercero = Terceros::where('id',$record->idtercero)->first();
                    $tercero_cuenta =$tercero->cuenta;
                    $tercero_nombre =$tercero->nombre;
                    $nopoliza = intval(DB::table('cat_polizas')
                        ->where('team_id',Filament::getTenant()->id)
                        ->where('tipo','Dr')
                        ->where('periodo',Filament::getTenant()->periodo)
                        ->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                    $poliza = CatPolizas::create([
                        'tipo'=>'Dr',
                        'folio'=>$nopoliza,
                        'fecha'=>$record->fecha,
                        'concepto'=>$tercero_nombre,
                        'cargos'=>round(floatval($record->importe_comp) * 2,2),
                        'abonos'=>round(floatval($record->importe_comp) * 2,2),
                        'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,
                        'referencia'=>'Reembolso',
                        'uuid'=>'',
                        'tiposat'=>'Dr',
                        'team_id'=>Filament::getTenant()->id,
                        'idmovb'=>0
                    ]);
                    $polno = $poliza['id'];
                    $no_partida = 0;
                    foreach ($detalles as $detalle) {
                        $cta_nom = CatCuentas::where('team_id',Filament::getTenant()->id)->where('codigo',$detalle->gasto)->first()->nombre;
                        $factura = Almacencfdis::where('id',$detalle->comprobante)->first();
                        $proveedor = Terceros::where('tipo','Proveedor')->where('rfc',$factura->Emisor_Rfc)->first();
                        $no_partida++;
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$detalle->gasto,
                            'cuenta'=>$cta_nom,
                            'concepto'=>$factura->Emisor_Nombre,
                            'cargo'=>$factura->SubTotal,
                            'abono'=>0,
                            'factura'=>$factura->Serie.$factura->Folio,
                            'nopartida'=>$no_partida,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'cat_polizas_id'=>$polno,
                            'auxiliares_id'=>$aux['id'],
                        ]);
                        $no_partida++;
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>'11801000',
                            'cuenta'=>'IVA acreditable pagado',
                            'concepto'=>$factura->Emisor_Nombre,
                            'cargo'=>$factura->TotalImpuestosTrasladados,
                            'abono'=>0,
                            'factura'=>$factura->Serie.$factura->Folio,
                            'nopartida'=>$no_partida,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'cat_polizas_id'=>$polno,
                            'auxiliares_id'=>$aux['id'],
                        ]);
                        $no_partida++;
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$proveedor->cuenta,
                            'cuenta'=>$proveedor->nombre,
                            'concepto'=>$factura->Emisor_Nombre,
                            'cargo'=>0,
                            'abono'=>$factura->Total,
                            'factura'=>$factura->Serie.$factura->Folio,
                            'nopartida'=>$no_partida,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'cat_polizas_id'=>$polno,
                            'auxiliares_id'=>$aux['id'],
                        ]);
                        $no_partida++;
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$proveedor->cuenta,
                            'cuenta'=>$proveedor->nombre,
                            'concepto'=>$factura->Emisor_Nombre,
                            'cargo'=>$factura->Total,
                            'abono'=>0,
                            'factura'=>$factura->Serie.$factura->Folio,
                            'nopartida'=>$no_partida,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'cat_polizas_id'=>$polno,
                            'auxiliares_id'=>$aux['id'],
                        ]);
                        $no_partida++;
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$tercero_cuenta,
                            'cuenta'=>$tercero_nombre,
                            'concepto'=>$factura->Emisor_Nombre,
                            'cargo'=>0,
                            'abono'=>$factura->Total,
                            'factura'=>$factura->Serie.$factura->Folio,
                            'nopartida'=>$no_partida,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'cat_polizas_id'=>$polno,
                            'auxiliares_id'=>$aux['id'],
                        ]);
                    }
                    $movs = Movbancos::where('id',$record->movbanco)->get()[0];
                    $banctas = BancoCuentas::where('id',$movs->cuenta)->get()[0];
                    $nopoliza1 = intval(DB::table('cat_polizas')
                            ->where('team_id',Filament::getTenant()->id)
                            ->where('tipo','Eg')
                            ->where('periodo',Filament::getTenant()->periodo)
                            ->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                    $poliza1 = CatPolizas::create([
                        'tipo'=>'Eg',
                        'folio'=>$nopoliza1,
                        'fecha'=>$record->fecha,
                        'concepto'=>$tercero_nombre,
                        'cargos'=>round(floatval($record->importe_comp),2),
                        'abonos'=>round(floatval($record->importe_comp),2),
                        'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,
                        'referencia'=>'Reembolso',
                        'uuid'=>'',
                        'tiposat'=>'Eg',
                        'team_id'=>Filament::getTenant()->id,
                        'idmovb'=>0
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$poliza1['id'],
                        'codigo'=>$tercero_cuenta,
                        'cuenta'=>$tercero_nombre,
                        'concepto'=>$tercero_nombre,
                        'cargo'=>round(floatval($record->importe_comp),2),
                        'abono'=>0,
                        'factura'=>'',
                        'nopartida'=>1,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'cat_polizas_id'=>$poliza1['id'],
                        'auxiliares_id'=>$aux['id'],
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$poliza1['id'],
                        'codigo'=>$banctas->codigo,
                        'cuenta'=>$banctas->banco,
                        'concepto'=>$tercero_nombre,
                        'cargo'=>0,
                        'abono'=>round(floatval($record->importe_comp),2),
                        'factura'=>'',
                        'nopartida'=>2,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'cat_polizas_id'=>$poliza1['id'],
                        'auxiliares_id'=>$aux['id'],
                    ]);
                    Movbancos::where('id',$record->movbanco)->update([
                        'contabilizada'=>'SI'
                    ]);
                    Reembolsos::where('id',$record->id)->update([
                       'estado'=>'Contabilizado'
                    ]);
                    Notification::make()
                        ->title('Reembolso Contabilizado')
                        ->success()
                        ->send();
                })
                ])

            ])->actionsPosition(Tables\Enums\ActionsPosition::BeforeCells)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->icon('fas-circle-plus')
                ->label('Agregar')
                ->closeModalByEscaping(false)
                ->createAnother(false)
                ->modalWidth('8xl')
                ->modalSubmitActionLabel('Aceptar')
                ->modalCancelActionLabel('Cancelar'),
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
            'index' => Pages\ListReembolsos::route('/'),
            //'create' => Pages\CreateReembolsos::route('/create'),
            //'edit' => Pages\EditReembolsos::route('/{record}/edit'),
        ];
    }
}
