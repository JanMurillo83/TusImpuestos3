<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovbancosResource\Pages;
use App\Filament\Resources\MovbancosResource\RelationManagers;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatPolizas;
use App\Models\Movbancos;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use CfdiUtils\Elements\Cfdi33\Comprobante;
use CfdiUtils\SumasPagos20\Decimal;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Infolists\Components\Actions as ComponentsActions;
use Filament\Infolists\Components\Actions\Action as ComponentsActionsAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Summarizers\Range;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Parent_;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum as MathTrigSum;

class MovbancosResource extends Resource
{
    protected static ?string $model = Movbancos::class;
    protected static ?string $navigationGroup = 'Bancos';
    protected static ?string $label = 'Movimiento Bancario';
    protected static ?string $pluralLabel = 'Movimientos Bancarios';
    public ?float $saldo_cuenta = 0;
    public ?float $saldo_cuenta_act = 0;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->tabs([
                        Tabs\Tab::make('Datos Generales')
                            ->schema([
                                Forms\Components\DatePicker::make('fecha')
                                    ->required(),
                                Forms\Components\Select::make('tipo')
                                    ->required()
                                    ->options([
                                        'E'=>'Entrada',
                                        'S'=>'Salida'
                                    ]),
                                Forms\Components\Select::make('cuenta')
                                    ->required()
                                    ->label('Cuenta Bancaria')
                                        ->required()
                                        ->options(BancoCuentas::where('team_id',Filament::getTenant()->id)->pluck('banco','id')),
                                Forms\Components\TextInput::make('importe')
                                        ->required()
                                        ->numeric(),
                                Forms\Components\TextInput::make('concepto')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                Forms\Components\TextInput::make('ejercicio')
                                        ->default(Filament::getTenant()->ejercicio),
                                Forms\Components\TextInput::make('periodo')
                                        ->default(Filament::getTenant()->periodo),
                                Forms\Components\TextInput::make('contabilizada')
                                        ->required()
                                        ->maxLength(45)
                                        ->default('NO')
                                        ->readOnly(),
                            ])->columns(4),
                        Tabs\Tab::make('Datos del Comprobante')
                            ->schema([
                                Forms\Components\TextInput::make('tercero')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('factura')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('uuid')
                                    ->label('UUID')
                                    ->required()
                                    ->maxLength(255),
                            ])->columns(1)->visible(
                                function(Get $get){
                                    $con = $get('contabilizada');
                                    if($con == 'NO')
                                        return false;
                                    else
                                        return true;
                                }
                            )
                    ])->columnSpanFull(),
                Forms\Components\Hidden::make('tax_id')
                    ->default(Filament::getTenant()->taxid),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
                Forms\Components\Hidden::make('actual')
                    ->default(0),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(function($table,$livewire){
                $record = $table->getRecords();
                $sdos = DB::table('saldosbancos')
                    ->where('cuenta',$record[0]->cuenta ?? 1)
                    ->where('ejercicio',Filament::getTenant()->ejercicio)
                    ->where('periodo',Filament::getTenant()->periodo)->get();
                    $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        $valor = $formatter->formatCurrency($sdos[0]->inicial ?? 0, 'MXN');
                    $livewire->saldo_cuenta = floatval($sdos[0]->inicial ?? 0);
                    $livewire->saldo_cuenta_act = floatval($sdos[0]->inicial ?? 0);
                    $valor2 = $formatter->formatCurrency($livewire->saldo_cuenta_act, 'MXN');
                return 'Saldo Inicial del Periodo: '.$valor;
            })
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable()
                    ->sortable()
                    ->state(function($record):string {
                        $v='';
                        if($record->tipo == 'E') $v = 'Ingreso';
                        if($record->tipo == 'S') $v = 'Egreso';
                        return $v;
                    }),
                Tables\Columns\TextColumn::make('tercero')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cuenta')
                    ->searchable()
                    ->sortable()
                    ->state(function($record):string {
                        $clientes = BancoCuentas::where('id',$record->cuenta)->get();
                        return $clientes[0]->banco;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('factura')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe')
                    ->numeric()
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                Tables\Columns\TextColumn::make('concepto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contabilizada')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('Saldo')
                    ->sortable()
                    ->getStateUsing(function($record,$livewire){
                        $tipo = $record->tipo;
                        if($tipo == 'E')
                            $livewire->saldo_cuenta_act = $livewire->saldo_cuenta_act + ($record->importe / 3);
                        else
                            $livewire->saldo_cuenta_act = $livewire->saldo_cuenta_act - ($record->importe / 3);
                        return $livewire->saldo_cuenta_act;
                    })
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    })
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Action::make('procesa_e')
                        ->form(function(Form $form){
                            return $form
                            ->schema([
                                TextInput::make('importe')
                                ->label('Importe Movimiento')
                                ->readOnly()
                                ->numeric()
                                ->prefix('$')
                                ->default(function(Model $record){
                                    return $record->importe;
                                }),
                                TextInput::make('importefactu')
                                ->visible(false)
                                ->label('Importe Facturas')
                                ->placeholder(function (Get $get,Set $set) {
                                    $valor = collect($get('Facturas'))->pluck('Importe')->sum();
                                    //Self::updateTotals($get,$set);
                                    return floatval($valor);
                                })
                                ->readOnly()
                                ->numeric()
                                ->prefix('$')
                                ->default(0),
                                Select::make('Movimiento')
                                    ->required()
                                    ->options([
                                        '1'=>'Cobro de Factura',
                                        '2'=>'Prestamo',
                                        '4'=>'Otros Ingresos'
                                    ])->columnSpan(2),
                                    TableRepeater::make('Facturas')
                                    ->headers([
                                        Header::make('Factura')->width('100px'),
                                        Header::make('Emisor')->width('100px'),
                                        Header::make('Receptor')->width('100px'),
                                        Header::make('Importe')->width('100px'),
                                        //Header::make('Idd')->width('50px')
                                    ])
                                    ->schema([
                                        TextInput::make('Factura')
                                        ->afterStateUpdated(function(Get $get,Set $set){
                                            $factura = $get('Factura');
                                            $facts = DB::table('almacencfdis')->where('id',$factura)->get();
                                            $fac = $facts[0];
                                            $set('Emisor',$fac->Emisor_Rfc);
                                            $set('Receptor',$fac->Receptor_Rfc);
                                            $set('Importe',$fac->Total);
                                            $set('FacId',$fac->id);
                                        })->live(onBlur:true)
                                        ->suffixAction(
                                            ActionsAction::make('SelF')
                                            ->label('')
                                            ->icon('fas-magnifying-glass')
                                            ->form([
                                                Select::make('selcfdi')
                                                ->searchable()
                                                ->label('CFDI')
                                                ->options(
                                                    function(){
                                                        $cfdis = DB::table('almacencfdis')->where(['team_id'=>Filament::getTenant()->id,'xml_type'=>'Emitidos'])
                                                        ->select('id',DB::Raw("concat('Factura: ',serie,folio,'  Fecha: ',
                                                        DATE_FORMAT(fecha,'%d-%m-%Y'),'  Receptor: ',Receptor_Nombre,
                                                        '  Importe: $',FORMAT(Total,2)) CFDI"))->get();
                                                        $resultado =[];
                                                        foreach($cfdis as $cfdi)
                                                        {
                                                            array_push($resultado,[$cfdi->id=>$cfdi->CFDI]);
                                                        }
                                                        return $resultado;
                                            })
                                            ])->action(function(Set $set,$data){
                                                $idd = $data['selcfdi'];
                                                $set('Factura',$idd);
                                                $facts = DB::table('almacencfdis')->where('id',$idd)->get();
                                                $fac = $facts[0];
                                                $set('Factura',$fac->Serie.$fac->Folio);
                                                $set('Emisor',$fac->Emisor_Rfc);
                                                $set('Receptor',$fac->Receptor_Rfc);
                                                $set('Importe',$fac->Total);
                                                $set('FacId',$fac->id);
                                            })
                                        ),
                                        TextInput::make('Emisor')->readOnly(),
                                        TextInput::make('Receptor')->readOnly(),
                                        TextInput::make('Importe')->readOnly()
                                        ->numeric()->prefix('$'),
                                        Hidden::make('FacId'),
                                        Hidden::make('UUID')
                                    ])->columnSpanFull()
                            ])->columns(4);
                        })
                        ->modalWidth('7xl')
                        ->visible(fn ($record) => $record->tipo == 'E')
                        ->label('Procesar')
                        ->accessSelectedRecords()
                        ->icon('fas-check-to-slot')
                        ->action(function (Model $record,$data,Get $get, Set $set) {
                            Self::procesa_e_f($record,$data,$get,$set);
                        }),
                    Action::make('procesa_s')
                    ->form(function(Form $form){
                        return $form
                        ->schema([
                            TextInput::make('importe')
                            ->label('Importe Movimiento')
                            ->readOnly()
                            ->numeric()
                            ->prefix('$')
                            ->default(function(Model $record){
                                return $record->importe;
                            }),
                            TextInput::make('importefactu')
                            ->visible(false)
                            ->label('Importe Facturas')
                            ->placeholder(function (Get $get,Set $set) {
                                $valor = collect($get('Facturas'))->pluck('Importe')->sum();
                                //Self::updateTotals($get,$set);
                                return floatval($valor);
                            })
                            ->readOnly()
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                            Select::make('Movimiento')
                                ->required()
                                ->options([
                                    '1'=>'Cobro de Factura',
                                    '2'=>'Cobro no identificado',
                                    '3'=>'Prestamo',
                                    '4'=>'Otros Ingresos'
                                ])->columnSpan(2),
                                TableRepeater::make('Facturas')
                                ->headers([
                                    Header::make('Factura')->width('100px'),
                                    Header::make('Emisor')->width('100px'),
                                    Header::make('Receptor')->width('100px'),
                                    Header::make('Importe')->width('100px'),
                                    //Header::make('Idd')->width('50px')
                                ])
                                ->schema([
                                    TextInput::make('Factura')
                                    ->afterStateUpdated(function(Get $get,Set $set){
                                        $factura = $get('Factura');
                                        $facts = DB::table('almacencfdis')->where('id',$factura)->get();
                                        $fac = $facts[0];
                                        $set('Emisor',$fac->Emisor_Rfc);
                                        $set('Receptor',$fac->Receptor_Rfc);
                                        $set('Importe',$fac->Total);
                                        $set('FacId',$fac->id);
                                    })->live(onBlur:true)
                                    ->suffixAction(
                                        ActionsAction::make('SelF')
                                        ->label('')
                                        ->icon('fas-magnifying-glass')
                                        ->infolist(function($infolist, Set $set, Get $get,$data){
                                            $comp = DB::table('almacencfdis')->get();
                                            //->where(['TipoDeComprobante'=>'I','xml_type'=>'Emitidos'])->get();
                                            $datos [] =['Titulo'=>'Comprobantes Fiscales','Facturas'=>$comp];
                                            return $infolist
                                            ->state($datos[0])
                                            ->schema([
                                                TextEntry::make('Titulo'),
                                                RepeatableEntry::make('Facturas')
                                                ->schema([
                                                    TextEntry::make('Fecha')->date('d-m-y'),
                                                    TextEntry::make('Serie'),
                                                    TextEntry::make('Folio'),
                                                    TextEntry::make('Emisor_Rfc'),
                                                    TextEntry::make('Receptor_Rfc'),
                                                    TextEntry::make('Total')->numeric(),
                                                    TextEntry::make('id')->numeric()
                                                    ->label('')
                                                    ->formatStateUsing(function($state){
                                                        $state = '';
                                                        return $state;
                                                    })
                                                    ->suffixAction(
                                                        ComponentsActionsAction::make('Sel')
                                                        ->label('')
                                                        ->icon('fas-check')
                                                        ->action(function(ComponentsActions\Action $action)use($get,$set,$data){
                                                            $val = ($action->getComponent()->getState());
                                                            $set('Factura',$val);
                                                            $facts = DB::table('almacencfdis')->where('id',$val)->get();
                                                            $fac = $facts[0];
                                                            $set('Factura',$fac->Serie.$fac->Folio);
                                                            $set('Emisor',$fac->Emisor_Rfc);
                                                            $set('Receptor',$fac->Receptor_Rfc);
                                                            $set('Importe',$fac->Total);
                                                            $set('FacId',$fac->id);
                                                            $set('UUID',$fac->UUID);
                                                            //self::sumas($get,$set,$data);
                                                        })->close()
                                                    ),
                                                    TextEntry::make('UUID')->visible(false),
                                                ])->columns(7)
                                            ]);
                                        })
                                    ),
                                    TextInput::make('Emisor')->readOnly(),
                                    TextInput::make('Receptor')->readOnly(),
                                    TextInput::make('Importe')->readOnly()
                                    ->numeric()->prefix('$'),
                                    Hidden::make('FacId'),
                                    Hidden::make('UUID')
                                ])->columnSpanFull()
                        ])->columns(4);
                    })
                    ->modalWidth('7xl')
                    ->visible(fn ($record) => $record->tipo == 'S')
                    ->label('Procesar')
                    ->accessSelectedRecords()
                    ->icon('fas-check-to-slot')
                    ->action(function (Model $record,$data,Get $get, Set $set) {
                        Self::procesa_s_f($record,$data,$get,$set);
                    })
                ])->color('primary')
            ])->actionsPosition(ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped()->defaultPaginationPageOption(6)
            ->paginated([6, 'all'])
            ->filters([
                Filter::make('created_at')
                ->form([
                    DatePicker::make('Fecha_Inicial')
                    ->default(function(){
                        $ldom = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo;
                        $Fecha_Inicial = Carbon::make('first day of'.$ldom);
                        return $Fecha_Inicial->format('Y-m-d');
                    }),
                    DatePicker::make('Fecha_Final')
                    ->default(function(){
                        $ldom = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo;
                        $Fecha_Inicial = Carbon::make('last day of'.$ldom);
                        return $Fecha_Inicial->format('Y-m-d');
                    }),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['Fecha_Inicial'],
                            fn (Builder $query, $date): Builder => $query->whereDate('Fecha', '>=', $date),
                        )
                        ->when(
                            $data['Fecha_Final'],
                            fn (Builder $query, $date): Builder => $query->whereDate('Fecha', '<=', $date),);
                        })
            ],layout: FiltersLayout::Modal)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Cambiar Periodo'),
            )
            ->deferFilters()
            ->defaultSort('Fecha', 'asc');;
    }

    public static function sumas(Get $get,Set $set,$data) :void
    {
        //dd($data);
        $col = array_column($get('../../Facturas'),'Importe');
        $suma = array_sum($col);
        $set('importefactu',$suma);
    }
    public static function procesa_e_f($record,$data)
    {

        $facts =$data['Facturas'];
        //dd($facts[0]);
        DB::table('movbancos')->where('id',$record->id)->update([
            'tercero'=>$facts[0]['Receptor'],
            'factura'=>$facts[0]['Factura'],
            'uuid'=>$facts[0]['UUID'],
            'contabilizada'=>'SI'
        ]);
        $fss = DB::table('almacencfdis')->where('id',$facts[0]['FacId'])->get();
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->get();
        $ter = DB::table('terceros')->where('rfc',$facts[0]['Receptor'])->get();
        $nom = $fss[0]->Receptor_Nombre;
        //-------------------------------------------------------------------
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Ig')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        $poliza = CatPolizas::create([
            'tipo'=>'Ig',
            'folio'=>$nopoliza,
            'fecha'=>$record->fecha,
            'concepto'=>$nom,
            'cargos'=>$record->importe,
            'abonos'=>$record->importe,
            'periodo'=>Filament::getTenant()->periodo,
            'ejercicio'=>Filament::getTenant()->ejercicio,
            'referencia'=>$facts[0]['Factura'],
            'uuid'=>$facts[0]['UUID'],
            'tiposat'=>'Ig',
            'team_id'=>Filament::getTenant()->id
        ]);
        $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ban[0]->codigo,
                'cuenta'=>$ban[0]->cuenta,
                'concepto'=>$nom,
                'cargo'=>$record->importe,
                'abono'=>0,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>1,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ter[0]->cuenta,
                'cuenta'=>$ter[0]->nombre,
                'concepto'=>$nom,
                'cargo'=>0,
                'abono'=>$record->importe,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
        Notification::make('Concluido')
        ->title('Proceso Concluido')
        ->success()
        ->send();
    }

    public static function procesa_s_f($record,$data)
    {
        $facts =$data['Facturas'];
        //dd($facts[0]);
        DB::table('movbancos')->where('id',$record->id)->update([
            'tercero'=>$facts[0]['Receptor'],
            'factura'=>$facts[0]['Factura'],
            'uuid'=>$facts[0]['UUID'],
            'contabilizada'=>'SI'
        ]);
        $fss = DB::table('almacencfdis')->where('id',$facts[0]['FacId'])->get();
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->get();
        $ter = DB::table('terceros')->where('rfc',$facts[0]['Receptor'])->get();
        $nom = $fss[0]->Receptor_Nombre;
        //-------------------------------------------------------------------
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Eg')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        $poliza = CatPolizas::create([
            'tipo'=>'Eg',
            'folio'=>$nopoliza,
            'fecha'=>$record->fecha,
            'concepto'=>$nom,
            'cargos'=>$record->importe,
            'abonos'=>$record->importe,
            'periodo'=>Filament::getTenant()->periodo,
            'ejercicio'=>Filament::getTenant()->ejercicio,
            'referencia'=>$facts[0]['Factura'],
            'uuid'=>$facts[0]['UUID'],
            'tiposat'=>'Eg',
            'team_id'=>Filament::getTenant()->id
        ]);
        $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ban[0]->codigo,
                'cuenta'=>$ban[0]->cuenta,
                'concepto'=>$nom,
                'cargo'=>0,
                'abono'=>$record->importe,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>1,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ter[0]->cuenta,
                'cuenta'=>$ter[0]->nombre,
                'concepto'=>$nom,
                'cargo'=>$record->importe,
                'abono'=>0,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
        Notification::make('Concluido')
        ->title('Proceso Concluido')
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
            'index' => Pages\ListMovbancos::route('/'),
            //'create' => Pages\CreateMovbancos::route('/create'),
            //'edit' => Pages\EditMovbancos::route('/{record}/edit'),
        ];
    }
}

