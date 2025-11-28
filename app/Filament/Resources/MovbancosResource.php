<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Herramientas\Resources\IngresosEgresosResource;
use App\Filament\Pages\PagoMultiPesosPesos;
use App\Filament\Resources\MovbancosResource\Pages;
use App\Filament\Resources\MovbancosResource\RelationManagers;
use App\Http\Controllers\DescargaSAT;
use App\Livewire\FacturasEgWidget;
use App\Livewire\IngEgWidget;
use App\Livewire\TraspasoBanco;
use App\Models\Activosfijos;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\ContaPeriodos;
use App\Models\CuentasDetalle;
use App\Models\IngresosEgresos;
use App\Models\Movbancos;
use App\Models\Regimenes;
use App\Models\Terceros;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use CfdiUtils\Elements\Cfdi33\Comprobante;
use CfdiUtils\SumasPagos20\Decimal;
use DefStudio\SearchableInput\DTO\SearchResult;
use DefStudio\SearchableInput\Forms\Components\SearchableInput;
use Dvarilek\FilamentTableSelect\Components\Form\TableSelect;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Livewire;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\ActionSize;
use Filament\Support\RawJs;
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
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
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
use Mockery\Matcher\Not;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use phpDocumentor\Reflection\Types\Parent_;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum as MathTrigSum;
use Svg\Tag\Text;

class MovbancosResource extends Resource
{
    protected static ?string $model = Movbancos::class;
    protected static ?string $navigationGroup = 'Bancos';
    protected static ?string $label = 'Movimiento Bancario';
    protected static ?string $pluralLabel = 'Movimientos Bancarios';
    protected static ?string $navigationIcon ='fas-money-bill-transfer';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->tabs([
                        Tabs\Tab::make('Datos Generales')
                            ->schema([
                                Forms\Components\TextInput::make('fecha_dia')
                                    ->required(function ($context){
                                        if($context != 'create')
                                            return false;
                                        else
                                            return true;
                                    })
                                    ->label('Fecha')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->default(Carbon::now()->day)->disabledOn('edit')
                                    ->visibleOn('create')
                                    ->suffix(function ($record,$context){
                                        if($context == 'create')
                                            return '-'.Filament::getTenant()->periodo.'-'.Filament::getTenant()->ejercicio;
                                        else
                                            return '-'.$record->periodo.'-'.$record->ejercicio;

                                    }),
                                DatePicker::make('fecha')->readOnly()
                                ->visibleOn(['edit','view']),
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
                                        ->currencyMask(precision: 2)->prefix('$')
                                    ->default(function ($record,$context){
                                        if($context != 'create')return number_format(floatval($record->importe),2);
                                        else return number_format(0.00,2);
                                    }),
                                Forms\Components\Select::make('moneda')
                                ->options(['MXN'=>'MXN','USD'=>'USD'])
                                ->default('MXN'),
                                Forms\Components\TextInput::make('tcambio')
                                    ->required()
                                    ->label('Tipo de Cambio')
                                    ->currencyMask(precision: 4)->prefix('$')
                                    ->default(function ($record,$context){
                                        if($context != 'create')return number_format(floatval($record->tcambio),4);
                                        else return number_format(1.0000,4);
                                    }),
                                Forms\Components\TextInput::make('concepto')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2),
                                Forms\Components\TextInput::make('ejercicio')
                                        ->default(Filament::getTenant()->ejercicio)->readOnly(),
                                Forms\Components\TextInput::make('periodo')
                                        ->default(Filament::getTenant()->periodo)->readOnly(),
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
            ->heading(function ($livewire) {
                $cuenta_id = $livewire->selected_tier;
                $cuenta = BancoCuentas::where('id',$cuenta_id)->first();
                $inicial_origen = floatval($cuenta->inicial);
                $periodo = Filament::getTenant()->periodo ?? 1;
                $ejercicio = Filament::getTenant()->ejercicio ?? 2020;
                $entradas_act = Movbancos::where('cuenta',$cuenta_id)->where('tipo','E')->where('ejercicio',$ejercicio)->where('periodo',$periodo)->sum('importe') ?? 0;
                $salidas_act = Movbancos::where('cuenta',$cuenta_id)->where('tipo','S')->where('ejercicio',$ejercicio)->where('periodo',$periodo)->sum('importe') ?? 0;
                $entradas_ant = Movbancos::where('cuenta',$cuenta_id)->where('tipo','E')->where('ejercicio',$ejercicio)->where('periodo','<',$periodo)->sum('importe') ?? 0;
                $salidas_ant = Movbancos::where('cuenta',$cuenta_id)->where('tipo','S')->where('ejercicio',$ejercicio)->where('periodo','<',$periodo)->sum('importe') ?? 0;
                $inicial = $inicial_origen + $entradas_ant - $salidas_ant;
                $actual = $inicial + $entradas_act - $salidas_act;
                $livewire->saldo_cuenta_ant = $inicial;
                return 'Saldo Inicial: $'.number_format($inicial,2,'.',',').'       Saldo Actual: $'.number_format($actual,2,'.',',');
            })
            ->recordClasses(function (Model $record) {
                if($record->contabilizada == 'SI')
                    return 'row_si';
                if($record->contabilizada == 'PA')
                    return 'row_pa';
                return '';
            })
            ->paginated(false)
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
                Tables\Columns\TextColumn::make('moneda'),
                Tables\Columns\TextColumn::make('concepto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contabilizada')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('Saldo')
                    ->sortable()
                    ->getStateUsing(function($record,$livewire){
                        $Mov = Movbancos::where('id',$record->id)->first();
                        $Tipo = $Mov->tipo;
                        $Importe = floatval($Mov->importe);
                        if($Tipo == 'E')
                            //$livewire->saldo_cuenta_act = $livewire->saldo_cuenta_act + ($record->importe / 3);
                            $livewire->saldo_cuenta_ant += $Importe / 3;
                        else
                            $livewire->saldo_cuenta_ant -= $Importe / 3;
                        return floatval($livewire->saldo_cuenta_ant);
                        //return $record->id;
                    })
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    })
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                    ->visible(function($record){
                        if($record->contabilizada == 'NO') return true;
                        if($record->contabilizada == 'SI') return false;
                    }),
                    Tables\Actions\ViewAction::make()
                    ->visible(function($record){
                        if($record->contabilizada == 'SI') return true;
                        if($record->contabilizada == 'NO') return false;
                    }),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->before(function (Model $record) {
                            DB::table('movbancos_team')->where('movbancos_id', $record->id)
                                ->where('team_id', Filament::getTenant()->id)
                                ->delete();
                        })
                        ->visible(function($record){
                            if($record->contabilizada == 'SI') return false;
                            if($record->contabilizada == 'NO') return true;
                        }),
                    Action::make('ver_poliza')
                        ->label('Ver Póliza')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->visible(function($record){
                            if($record->contabilizada == 'SI') return true;
                            return false;
                        })
                        ->modalHeading('Detalles de la Póliza')
                        ->modalWidth('7xl')
                        ->form(function($record){
                            // Buscar la póliza relacionada con este movimiento bancario
                            $poliza = CatPolizas::where('idmovb', $record->id)->first();

                            if (!$poliza) {
                                return [];
                            }

                            // Obtener las partidas de la póliza
                            $partidas = DB::table('auxiliares')
                                ->where('cat_polizas_id', $poliza->id)
                                ->get();

                            return [
                                Section::make()
                                    ->columns([
                                        'default' => 5,
                                        'sm' => 2,
                                        'md' => 2,
                                        'lg' => 5,
                                        'xl' => 5,
                                        '2xl' => 5,
                                    ])
                                    ->schema([
                                        Forms\Components\TextInput::make('fecha')
                                            ->label('Fecha')
                                            ->default(function() use ($poliza) {
                                                return date('d-m-Y', strtotime($poliza->fecha));
                                            })
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('tipo')
                                            ->label('Tipo')
                                            ->default($poliza->tipo)
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('folio')
                                            ->label('Folio')
                                            ->default($poliza->folio)
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('cargos')
                                            ->label('Cargos')
                                            ->prefix('$')
                                            ->default(function() use ($poliza) {
                                                $formatter = new \NumberFormatter('es_MX', \NumberFormatter::DECIMAL);
                                                return $formatter->format($poliza->cargos);
                                            })
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('abonos')
                                            ->label('Abonos')
                                            ->prefix('$')
                                            ->default(function() use ($poliza) {
                                                $formatter = new \NumberFormatter('es_MX', \NumberFormatter::DECIMAL);
                                                return $formatter->format($poliza->abonos);
                                            })
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('concepto')
                                            ->label('Concepto')
                                            ->default($poliza->concepto)
                                            ->columnSpan(4)
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('referencia')
                                            ->label('Referencia')
                                            ->prefix('F-')
                                            ->default($poliza->referencia)
                                            ->readOnly(),
                                    ]),
                                Section::make('Partidas')
                                    ->columns([
                                        'default' => 5,
                                        'sm' => 5,
                                        'md' => 5,
                                        'lg' => 5,
                                        'xl' => 5,
                                        '2xl' => 5,
                                    ])
                                    ->schema([
                                        TableRepeater::make('detalle')
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->disableItemMovement()
                                            ->columnSpanFull()
                                            ->headers([
                                                Header::make('codigo')->width('250px'),
                                                Header::make('cargo')->width('100px'),
                                                Header::make('abono')->width('100px'),
                                                Header::make('factura')->width('100px')->label('Referencia'),
                                                Header::make('concepto')->width('300px'),
                                            ])
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->readOnly(),
                                                TextInput::make('cargo')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->readOnly(),
                                                TextInput::make('abono')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->readOnly(),
                                                TextInput::make('factura')
                                                    ->label('Referencia')
                                                    ->prefix('F-')
                                                    ->readOnly(),
                                                TextInput::make('concepto')
                                                    ->readOnly(),
                                            ])
                                            ->default(function() use ($partidas) {
                                                $items = [];
                                                foreach ($partidas as $partida) {
                                                    $items[] = [
                                                        'codigo' => $partida->codigo,
                                                        'cargo' => $partida->cargo,
                                                        'abono' => $partida->abono,
                                                        'factura' => $partida->factura,
                                                        'concepto' => $partida->concepto,
                                                    ];
                                                }
                                                return $items;
                                            }),
                                    ]),
                            ];
                        }),
            //--------------------------------------------------------
                    Action::make('procesa_s')
                        ->visible(function($record){
                            if($record->contabilizada == 'SI') return false;
                            if($record->contabilizada == 'NO'&&$record->tipo == 'S') return true;
                        })
                    ->form(function(Form $form,$record,$livewire){
                        $livewire->recordid = $record->id;
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
                            TextInput::make('moneda')
                                ->default(function(Model $record){
                                    return $record->moneda;
                                })->readOnly(),
                            TextInput::make('importefactu')
                            ->visible(false)
                            ->label('Importe Facturas')
                            ->placeholder(function (Get $get) {
                                $valor = collect($get('Facturas'))->pluck('Importe')->sum();
                                return floatval($valor);
                            })
                            ->readOnly()
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                            Select::make('Movimiento')
                                ->required()
                                ->live()
                                ->options([
                                    '2'=>'Reembolso de Gastos',
                                    '3'=>'Compra de Activo',
                                    '4'=>'Prestamo',
                                    '5'=>'Gasto no Deducible',
                                    '6'=>'Pago de Nomina',
                                    '7'=>'Anticipo agencia aduanal',
                                    '8'=>'Captura Manual'
                                ])->columnSpan(2),
                                TableRepeater::make('Facturas')
                                    ->streamlined()->reorderable(false)
                                ->visible(function(Get $get){
                                    $mov = $get('Movimiento');
                                    if($mov == 1||$mov == 2||$mov == 3) return true;
                                    else return false;
                                })->headers([
                                    Header::make('Factura')->width('250px'),
                                    Header::make('Moneda')->width('50px'),
                                    Header::make('Emisor')->width('100px'),
                                    Header::make('Receptor')->width('100px'),
                                    Header::make('Importe')->width('100px'),
                                    Header::make('Tipo de Cambio')->width('100px')
                                ])
                                ->schema([
                                    Select::make('Factura')
                                    ->searchable()
                                    ->options(function (){
                                        $ing_ret = IngresosEgresos::where('team_id',Filament::getTenant()->id)->where('tipo',0)->where('pendientemxn','>',0)->where('tcambio','<',2)->get();
                                        $data = array();
                                        foreach ($ing_ret as $item){
                                            $tot = '$'.number_format($item->totalmxn,2);
                                            $pend = '$'.number_format($item->pendientemxn,2);
                                            $monea = 'MXN';
                                            $tc_n = 1;
                                            $alm = Almacencfdis::where('id',$item->xml_id)->first();
                                            if($item->tcambio > 1) {

                                                $fech_comp = date('Y-m-d',strtotime(Carbon::now()));
                                                if(count(DB::table('historico_tcs')->where('fecha',$fech_comp)->get()) == 0) {
                                                    $tip_cam = app(DescargaSAT::class)->TipoDeCambioBMX();
                                                    if ($tip_cam->getStatusCode() === 200) {
                                                        $vals = json_decode($tip_cam->getBody()->getContents());
                                                        $tc_n = floatval($vals->bmx->series[0]->datos[0]->dato);
                                                        DB::table('historico_tcs')->insert([
                                                            'fecha' => Carbon::now(),
                                                            'tipo_cambio' => $tc_n,
                                                            'team_id' => Filament::getTenant()->id
                                                        ]);
                                                    } else {
                                                        $tc_n = 1;
                                                    }
                                                }
                                                $monea = 'USD';
                                                $tot = '$'.number_format(($item->totalusd),2);
                                                $pend = '$'.number_format(($item->pendienteusd),2);
                                            }
                                            else{
                                                $monea = 'MXN';
                                                $tot = '$'.number_format($item->totalmxn,2);
                                                $pend = '$'.number_format($item->pendientemxn,2);
                                            }
                                            $data+= [
                                                $item->id.'|'.$item->xml_id =>
                                                    'Tercero: '.$alm->Emisor_Nombre.' |'.
                                                    'Referencia: '.$item->referencia.' |'.
                                                    'Importe: '.$tot.' |'.
                                                    'Pendiente: '.$pend.' |'.
                                                    'Moneda: '.$monea
                                            ];
                                        }
                                        //dd($data);
                                        return $data;
                                    })->afterStateUpdated(function(Get $get,Set $set){
                                        $factu = $get('Factura');
                                        $factur = explode('|',$factu);
                                        $ingeng = $factur[0];
                                        $factura = $factur[1];
                                        $facts = DB::table('almacencfdis')->where('id',$factura)->get();
                                        $fac = $facts[0];
                                        $tc_n = $get('Tc_Pago');
                                        $set('Emisor',$fac->Emisor_Rfc);
                                        $set('Receptor',$fac->Receptor_Rfc);
                                        $set('Importe',$fac->Total * $fac->TipoCambio);
                                        $set('FacId',$fac->id);
                                        $set('UUID',$fac->UUID);
                                        $set('desfactura',$fac->Serie.$fac->Folio);
                                        $set('ingengid',$ingeng);
                                        $set('Moneda',$fac->Moneda);
                                        if($fac->TipoCambio >0)
                                        $set('tipo_cam_m',$fac->TipoCambio);
                                        else $set('tipo_cam_m',1);
                                        $set('total_orig',$fac->Total);
                                        $set('tipo_cam_n',$tc_n);
                                        $set('Tc_Pago',$tc_n);
                                    })->live(onBlur:true),
                                    TextInput::make('Moneda')->readOnly()->live(),
                                    TextInput::make('Emisor')->readOnly(),
                                    TextInput::make('Receptor')->readOnly(),
                                    TextInput::make('Importe')->readOnly()
                                    ->numeric()->prefix('$'),
                                    TextInput::make('Tc_Pago')
                                        ->readOnly(function (Get $get){
                                            if($get('Moneda') == 'USD') return false;
                                            else return true;
                                        })
                                        ->numeric()->prefix('$')
                                    ->suffixAction(
                                        ActionsAction::make('Ver Importe')->hiddenLabel()
                                        ->icon('fas-dollar-sign')->button()->color(Color::Red)
                                        ->form(function (Form $form,Get $get,$record) {

                                            $mon_pag = floatval($record->importe) / floatval($get('Tc_Pago'));
                                            return $form ->schema([
                                            TextInput::make('Tot_Importe_MXN')->readOnly()->prefix('$')->inlineLabel()
                                                ->label('TOTAL MXN')->default($get('Importe'))->currencyMask(decimalSeparator: '.',precision: 2),
                                            TextInput::make('Tot_Importe_USD')->readOnly()->prefix('$')->inlineLabel()
                                                ->label('TOTAL USD')->default($get('total_orig'))->currencyMask(decimalSeparator: '.',precision: 2),
                                            TextInput::make('Tot_TCFactura')->prefix('$')->inlineLabel()
                                                ->label('Tipo de Cambio Factura')->default($get('tipo_cam_m'))->currencyMask(decimalSeparator: '.',precision: 4),
                                            TextInput::make('Tot_TCPago')->prefix('$')->inlineLabel()
                                                ->label('Tipo de Cambio del Pago')->default($get('Tc_Pago'))->currencyMask(decimalSeparator: '.',precision: 4),
                                            TextInput::make('Tot_Pago_MXN')->prefix('$')->inlineLabel()
                                                ->label('TOTAL PAGO MXN')->default(floatval($record->importe))->currencyMask(decimalSeparator: '.',precision: 2),
                                            TextInput::make('Tot_Pago_USD')->prefix('$')->inlineLabel()
                                                ->label('TOTAL PAGO USD')->default($mon_pag)->currencyMask(decimalSeparator: '.',precision: 2),
                                            ]);
                                        })->modalWidth('md')->modalSubmitAction(false)
                                    ),

                                    Hidden::make('FacId'),
                                    Hidden::make('UUID'),
                                    Hidden::make('desfactura'),
                                    Hidden::make('ingengid'),
                                    Hidden::make('tipo_cam_m'),
                                    Hidden::make('tipo_cam_n'),
                                    Hidden::make('total_orig')
                                ])->columnSpanFull(),
                                Fieldset::make('Activo Fijo')
                                ->visible(function(Get $get){
                                    if($get('Movimiento')== 3) return true;
                                    else return false;
                                })
                                ->schema([
                                    Select::make('Proveedor')
                                    ->searchable()
                                    ->required(function(Get $get){
                                        if($get('Movimiento')== 3) return true;
                                        else return false;
                                    })
                                    ->options(Terceros::where('tipo','Proveedor')->where('team_id',Filament::getTenant()->id)->select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
                                    ->createOptionForm(function($form){
                                        return $form
                                        ->schema([
                                            Forms\Components\TextInput::make('rfc')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('nombre')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(3),
                                            Forms\Components\TextInput::make('tipo')
                                                ->label('Tipo de Tercero')
                                                ->default('Acreedor')
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('cuenta')
                                                ->required()
                                                ->maxLength(255)
                                                ->readOnly()
                                                ->default(function(){
                                                    $nuecta = 20101000;
                                                    $rg = count(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20100000')->get() ?? 0);
                                                    if($rg > 0)
                                                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20100000')->max('codigo')) + 1000;
                                                    return $nuecta;
                                                }),
                                            Forms\Components\TextInput::make('telefono')
                                                ->tel()
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('correo')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('contacto')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\Select::make('regimen')
                                                ->searchable()
                                                ->label('Regimen Fiscal')
                                                ->columnSpan(2)
                                                ->options(Regimenes::all()->pluck('mostrar','clave')),
                                            Forms\Components\Hidden::make('tax_id')
                                                ->default(Filament::getTenant()->taxid),
                                            Forms\Components\Hidden::make('team_id')
                                                ->default(Filament::getTenant()->id),
                                            Forms\Components\TextInput::make('codigopos')
                                                ->label('Codigo Postal')
                                                ->required()
                                                ->maxLength(255),
                                        ])->columns(4);
                                    })
                                    ->createOptionUsing(function(array $data){
                                        $recor = DB::table('terceros')->insertGetId($data);
                                        DB::table('cat_cuentas')->insert([
                                            'nombre' =>  $data['nombre'],
                                            'team_id' => Filament::getTenant()->id,
                                            'codigo'=>$data['cuenta'],
                                            'acumula'=>'20100000',
                                            'tipo'=>'D',
                                            'naturaleza'=>'A',
                                        ]);
                                        $rec = Terceros::where('id',$recor)->get()[0];
                                        return $rec->nombre.'|'.$rec->cuenta;
                                    }),
                                    Select::make('Activo')
                                    ->searchable()
                                    ->label('Activo Fijo')
                                    ->options(Activosfijos::where('team_id',Filament::getTenant()->id)->pluck('descripcion','id'))
                                    ->createOptionForm(function($form){
                                        return $form
                                        ->schema([
                                            Forms\Components\TextInput::make('clave')
                                                ->maxLength(255),
                                            Forms\Components\Select::make('tipoact')
                                            ->label('Tipo de Activo')
                                            ->live()
                                            ->options([
                                                '15100000|17101000'=>'Terrenos',
                                                '15200000|17102000'=>'Edificios',
                                                '15300000|17103000'=>'Maquinaria y equipo',
                                                '15400000|17104000'=>'Automoviles, autobuses, camiones de carga',
                                                '15500000|17105000'=>'Mobiliario y equipo de oficina',
                                                '15600000|17106000'=>'Equipo de computo',
                                                '15700000|17107000'=>'Equipo de comunicacion',
                                                '15800000|17108000'=>'Activos biologicos, vegetales y semovientes',
                                                '15900000|17109000'=>'Obras en proceso de activos fijos',
                                                '16000000|17110000'=>'Otros activos fijos',
                                                '16100000|17111000'=>'Ferrocariles',
                                                '16200000|17112000'=>'Embarcaciones',
                                                '16300000|17113000'=>'Aviones',
                                                '16400000|17114000'=>'Troqueles, moldes, matrices y herramental',
                                                '16500000|17115000'=>'Equipo de comunicaciones telefonicas',
                                                '16600000|17116000'=>'Equipo de comunicacion satelital',
                                                '16700000|17117000'=>'Eq de adaptaciones para personas con capac dif',
                                                '16800000|17118000'=>'Maq y eq de generacion de energia de ftes renov',
                                                '16900000|17119000'=>'Otra maquinaria y equipo',
                                                '17000000|17120000'=>'Adaptaciones y mejoras'
                                            ])
                                            ->afterStateUpdated(function(Get $get,Set $set){
                                                $nucta = $get('tipoact');
                                                $nucta = explode('|',$nucta);
                                                $set('cuentadep',$nucta[1]);
                                                $nuecta = $nucta[0];
                                                $rg = count(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula',$nuecta)->get() ?? 0);
                                                if($rg > 0)
                                                $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula',$nuecta)->max('codigo')) + 1000;
                                                $set('cuentaact',$nuecta);
                                            }),
                                            Forms\Components\TextInput::make('descripcion')
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                            Forms\Components\TextInput::make('marca')
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('modelo')
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('serie')
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('importe')
                                                ->label('Importe Original')
                                                ->required()
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(0),
                                            Forms\Components\TextInput::make('depre')
                                                ->label('Tasa de Depreciacion')
                                                ->required()
                                                ->numeric()
                                                ->postfix('%')
                                                ->default(0),
                                            Forms\Components\TextInput::make('acumulado')
                                                ->label('Depreciacion acumulada')
                                                ->required()
                                                ->prefix('$')
                                                ->numeric()
                                                ->default(0)->readOnly(),
                                            Forms\Components\Select::make('proveedor')
                                                ->searchable()
                                                ->options(Terceros::where(['tipo'=>'Proveedor','team_id'=>Filament::getTenant()->id])->pluck('nombre','id')),
                                            Forms\Components\TextInput::make('cuentadep')
                                                ->label('Cuenta Depreciacion')
                                                ->maxLength(255)
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('cuentaact')
                                                ->label('Cuenta Activo Fijo')
                                                ->maxLength(255)
                                                ->readOnly(),
                                            Forms\Components\Hidden::make('tax_id')
                                                ->default(Filament::getTenant()->tax_id),
                                            Forms\Components\Hidden::make('team_id')
                                                ->default(Filament::getTenant()->id),
                                        ])->columns(3);
                                    })
                                    ->createOptionUsing(function(array $data,$livewire,$record) {
                                        $livewire->recordid = $record->id;
                                        $actf = DB::table('activosfijos')->insertGetId([
                                            'clave'=>$data['clave'],
                                            'descripcion'=>$data['descripcion'],
                                            'marca'=>$data['marca'],
                                            'modelo'=>$data['modelo'],
                                            'serie'=>$data['serie'],
                                            'proveedor'=>$data['proveedor'],
                                            'importe'=>$data['importe'],
                                            'depre'=>$data['depre'],
                                            'acumulado'=>$data['acumulado'],
                                            'cuentadep'=>$data['cuentadep'],
                                            'cuentaact'=>$data['cuentaact'],
                                            'team_id'=>Filament::getTenant()->id
                                        ]);
                                        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Dr')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                                        $dats = Carbon::now();
                                        $fecha = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo.'-'.$dats->day;
                                        $poliza = CatPolizas::create([
                                            'tipo'=>'Dr',
                                            'folio'=>$nopoliza,
                                            'fecha'=>$fecha,
                                            'concepto'=>'Registro de Activo Fijo',
                                            'cargos'=>$data['importe'],
                                            'abonos'=>$data['importe'],
                                            'periodo'=>Filament::getTenant()->periodo,
                                            'ejercicio'=>Filament::getTenant()->ejercicio,
                                            'referencia'=>'S/F',
                                            'uuid'=>'',
                                            'tiposat'=>'Dr',
                                            'idmovb'=>$livewire->recordid,
                                            'team_id'=>Filament::getTenant()->id
                                        ]);
                                        $polno = $poliza['id'];
                                            $aux = Auxiliares::create([
                                                'cat_polizas_id'=>$polno,
                                                'codigo'=>$data['cuentaact'],
                                                'cuenta'=>$data['descripcion'],
                                                'concepto'=>'Registro de Activo Fijo',
                                                'cargo'=>$data['importe'] / 1.16,
                                                'abono'=>0,
                                                'factura'=>'S/F',
                                                'nopartida'=>1,
                                                'team_id'=>Filament::getTenant()->id
                                            ]);
                                            DB::table('auxiliares_cat_polizas')->insert([
                                                'auxiliares_id'=>$aux['id'],
                                                'cat_polizas_id'=>$polno
                                            ]);
                                            $aux = Auxiliares::create([
                                                'cat_polizas_id'=>$polno,
                                                'codigo'=>'11901000',
                                                'cuenta'=>'IVA pendiente de pago',
                                                'concepto'=>'Registro de Activo Fijo',
                                                'cargo'=>($data['importe'] / 1.16) * 0.16,
                                                'abono'=> 0,
                                                'factura'=>'S/F',
                                                'nopartida'=>2,
                                                'team_id'=>Filament::getTenant()->id
                                            ]);
                                            DB::table('auxiliares_cat_polizas')->insert([
                                                'auxiliares_id'=>$aux['id'],
                                                'cat_polizas_id'=>$polno
                                            ]);
                                            $prov = Terceros::where('id',$data['proveedor'])->get()[0];
                                            $aux = Auxiliares::create([
                                                'cat_polizas_id'=>$polno,
                                                'codigo'=>$prov->cuenta,
                                                'cuenta'=>$prov->nombre,
                                                'concepto'=>'Registro de Activo Fijo',
                                                'cargo'=>0,
                                                'abono'=>$data['importe'],
                                                'factura'=>'S/F',
                                                'nopartida'=>3,
                                                'team_id'=>Filament::getTenant()->id
                                            ]);
                                            DB::table('auxiliares_cat_polizas')->insert([
                                                'auxiliares_id'=>$aux['id'],
                                                'cat_polizas_id'=>$polno
                                            ]);
                                            return $data['descripcion'];
                                    })
                                ]),
                                Fieldset::make('Tercero')
                                ->visible(function(Get $get){
                                    if($get('Movimiento')== 2||$get('Movimiento')== 4) return true;
                                    else return false;
                                })
                                ->schema([
                                    Select::make('Tercero')
                                    ->searchable()
                                    ->required(function(Get $get){
                                        if($get('Movimiento')== 2||$get('Movimiento')== 4) return true;
                                        else return false;
                                    })
                                    ->options(Terceros::where('tipo','Acreedor')->where('team_id',Filament::getTenant()->id)->select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
                                    ->createOptionForm(function($form){
                                        return $form
                                        ->schema([
                                            Forms\Components\TextInput::make('rfc')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('nombre')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(3),
                                            Forms\Components\TextInput::make('tipo')
                                                ->label('Tipo de Tercero')
                                                ->default('Acreedor')
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('cuenta')
                                                ->required()
                                                ->maxLength(255)
                                                ->readOnly()
                                                ->default(function(){
                                                    $nuecta = 10701000;
                                                    $rg = count(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','10700000')->get() ?? 0);
                                                    if($rg > 0)
                                                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','10700000')->max('codigo')) + 1000;
                                                    return $nuecta;
                                                }),
                                            Forms\Components\TextInput::make('telefono')
                                                ->tel()
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('correo')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('contacto')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\Select::make('regimen')
                                                ->searchable()
                                                ->label('Regimen Fiscal')
                                                ->columnSpan(2)
                                                ->options(Regimenes::all()->pluck('mostrar','clave')),
                                            Forms\Components\Hidden::make('tax_id')
                                                ->default(Filament::getTenant()->taxid),
                                            Forms\Components\Hidden::make('team_id')
                                                ->default(Filament::getTenant()->id),
                                            Forms\Components\TextInput::make('codigopos')
                                                ->label('Codigo Postal')
                                                ->required()
                                                ->maxLength(255),
                                        ])->columns(4);
                                    })
                                    ->createOptionUsing(function(array $data){
                                        $recor = DB::table('terceros')->insertGetId($data);
                                        DB::table('cat_cuentas')->insert([
                                            'nombre' =>  $data['nombre'],
                                            'team_id' => Filament::getTenant()->id,
                                            'codigo'=>$data['cuenta'],
                                            'acumula'=>'10700000',
                                            'tipo'=>'D',
                                            'naturaleza'=>'D',
                                        ]);
                                        $rec = Terceros::where('id',$recor)->get()[0];
                                        return $rec->nombre.'|'.$rec->cuenta;
                                    })
                                ]),
                            Fieldset::make('Pago de Nomina')
                                ->visible(function(Get $get){
                                    if($get('Movimiento')== 6) return true;
                                    else return false;
                                })
                            ->schema([
                                Select::make('nom_reggasto_cta')->label('Registro del Gasto')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))
                                    ->searchable()->default(function(){return CatCuentas::where('team_id',Filament::getTenant()->id)->where('codigo','60301000')->first()->codigo;}),
                                TextInput::make('nom_reggasto')->numeric()->prefix('$')->hiddenLabel()->default(fn(Get $get)=>$get('importe')),
                                Select::make('nom_retisr_cta')->label('Retencion de ISR')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))
                                    ->searchable()->default(function(){return CatCuentas::where('team_id',Filament::getTenant()->id)->where('codigo','21601000')->first()->codigo;}),
                                TextInput::make('nom_retisr')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_retimss_cta')->label('Retencion IMSS')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))
                                    ->searchable()->default(function(){return CatCuentas::where('team_id',Filament::getTenant()->id)->where('codigo','21611000')->first()->codigo;}),
                                TextInput::make('nom_retimss')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_infonavit_cta')->label('Credito Infonavit')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))
                                    ->searchable()->default(function(){return CatCuentas::where('team_id',Filament::getTenant()->id)->where('codigo','21612000')->first()->codigo;}),
                                TextInput::make('nom_infonavit')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_presempre_cta')->label('Prestamo Empresa')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))
                                    ->searchable()->default(function(){return CatCuentas::where('team_id',Filament::getTenant()->id)->where('codigo','21613000')->first()->codigo;}),
                                TextInput::make('nom_presempre')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_banco_cta')->label('Cuenta Bancaria')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))
                                    ->searchable()
                                    ->default(function ($record){
                                        return BancoCuentas::where('id',$record->cuenta)->get()[0]->codigo;
                                    }),
                                TextInput::make('nom_banco')->numeric()->prefix('$')->hiddenLabel()->default(fn(Get $get)=>$get('importe')),
                            ])->columnSpan(3)->columns(3),
                            Fieldset::make('Anticipo agencia aduanal')
                            ->visible(function(Get $get){
                                if($get('Movimiento')== 7) return true;
                                else return false;
                            })
                            ->schema([
                                Select::make('aduana_dta_cta')->label('DTA')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('aduana_dta')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('aduana_ivaprv_cta')->label('IVA/PRV')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('aduana_ivaprv')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('aduana_igi_cta')->label('IGI/EGE')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('aduana_igi')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('aduana_prv_cta')->label('PRV')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('aduana_prv')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('aduana_iva_cta')->label('IVA')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('aduana_iva')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('aduana_pagos_cta')->label('Pagos en el Extranjero')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('aduana_pagos')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('aduana_bancos_cta')->label('Cuenta Bancaria')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable()
                                    ->default(function ($record){
                                        return BancoCuentas::where('id',$record->cuenta)->get()[0]->codigo;
                                    }),
                                TextInput::make('aduana_bancos')->numeric()->prefix('$')->hiddenLabel()->default(fn(Get $get)=>$get('importe')),
                                Forms\Components\FileUpload::make('aduana_xml')->label('Asociar XML')->inlineLabel()->columnSpan(2)
                            ])->columnSpan(3)->columns(3),
                            Fieldset::make('Captura Manual')
                                ->visible(function(Get $get){
                                    if($get('Movimiento')== 8) return true;
                                    else return false;
                                })
                                ->schema([
                                    Section::make()
                                        ->columns([
                                            'default' => 5,
                                            'sm' => 2,
                                            'md' => 2,
                                            'lg' => 5,
                                            'xl' => 5,
                                            '2xl' => 5,
                                        ])
                                        ->schema([
                                            Forms\Components\TextInput::make('fecha')
                                                ->label('Fecha')
                                                ->default(function() use ($record) {
                                                    return date('d-m-Y', strtotime($record->fecha));
                                                })
                                                ->readOnly(),
                                            Forms\Components\Select::make('tipo')
                                                ->required()
                                                ->live()
                                                ->options([
                                                    'Dr'=>'Dr',
                                                    'Eg'=>'Eg',
                                                ])->afterStateUpdated(function(Get $get,Set $set){
                                                    $nopoliza = intval(DB::table('cat_polizas')
                                                            ->where('team_id',Filament::getTenant()->id)
                                                            ->where('tipo',$get('tipo'))->where('periodo',Filament::getTenant()->periodo)
                                                            ->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                                                    $set('folio',$nopoliza);
                                                    $tipol =$get('tipo');
                                                    if($tipol == 'PV')$tipol = 'Dr';
                                                    if($tipol == 'PG')$tipol = 'Dr';
                                                    $set('tiposat',$tipol);
                                                }),
                                            Forms\Components\TextInput::make('folio')
                                                ->required()
                                                ->numeric()
                                                ->readOnly(),
                                            Forms\Components\Hidden::make('cargos')
                                                ->default(0.00),
                                            Forms\Components\Hidden::make('abonos')
                                                ->default(0.00),
                                            Forms\Components\TextInput::make('concepto')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(4)
                                                ->default($record->concepto),
                                            Forms\Components\TextInput::make('referencia')
                                                ->maxLength(255)
                                                ->prefix('F-'),
                                            Forms\Components\Hidden::make('periodo')
                                                ->default(Filament::getTenant()->periodo),
                                            Forms\Components\Hidden::make('ejercicio')
                                                ->default(Filament::getTenant()->ejercicio),
                                            Forms\Components\Hidden::make('uuid')
                                                ->default(''),
                                            Forms\Components\Hidden::make('tiposat')
                                                ->default(''),
                                            Forms\Components\Hidden::make('team_id')
                                                ->default(Filament::getTenant()->id)
                                                ->required(),
                                        ]),
                                    Section::make('Partidas')
                                        ->columns([
                                            'default' => 5,
                                            'sm' => 5,
                                            'md' => 5,
                                            'lg' => 5,
                                            'xl' => 5,
                                            '2xl' => 5,
                                        ])
                                        ->schema([
                                            TableRepeater::make('detalle')
                                                ->streamlined()
                                                ->defaultItems(5)
                                                ->columnSpanFull()
                                                ->headers([
                                                    Header::make('codigo')->width('150px'),
                                                    Header::make('cuenta')->width('100px'),
                                                    Header::make('cargo')->width('150px'),
                                                    Header::make('abono')->width('150px'),
                                                    Header::make('factura')->width('100px')
                                                        ->label('Referencia'),
                                                    Header::make('concepto')->width('300px'),
                                                ])
                                                ->schema([
                                                    TextInput::make('codigo')->required()
                                                        ->live(onBlur: true)
                                                    ->afterStateUpdated(function($state,Set $set, Get $get){
                                                        $cuenta = CatCuentas::where('team_id',Filament::getTenant()->id)
                                                            ->where('codigo',$state)->first();
                                                        $nom = $cuenta->nombre;
                                                        $set('cuenta',$nom);
                                                        $set('concepto',$get('../../concepto'));
                                                    })->suffixAction(
                                                            Actions\Action::make('BusquedaC')
                                                            ->icon('fas-magnifying-glass')
                                                                ->modalSubmitActionLabel('Seleccionar')
                                                            ->form([
                                                                Forms\Components\Select::make('codigo_b')
                                                                ->label('Buscar Cuenta')
                                                                ->required()
                                                                ->searchable()
                                                                ->options(
                                                                    CatCuentas::where('team_id',Filament::getTenant()->id)
                                                                        ->select('codigo',DB::raw("CONCAT(codigo,' - ',nombre) as nombre"))->orderBy('codigo')->pluck('nombre','codigo'))
                                                            ])->action(function(array $data,Set $set,Get $get){
                                                                $cuenta = CatCuentas::where('codigo',$data['codigo_b'])->first();
                                                                $set('codigo',$cuenta->codigo);
                                                                $set('cuenta',$cuenta->nombre);
                                                                $set('concepto',$get('../../concepto'));

                                                            })
                                                        ),
                                                    TextInput::make('cuenta')->readOnly(),
                                                    TextInput::make('cargo')
                                                        ->currencyMask()
                                                        ->default(0)
                                                        ->live(onBlur:true)
                                                        ->prefix('$')
                                                        ->suffixAction(
                                                            Actions\Action::make('Calcula_1')
                                                                ->label('Calcular')
                                                            ->icon('fas-calculator')
                                                            ->modalSubmitActionLabel('Usar')
                                                            ->form([
                                                                Forms\Components\TextInput::make('Operacion')
                                                                ->default(0)->required()
                                                                ->live(onBlur: true)
                                                                ->afterStateUpdated(function($state,Set $set,Get $get){
                                                                    $s = eval('return '.$state.';');
                                                                    $set('resultado',$s);
                                                                }),
                                                                TextInput::make('resultado')->readOnly(),
                                                            ])->action(function(array $data,Set $set,Get $get){
                                                                $val = floatval($data['resultado']);
                                                                $set('cargo',$val);
                                                            })
                                                          )
                                                        ->afterStateUpdated(function(Get $get,Set $set){
                                                            self::sumas_partidas_manual($get,$set);
                                                        }),
                                                    TextInput::make('abono')
                                                        ->currencyMask()
                                                        ->default(0)
                                                        ->live(onBlur:true)
                                                        ->prefix('$')
                                                        ->suffixAction(
                                                            Actions\Action::make('Calcula_1')
                                                                ->label('Calcular')
                                                                ->icon('fas-calculator')
                                                                ->modalSubmitActionLabel('Usar')
                                                                ->form([
                                                                    Forms\Components\TextInput::make('Operacion')
                                                                        ->default(0)->required()
                                                                        ->live(onBlur: true)
                                                                        ->afterStateUpdated(function($state,Set $set,Get $get){
                                                                            $s = eval('return '.$state.';');
                                                                            $set('resultado',$s);
                                                                        }),
                                                                    TextInput::make('resultado')->readOnly(),
                                                                ])->action(function(array $data,Set $set,Get $get){
                                                                    $val = floatval($data['resultado']);
                                                                    $set('abono',$val);
                                                                })
                                                        )
                                                        ->afterStateUpdated(function(Get $get,Set $set){
                                                            self::sumas_partidas_manual($get,$set);
                                                        }),
                                                    TextInput::make('factura')
                                                        ->label('Referencia')
                                                        ->prefix('F-'),
                                                    TextInput::make('concepto'),
                                                    Hidden::make('team_id')->default(Filament::getTenant()->id),
                                                    //Hidden::make('cuenta'),
                                                    Hidden::make('cat_polizas_id')
                                                        ->default(0),
                                                    Hidden::make('nopartida')
                                                        ->default(0),
                                                ]),
                                        ]),
                                    Fieldset::make('Sumas Iguales')
                                        ->schema([
                                            TextInput::make('cargos_tot')
                                                ->label('Cargos')
                                                ->prefix('$')->readOnly()->currencyMask()->default(0),
                                            TextInput::make('abonos_tot')
                                                ->label('Abonos')
                                                ->prefix('$')->readOnly()->currencyMask()->default(0),
                                        ])->columns(5)
                                ])->columnSpanFull(),

                        ])->columns(4);
                    })
                    ->modalWidth('full')
                    ->label('Procesar')
                    ->accessSelectedRecords()
                    ->icon('fas-check-to-slot')
                    ->action(function (Model $record,$data,Get $get, Set $set) {
                        Self::procesa_s_f($record,$data,$get,$set);
                    }),
                //--------------------------------------------------------------------
                Action::make('procesa_e')
                    ->visible(function($record){
                        if($record->contabilizada == 'SI') return false;
                        if($record->contabilizada == 'NO'&&$record->tipo == 'E') return true;
                    })
                    ->form(function(Form $form,$record,$livewire){
                        $livewire->recordid = $record->id;
                        return $form
                        ->schema([
                            TextInput::make('importe')
                            ->label('Importe Movimiento')
                            ->readOnly()
                            ->currencyMask()
                            ->prefix('$')
                            ->default(function(Model $record){
                                return number_format($record->importe,2);
                            }),
                            TextInput::make('importefactu')
                            ->visible(false)
                            ->label('Importe Facturas')
                            ->placeholder(function (Get $get,Set $set) {
                                $valor = collect($get('Facturas'))->pluck('Importe')->sum();
                                return floatval($valor);
                            })
                            ->readOnly()
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                            Select::make('Movimiento')
                                ->required()
                                ->live()
                                ->options([
                                    '2'=>'Cobro no identificado',
                                    '3'=>'Prestamo',
                                    '4'=>'Otros Ingresos',
                                    '5'=>'Captura Manual',
                                ])->columnSpan(2),
                                TableRepeater::make('Facturas')
                                ->visible(function(Get $get){
                                    if($get('Movimiento') == 1) return true;
                                    else return false;
                                })
                                ->headers([
                                    Header::make('Factura')->width('200px'),
                                    Header::make('Emisor')->width('100px'),
                                    Header::make('Receptor')->width('100px'),
                                    Header::make('Importe')->width('100px')
                                ])
                                ->schema([
                                    Select::make('Factura')
                                        ->searchable()
                                        ->options(function (){
                                            $ing_ret = IngresosEgresos::where('team_id',Filament::getTenant()->id)->where('tipo',1)->where('pendientemxn','>',0)->where('tcambio','<',2)->get();
                                            $data = [];
                                            foreach ($ing_ret as $item){
                                                $alm = Almacencfdis::where('id',$item->xml_id)->first();
                                                $tot = '$'.number_format($item->totalmxn,2);
                                                $pend = '$'.number_format($item->pendientemxn,2);
                                                $monea = 'MXN';
                                                if($item->tcambio > 1) {
                                                    $tc_n = DB::table('historico_tcs')->latest('id')->first()->tipo_cambio;
                                                    $monea = 'USD';
                                                    $tot = '$'.number_format(($item->totalusd),2);
                                                    $pend = '$'.number_format(($item->pendienteusd),2);
                                                }
                                                else{
                                                    $monea = 'MXN';
                                                    $tot = '$'.number_format($item->totalmxn,2);
                                                    $pend = '$'.number_format($item->pendientemxn,2);
                                                }
                                                $column = "
                                            <table>
                                                <tr>
                                                    <th style='padding: 10px'>Tercero</th>
                                                    <th style='padding: 10px'>Referencia</th>
                                                    <th style='padding: 10px'>Importe</th>
                                                    <th style='padding: 10px'>Pendiente</th>
                                                    <th style='padding: 10px'>Moneda</th>
                                                </tr>
                                                <tr>
                                                    <td class='border' style='padding: 10px'>$alm->Receptor_Nombre</td>
                                                    <td class='border' style='padding: 10px'>$item->referencia</td>
                                                    <td class='border' style='padding: 10px'>$tot</td>
                                                    <td class='border' style='padding: 10px'>$pend</td>
                                                    <td class='border' style='padding: 10px'>$monea</td>
                                                </tr>
                                            </table>";
                                                $data[] = [
                                                    $item->id.'|'.$item->xml_id => $column
                                                ];
                                            }
                                            //dd($data);
                                            return $data;
                                        })->allowHtml(true)
                                        ->afterStateUpdated(function(Get $get,Set $set){
                                            $factu = $get('Factura');
                                            $factur = explode('|',$factu);
                                            $ingeng = $factur[0];
                                            $factura = $factur[1];
                                            $facts = DB::table('almacencfdis')->where('id',$factura)->get();
                                            $fac = $facts[0];
                                            $tc_n = 1;
                                            if($fac->Moneda != 'MXN')
                                            {
                                                $tc_n = DB::table('historico_tcs')->latest('id')->first()->tipo_cambio;
                                            }
                                            $set('Emisor',$fac->Emisor_Rfc);
                                            $set('Receptor',$fac->Receptor_Rfc);
                                            $set('Importe',$fac->Total * $tc_n);
                                            $set('FacId',$fac->id);
                                            $set('UUID',$fac->UUID);
                                            $set('desfactura',$fac->Serie.$fac->Folio);
                                            $set('ingengid',$ingeng);
                                        })->live(onBlur:true),
                                    TextInput::make('Emisor')->readOnly(),
                                    TextInput::make('Receptor')->readOnly(),
                                    TextInput::make('Importe')->readOnly()
                                    ->numeric()->prefix('$'),
                                    Hidden::make('FacId'),
                                    Hidden::make('UUID'),
                                    Hidden::make('desfactura'),
                                    Hidden::make('desuuid'),
                                    Hidden::make('ingengid'),
                                ])->columnSpanFull(),
                                Fieldset::make('Tercero')
                                ->visible(function(Get $get){
                                    if($get('Movimiento')== 3) return true;
                                    else return false;
                                })
                                ->schema([
                                    Select::make('Tercero')
                                    ->searchable()
                                    ->required(function(Get $get){
                                        if($get('Movimiento')== 3) return true;
                                        else return false;
                                    })
                                    ->options(Terceros::where('team_id',Filament::getTenant()->id)->select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
                                    ->createOptionForm(function($form){
                                        return $form
                                        ->schema([
                                            Forms\Components\TextInput::make('rfc')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('nombre')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(3),
                                            Forms\Components\TextInput::make('tipo')
                                                ->label('Tipo de Tercero')
                                                ->default('Acreedor')
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('cuenta')
                                                ->required()
                                                ->maxLength(255)
                                                ->readOnly()
                                                ->default(function(){
                                                    $nuecta = 20501000;
                                                    $rg = count(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20500000')->get() ?? 0);
                                                    if($rg > 0)
                                                    $nuecta = intval(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->where('acumula','20500000')->max('codigo')) + 1000;
                                                    return $nuecta;
                                                }),
                                            Forms\Components\TextInput::make('telefono')
                                                ->tel()
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('correo')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('contacto')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\Select::make('regimen')
                                                ->searchable()
                                                ->label('Regimen Fiscal')
                                                ->columnSpan(2)
                                                ->options(Regimenes::all()->pluck('mostrar','clave')),
                                            Forms\Components\Hidden::make('tax_id')
                                                ->default(Filament::getTenant()->taxid),
                                            Forms\Components\Hidden::make('team_id')
                                                ->default(Filament::getTenant()->id),
                                            Forms\Components\TextInput::make('codigopos')
                                                ->label('Codigo Postal')
                                                ->required()
                                                ->maxLength(255),
                                        ])->columns(4);
                                    })
                                    ->createOptionUsing(function(array $data){
                                        $recor = DB::table('terceros')->insertGetId($data);
                                        DB::table('cat_cuentas')->insert([
                                            'nombre' =>  $data['nombre'],
                                            'team_id' => Filament::getTenant()->id,
                                            'codigo'=>$data['cuenta'],
                                            'acumula'=>'20500000',
                                            'tipo'=>'D',
                                            'naturaleza'=>'A',
                                        ]);
                                        $rec = Terceros::where('id',$recor)->get()[0];
                                        return $rec->nombre.'|'.$rec->cuenta;
                                    })
                                ]),
                            Fieldset::make('Captura Manual')
                                ->visible(function(Get $get){
                                    if($get('Movimiento')== 5) return true;
                                    else return false;
                                })
                                ->schema([
                                    Section::make()
                                        ->columns([
                                            'default' => 5,
                                            'sm' => 2,
                                            'md' => 2,
                                            'lg' => 5,
                                            'xl' => 5,
                                            '2xl' => 5,
                                        ])
                                        ->schema([
                                            Forms\Components\TextInput::make('fecha')
                                                ->label('Fecha')
                                                ->default(function() use ($record) {
                                                    return date('d-m-Y', strtotime($record->fecha));
                                                })
                                                ->readOnly(),
                                            Forms\Components\Select::make('tipo')
                                                ->required()
                                                ->live()
                                                ->options([
                                                    'Dr'=>'Dr',
                                                    'Ig'=>'Ig',
                                                ])->afterStateUpdated(function(Get $get,Set $set){
                                                    $nopoliza = intval(DB::table('cat_polizas')
                                                            ->where('team_id',Filament::getTenant()->id)
                                                            ->where('tipo',$get('tipo'))->where('periodo',Filament::getTenant()->periodo)
                                                            ->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                                                    $set('folio',$nopoliza);
                                                    $tipol =$get('tipo');
                                                    if($tipol == 'PV')$tipol = 'Dr';
                                                    if($tipol == 'PG')$tipol = 'Dr';
                                                    $set('tiposat',$tipol);
                                                }),
                                            Forms\Components\TextInput::make('folio')
                                                ->required()
                                                ->numeric()
                                                ->readOnly(),
                                            Forms\Components\Hidden::make('cargos')
                                                ->default(0.00),
                                            Forms\Components\Hidden::make('abonos')
                                                ->default(0.00),
                                            Forms\Components\TextInput::make('concepto')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(4)
                                                ->default($record->concepto),
                                            Forms\Components\TextInput::make('referencia')
                                                ->maxLength(255)
                                                ->prefix('F-'),
                                            Forms\Components\Hidden::make('periodo')
                                                ->default(Filament::getTenant()->periodo),
                                            Forms\Components\Hidden::make('ejercicio')
                                                ->default(Filament::getTenant()->ejercicio),
                                            Forms\Components\Hidden::make('uuid')
                                                ->default(''),
                                            Forms\Components\Hidden::make('tiposat')
                                                ->default(''),
                                            Forms\Components\Hidden::make('team_id')
                                                ->default(Filament::getTenant()->id)
                                                ->required(),
                                        ]),
                                    Section::make('Partidas')
                                        ->columns([
                                            'default' => 5,
                                            'sm' => 5,
                                            'md' => 5,
                                            'lg' => 5,
                                            'xl' => 5,
                                            '2xl' => 5,
                                        ])
                                        ->schema([
                                            TableRepeater::make('detalle')
                                                ->streamlined()
                                                ->columnSpanFull()
                                                ->defaultItems(5)
                                                ->headers([
                                                    Header::make('codigo')->width('150px'),
                                                    Header::make('cuenta')->width('100px'),
                                                    Header::make('cargo')->width('150px'),
                                                    Header::make('abono')->width('150px'),
                                                    Header::make('factura')->width('100px')
                                                        ->label('Referencia'),
                                                    Header::make('concepto')->width('300px'),
                                                ])
                                                ->schema([
                                                    TextInput::make('codigo')->required()
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function($state,Set $set, Get $get){
                                                            $cuenta = CatCuentas::where('team_id',Filament::getTenant()->id)
                                                                ->where('codigo',$state)->first();
                                                            $nom = $cuenta->nombre;
                                                            $set('cuenta',$nom);
                                                            $set('concepto',$get('../../concepto'));
                                                        })->suffixAction(
                                                            Actions\Action::make('BusquedaC')
                                                                ->icon('fas-magnifying-glass')
                                                                ->modalSubmitActionLabel('Seleccionar')
                                                                ->form([
                                                                    Forms\Components\Select::make('codigo_b')
                                                                        ->label('Buscar Cuenta')
                                                                        ->required()
                                                                        ->searchable()
                                                                        ->options(
                                                                            CatCuentas::where('team_id',Filament::getTenant()->id)
                                                                                ->select('codigo',DB::raw("CONCAT(codigo,' - ',nombre) as nombre"))->orderBy('codigo')->pluck('nombre','codigo'))
                                                                ])->action(function(array $data,Set $set,Get $get){
                                                                    $cuenta = CatCuentas::where('codigo',$data['codigo_b'])->first();
                                                                    $set('codigo',$cuenta->codigo);
                                                                    $set('cuenta',$cuenta->nombre);
                                                                    $set('concepto',$get('../../concepto'));

                                                                })
                                                        ),
                                                    TextInput::make('cuenta')->readOnly(),
                                                    TextInput::make('cargo')
                                                        ->currencyMask()
                                                        ->default(0)
                                                        ->live(onBlur:true)
                                                        ->prefix('$')
                                                        ->suffixAction(
                                                            Actions\Action::make('Calcula_1')
                                                                ->label('Calcular')
                                                                ->icon('fas-calculator')
                                                                ->modalSubmitActionLabel('Usar')
                                                                ->form([
                                                                    Forms\Components\TextInput::make('Operacion')
                                                                        ->default(0)->required()
                                                                        ->live(onBlur: true)
                                                                        ->afterStateUpdated(function($state,Set $set,Get $get){
                                                                            $s = eval('return '.$state.';');
                                                                            $set('resultado',$s);
                                                                        }),
                                                                    TextInput::make('resultado')->readOnly(),
                                                                ])->action(function(array $data,Set $set,Get $get){
                                                                    $val = floatval($data['resultado']);
                                                                    $set('cargo',$val);
                                                                })
                                                        )
                                                        ->afterStateUpdated(function(Get $get,Set $set){
                                                            self::sumas_partidas_manual($get,$set);
                                                        }),
                                                    TextInput::make('abono')
                                                        ->currencyMask()
                                                        ->default(0)
                                                        ->live(onBlur:true)
                                                        ->prefix('$')
                                                        ->suffixAction(
                                                            Actions\Action::make('Calcula_1')
                                                                ->label('Calcular')
                                                                ->icon('fas-calculator')
                                                                ->modalSubmitActionLabel('Usar')
                                                                ->form([
                                                                    Forms\Components\TextInput::make('Operacion')
                                                                        ->default(0)->required()
                                                                        ->live(onBlur: true)
                                                                        ->afterStateUpdated(function($state,Set $set,Get $get){
                                                                            $s = eval('return '.$state.';');
                                                                            $set('resultado',$s);
                                                                        }),
                                                                    TextInput::make('resultado')->readOnly(),
                                                                ])->action(function(array $data,Set $set,Get $get){
                                                                    $val = floatval($data['resultado']);
                                                                    $set('abono',$val);
                                                                })
                                                        )
                                                        ->afterStateUpdated(function(Get $get,Set $set){
                                                            self::sumas_partidas_manual($get,$set);
                                                        }),
                                                    TextInput::make('factura')
                                                        ->label('Referencia')
                                                        ->prefix('F-'),
                                                    TextInput::make('concepto'),
                                                    Hidden::make('team_id')->default(Filament::getTenant()->id),
                                                    Hidden::make('cuenta'),
                                                    Hidden::make('cat_polizas_id')
                                                        ->default(0),
                                                    Hidden::make('nopartida')
                                                        ->default(0),
                                                ]),
                                        ]),
                                    Fieldset::make('Sumas Iguales')
                                        ->schema([
                                            TextInput::make('cargos_tot')
                                                ->label('Cargos')
                                                ->prefix('$')->readOnly()->currencyMask()->default(0),
                                            TextInput::make('abonos_tot')
                                                ->label('Abonos')
                                                ->prefix('$')->readOnly()->currencyMask()->default(0),
                                        ])->columns(5)
                                ])->columnSpanFull()
                        ])->columns(4);
                    })
                    ->modalWidth('7xl')
                    ->label('Procesar')
                    ->accessSelectedRecords()
                    ->modalSubmitActionLabel('Grabar')
                    ->icon('fas-check-to-slot')
                    ->action(function (Model $record,$data,Get $get, Set $set) {
                        Self::procesa_e_f($record,$data,$get,$set);
                    }),
                    Action::make('Pagos Multi-Factura')
                        ->label('Pagos a Facturas')
                        ->icon('fas-money-check-dollar')
                        ->visible(function($record){
                            if($record->contabilizada == 'SI') return false;
                            if($record->contabilizada != 'SI'&&$record->tipo == 'S') return true;
                        })
                        ->url(fn($record)=>Pages\Pagos::getUrl(['record'=>$record])),
                Action::make('traspaso_cuentas')
                    ->label('Traspaso entre Cuentas')
                    ->icon('fas-exchange-alt')
                    ->visible(function($record){
                        if($record->contabilizada == 'SI') return false;
                        if($record->contabilizada == 'NO'&&$record->tipo == 'S') return true;
                    })->form(function(Form $form,$record){
                          return $form
                          ->schema([
                              Forms\Components\Livewire::make(TraspasoBanco::class,['record'=>$record])
                          ]);
                    })->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('Pago de Nomina')
                    ->icon('fas-user')
                    ->visible(function($record){
                        if($record->contabilizada == 'SI') return false;
                        if($record->contabilizada == 'NO'&&$record->tipo == 'S') return true;
                    })
                    ->modalWidth('full')
                    ->modalSubmitActionLabel('Grabar')
                    ->form(function($record,Form $form){
                        return
                            $form->schema([
                                TextInput::make('importe_p')->label('Importe')
                                ->default($record->importe)->readOnly()->prefix('$')->currencyMask(),
                                Hidden::make('used_ban')->default('NO'),
                                Select::make('recibo_nomina')
                                    ->searchable()
                                    ->label('Recibo de Nomina')
                                    ->options(Almacencfdis::select(DB::raw("CONCAT('Recibo:',Serie,Folio,'  -  Receptor: ',Receptor_Nombre,'  -  Fecha: ',Fecha,'  -  Importe: ',Total) as recibo,id"))
                                        ->where('team_id',Filament::getTenant()->id)
                                        ->where('xml_type','Emitidos')
                                        ->where('TipoDeComprobante','N')
                                        ->where('used','NO')
                                        ->where('periodo',Filament::getTenant()->periodo)
                                        ->where('ejercicio',Filament::getTenant()->ejercicio)
                                        ->pluck('recibo','id'))
                                    ->live(onBlur: true)
                                    ->columnSpan(3)
                                    ->afterStateUpdated(function(Get $get,Set $set)use ($record){
                                        $record_a = Almacencfdis::where('id',$get('recibo_nomina'))->first();
                                        $xml_content = $record_a->content;
                                        $cfdi = Cfdi::newFromString($xml_content);
                                        $comp = $cfdi->getQuickReader();
                                        $receptor = $comp->Receptor;
                                        $complemento = $comp->Complemento;
                                        $fisc = $complemento->TimbreFiscalDigital;
                                        $nomina = $complemento->Nomina;
                                        $percepciones = $nomina->Percepciones;
                                        $otros_pagos = $nomina->OtrosPagos;
                                        $deducciones = $nomina->Deducciones;
                                        //dd($comp);
                                        $set('importe_p_nomina',number_format($comp['Total'],2));
                                        $conce = 'NOMINA '.$receptor['Nombre'];
                                        $set('concepto_poliza','NOMINA '.$receptor['Nombre']);
                                        $set('pol_referencia',$comp['serie'].$comp['folio']);
                                        $set('pol_uuid',$fisc['UUID']);
                                        $detalle = $get('detalle_nomina');
                                        $banco_cta = BancoCuentas::where('id',$record->cuenta)->first();
                                        $cta_con_ban = CatCuentas::where('codigo',$banco_cta->codigo)
                                            ->where('team_id',Filament::getTenant()->id)
                                            ->first();
                                        $cargos_t = 0;
                                        $abonos_t = 0;
                                        if($get('used_ban') == 'NO') {
                                            $detalle[] = [
                                                'Cuenta_Con' => $cta_con_ban->id,
                                                'Cuenta' => $cta_con_ban->codigo,
                                                'Nombre' => $cta_con_ban->nombre,
                                                'Concepto' => $conce,
                                                'Cargo' => 0,
                                                'Abono' => $record->importe,
                                                'Referencia' => $comp['serie'] . $comp['folio'],
                                                'UUID' => $fisc['UUID'],
                                            ];

                                            $set('used_ban','SI');
                                        }
                                        $abonos_t += floatval($record->importe);
                                        foreach($percepciones() as $percepcion){
                                            $cta = '50102000';
                                            if($percepcion['Clave'] == '052') $cta = '21611000';
                                            if($percepcion['Clave'] == '045') $cta = '21601000';
                                            if($percepcion['Clave'] == '016') $cta = '21612000';
                                            $nom = CatCuentas::where('codigo',$cta)->where('team_id',Filament::getTenant()->id)->first();
                                            $detalle[] = [
                                                'Cuenta_Con'=>$nom?->id ?? '',
                                                'Cuenta'=>$nom?->nombre ?? 'NO REGISTRADA',
                                                'Nombre'=>$percepcion['Clave'].' '.$percepcion['Concepto'],
                                                'Concepto'=>$conce,
                                                'Cargo'=>floatval($percepcion['ImporteGravado'])+floatval($percepcion['ImporteExento']),
                                                'Abono'=>0,
                                                'Referencia'=>$comp['serie'].$comp['folio'],
                                                'UUID'=>$fisc['UUID'],
                                            ];
                                            $cargos_t+=floatval($percepcion['ImporteGravado'])+floatval($percepcion['ImporteExento']);
                                        }
                                        foreach($otros_pagos() as $otropago){
                                            if(floatval($otropago['Importe']) > 0) {
                                                $cta = '50102000';
                                                if($otropago['Clave'] == '052') $cta = '21611000';
                                                if($otropago['Clave'] == '045') $cta = '21601000';
                                                if($otropago['Clave'] == '016') $cta = '21612000';
                                                $nom = CatCuentas::where('codigo',$cta)->where('team_id',Filament::getTenant()->id)->first();
                                                $detalle[] = [
                                                    'Cuenta_Con' => $nom?->id ?? '',
                                                    'Cuenta' => $nom?->nombre ?? 'NO REGISTRADA',
                                                    'Nombre' => $otropago['Clave'].' '.$otropago['Concepto'],
                                                    'Concepto' => $conce,
                                                    'Cargo' => floatval($otropago['Importe']),
                                                    'Abono' => 0,
                                                    'Referencia' => $comp['serie'] . $comp['folio'],
                                                    'UUID' => $fisc['UUID'],
                                                ];
                                                $cargos_t+=floatval($otropago['Importe']);
                                            }
                                        }
                                        foreach($deducciones() as $deduccion){
                                            $cta = '50102000';
                                            if($deduccion['Clave'] == '052') $cta = '21611000';
                                            if($deduccion['Clave'] == '045') $cta = '21601000';
                                            if($deduccion['Clave'] == '016') $cta = '21612000';
                                            $nom = CatCuentas::where('codigo',$cta)->where('team_id',Filament::getTenant()->id)->first();
                                            $detalle[] = [
                                                'Cuenta_Con'=>$nom?->id ?? '',
                                                'Cuenta'=>$nom?->nombre ?? 'NO REGISTRADA',
                                                'Nombre'=>$deduccion['Clave'].' '.$deduccion['Concepto'],
                                                'Concepto'=>$conce,
                                                'Cargo'=>0,
                                                'Abono'=>floatval($deduccion['Importe']),
                                                'Referencia'=>$comp['serie'].$comp['folio'],
                                                'UUID'=>$fisc['UUID'],
                                            ];
                                            $abonos_t+=floatval($deduccion['Importe']);
                                        }
                                        //dd($detalle);
                                        $set('detalle_nomina',$detalle);
                                        $set('cargos_poliza',round($cargos_t,2));
                                        $set('abonos_poliza',round($abonos_t,2));
                                    }),
                                TextInput::make('importe_p_nomina')->label('Importe Nomina')->readOnly()->prefix('$')->currencyMask()->default(0.00),
                                TextInput::make('concepto_poliza')->label('Concepto'),
                                TextInput::make('pol_referencia')->label('Referencia')->readOnly(),
                                TextInput::make('pol_uuid')->label('UUID')->readOnly(),
                                TableRepeater::make('detalle_nomina')
                                ->columnSpanFull()->streamlined()
                                ->defaultItems(0)
                                ->reorderable(false)
                                ->emptyLabel('No hay datos')
                                ->headers([
                                    Header::make('Cuenta_Con')->label('Cuenta'),
                                    //Header::make('Cuenta'),
                                    Header::make('Nombre'),
                                    Header::make('Concepto'),
                                    Header::make('Cargo'),
                                    Header::make('Abono'),
                                    Header::make('Referencia'),
                                    Header::make('UUID'),
                                ])->schema([
                                    Select::make('Cuenta_Con')
                                        ->searchable()
                                        ->required()
                                        ->options(CatCuentas::select(DB::raw("CONCAT(codigo,' - ',nombre) as cuenta,id"))
                                        ->where('team_id',Filament::getTenant()->id)
                                        ->pluck('cuenta','id'))
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function(Get $get,Set $set)use ($record){
                                            $cta = $get('Cuenta_Con');
                                            $cat = CatCuentas::where('id',$cta)->first();
                                            $set('Cuenta',$cat?->codigo ?? '');
                                            $set('Nombre',$cat?->nombre ?? '');
                                        }),
                                    Hidden::make('Cuenta'),
                                    TextInput::make('Nombre'),
                                    TextInput::make('Concepto'),
                                    TextInput::make('Cargo')->prefix('$')->currencyMask(decimalSeparator: '.',precision: 4)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function(Get $get,Set $set){
                                            self::sumas_nomina($get,$set);
                                        }),
                                    TextInput::make('Abono')->prefix('$')->currencyMask(decimalSeparator: '.',precision: 4)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function(Get $get,Set $set){
                                            self::sumas_nomina($get,$set);
                                        }),
                                    TextInput::make('Referencia'),
                                    TextInput::make('UUID'),
                                ]),
                                Fieldset::make('Totales')->schema([
                                    TextInput::make('cargos_poliza')->label('Cargos')->readOnly()->prefix('$')->currencyMask(decimalSeparator: '.',precision: 4)->default(0.00),
                                    TextInput::make('abonos_poliza')->label('Abonos')->readOnly()->prefix('$')->currencyMask(decimalSeparator: '.',precision: 4)->default(0.00),
                                ])->columnSpanFull()
                            ])->columns(4);
                    })
                    ->before(function($record,$data,$action){
                        $cargos = floatval($data['cargos_poliza']);
                        $abonos = floatval($data['abonos_poliza']);
                        if($cargos != $abonos ){
                            Notification::make()->title('Cargos y Abonos no son iguales')->warning()->send();
                            $action->cancel();
                        }
                    })
                    ->action(function($record,$data)
                    {
                        $detalles = $data['detalle_nomina'];
                        Almacencfdis::where('id',$data['recibo_nomina'])->update([
                            'used'=>'SI'
                        ]);
                        //dd($detalles);
                        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Dr')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                        $poliza = CatPolizas::create([
                            'tipo' => 'Dr',
                            'folio' => $nopoliza,
                            'fecha' => Carbon::create($record->fecha)->format('Y-m-d'),
                            'concepto' => $data['concepto_poliza'],
                            'cargos' => floatval($data['cargos_poliza']),
                            'abonos' => floatval($data['abonos_poliza']),
                            'periodo' => Filament::getTenant()->periodo,
                            'ejercicio' => Filament::getTenant()->ejercicio,
                            'referencia' => $data['pol_referencia'],
                            'uuid' => $data['pol_uuid'],
                            'tiposat' => 'Dr',
                            'team_id' => Filament::getTenant()->id,
                            'idmovb' => $record->id
                        ]);
                        $polno = $poliza['id'];
                        $partida = 1;
                        foreach ($detalles as $detalle) {
                            $aux = Auxiliares::create([
                                'cat_polizas_id' => $polno,
                                'codigo' => $detalle['Cuenta'],
                                'cuenta' => $detalle['Nombre'],
                                'concepto' => $detalle['Concepto'],
                                'cargo' => $detalle['Cargo'],
                                'abono' => $detalle['Abono'],
                                'factura' => $detalle['Referencia'],
                                'nopartida' => $partida,
                                'team_id' => Filament::getTenant()->id,
                            ]);
                            DB::table('auxiliares_cat_polizas')->insert([
                                'auxiliares_id' => $aux['id'],
                                'cat_polizas_id' => $polno
                            ]);
                            $partida++;
                        }
                        Movbancos::where('id',$record->id)->decrement('pendiente_apli',floatval($data['importe_p']));
                        Movbancos::where('id',$record->id)->update([
                            'contabilizada' => 'SI'
                        ]);
                        Notification::make()->title('Poliza -Dr '.$nopoliza.'- Grabada')->success()->send();
                    }),
                //--------------------------------------------------------------------------------------------------
                    Action::make('Cobros Multi-Factura')
                        ->label('Cobros a Facturas')
                        ->icon('fas-money-check-dollar')
                        ->visible(function($record){
                            if($record->contabilizada == 'SI') return false;
                            if($record->contabilizada != 'SI'&&$record->tipo == 'E') return true;
                        })
                    ->url(fn($record)=>Pages\Cobros::getUrl(['record'=>$record]))
                //--------------------------------------------------------------------------------------------------
                ])->color('primary')
                ->visible(function(){
                    $team = Filament::getTenant()->id;
                    $periodo = Filament::getTenant()->periodo;
                    $ejercicio = Filament::getTenant()->ejercicio;
                    if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                    {
                        return true;
                    }
                    else{
                        $estado = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first()->estado;
                        if($estado == 1) return true;
                        else return false;
                    }
                }),
            ])->actionsPosition(ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                Filter::make('created_at')
                ->form([
                    DatePicker::make('Fecha_Inicial')
                    ->default(function(){
                        $ldom = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo ?? 2020-1;
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
            ->defaultSort('Fecha', 'asc');
    }

    public static function sumas_nomina(Get $get,Set $set) :void
    {
        $detalles =$get('../../detalle_nomina');
        $cargos = 0;
        $abonos = 0;
        foreach($detalles as $detalle){
            $cargos+=floatval($detalle['Cargo']);
            $abonos+=floatval($detalle['Abono']);
        }
        $set('../../cargos_poliza',round($cargos,2));
        $set('../../abonos_poliza',round($abonos,2));
    }
    public static function sumas(Get $get,Set $set,$data) :void
    {
        $col = array_column($get('../../Facturas'),'Importe');
        $suma = array_sum($col);
        $set('importefactu',$suma);
    }
    public static function procesa_e_f($record,$data)
    {
        //dd($data);

        $facts =$data['Facturas'] ?? 0;
        //dd($facts[0]);
        DB::table('movbancos')->where('id',$record->id)->update([
            'tercero'=>$facts[0]['Receptor'] ?? 'N/A',
            'factura'=>$facts[0]['desfactura'] ?? 'N/A',
            'uuid'=>$facts[0]['UUID'] ?? 'N/A',
            'contabilizada'=>'SI'
        ]);
        if($data['Movimiento'] == 1) $fss = DB::table('almacencfdis')->where('id',$facts[0]['FacId'])->get();
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->get();
        if($data['Movimiento'] == 1) $ter = DB::table('terceros')->where('rfc',$facts[0]['Receptor'])->get();
        if($data['Movimiento'] == 1) $nom = $fss[0]->Receptor_Nombre;
        //-------------------------------------------------------------------
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Ig')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        if($data['Movimiento'] == 1) {
            $ingengid = $facts[0]['ingengid'] ?? 0;
            $pags = DB::table('ingresos_egresos')->where('id', $ingengid)->get()[0];
            $tc_n = DB::table('historico_tcs')->latest('id')->first()->tipo_cambio;
            $npagusd = $pags->pagadousd + $record->importe;
            $npendusd = $pags->pendienteusd - $record->importe;
            $npag = $pags->pagadomxn + $record->importe;
            $npend = $pags->pendientemxn - $record->importe;
            if ($fss[0]->Moneda == 'USD') {
                $npag = $pags->pagadomxn + ($record->importe * $tc_n);
                $npend = $pags->pendientemxn - ($record->importe * $tc_n);
            } else {
                $tc_n = 1;
            }
            if ($npend < 0) $npend = 0;
            if ($npendusd < 0) $npendusd = 0;
            DB::table('ingresos_egresos')->where('id', $ingengid)
                ->update([
                    'pagadomxn' => $npag,
                    'pendientemxn' => $npend,
                    'pagadousd' => $npagusd,
                    'pendienteusd' => $npendusd,
                ]);
        }
        if($data['Movimiento'] == 1)
        {
            $poliza = CatPolizas::create([
                'tipo'=>'Ig',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>$nom,
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>$facts[0]['desfactura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Ig',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ter[0]->cuenta,
                    'cuenta'=>$ter[0]->nombre,
                    'concepto'=>$nom,
                    'cargo'=>0,
                    'abono'=>$record->importe,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>1,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'20801000',
                    'cuenta'=>'IVA trasladado cobrado',
                    'concepto'=>$nom,
                    'cargo'=>0,
                    'abono'=>($record->importe /1.16) * 0.16,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>2,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'20901000',
                    'cuenta'=>'IVA trasladado no cobrado',
                    'concepto'=>$nom,
                    'cargo'=>($record->importe /1.16) * 0.16,
                    'abono'=>0,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>3,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ban[0]->codigo,
                    'cuenta'=>$ban[0]->cuenta,
                    'concepto'=>$nom,
                    'cargo'=>$record->importe,
                    'abono'=>0,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>4,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
            }
            if($data['Movimiento'] == 2)
            {
                $poliza = CatPolizas::create([
                    'tipo'=>'Ig',
                    'folio'=>$nopoliza,
                    'fecha'=>$record->fecha,
                    'concepto'=>'Cobro No Identificado',
                    'cargos'=>$record->importe,
                    'abonos'=>$record->importe,
                    'periodo'=>Filament::getTenant()->periodo,
                    'ejercicio'=>Filament::getTenant()->ejercicio,
                    'referencia'=>'N/I',
                    'uuid'=>'',
                    'tiposat'=>'Ig',
                    'team_id'=>Filament::getTenant()->id,
                    'idmovb'=>$record->id
                ]);
                $polno = $poliza['id'];
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>'10501001',
                        'cuenta'=>'Clientes Globales',
                        'concepto'=>'Cobro No Identificado',
                        'cargo'=>0,
                        'abono'=>$record->importe,
                        'factura'=>'N/I',
                        'nopartida'=>1,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>'20801000',
                        'cuenta'=>'IVA trasladado cobrado',
                        'concepto'=>'Cobro No Identificado',
                        'cargo'=>0,
                        'abono'=>($record->importe /1.16) * 0.16,
                        'factura'=>'N/I',
                        'nopartida'=>2,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>'20901000',
                        'cuenta'=>'IVA trasladado no cobrado',
                        'concepto'=>'Cobro No Identificado',
                        'cargo'=>($record->importe /1.16) * 0.16,
                        'abono'=>0,
                        'factura'=>'N/I',
                        'nopartida'=>3,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>$ban[0]->codigo,
                        'cuenta'=>$ban[0]->cuenta,
                        'concepto'=>'Cobro No Identificado',
                        'cargo'=>$record->importe,
                        'abono'=>0,
                        'factura'=>'N/I',
                        'nopartida'=>4,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                }
            if($data['Movimiento'] == 3)
            {
                $dater = explode('|',$data['Tercero']);
                $tc = floatval($record->tcambio);
                if($tc == 0) $tc = 1;
                if($tc > 1){
                    $dolares = floatval($record->importe);
                    $pesos = $dolares * $tc;
                    $comple = $pesos - $dolares;
                }else{
                    $dolares = floatval($record->importe);
                    $pesos = floatval($record->importe);
                    $comple = 0;
                }
                $poliza = CatPolizas::create([
                    'tipo'=>'Ig',
                    'folio'=>$nopoliza,
                    'fecha'=>$record->fecha,
                    'concepto'=>$dater[0],
                    'cargos'=>$pesos,
                    'abonos'=>$pesos,
                    'periodo'=>Filament::getTenant()->periodo,
                    'ejercicio'=>Filament::getTenant()->ejercicio,
                    'referencia'=>'Prestamo',
                    'uuid'=>'',
                    'tiposat'=>'Ig',
                    'team_id'=>Filament::getTenant()->id,
                    'idmovb'=>$record->id
                ]);
                $parno = 1;
                $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ban[0]->codigo,
                    'cuenta'=>$ban[0]->cuenta,
                    'concepto'=>$dater[0],
                    'cargo'=>$dolares,
                    'abono'=>0,
                    'factura'=>'Prestamo',
                    'nopartida'=>$parno,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $parno++;
                if($tc > 0){
                    $comple_c = CatCuentas::where('id',$ban[0]->complementaria)->first();
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>$comple_c->codigo,
                        'cuenta'=>$comple_c->nombre,
                        'concepto'=>$dater[0],
                        'cargo'=>$comple,
                        'abono'=>0,
                        'factura'=>'Prestamo',
                        'nopartida'=>$parno,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                    $parno++;
                }
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>$dater[1],
                        'cuenta'=>$dater[0],
                        'concepto'=>$dater[0],
                        'cargo'=>0,
                        'abono'=>$pesos,
                        'factura'=>'Prestamo',
                        'nopartida'=>$parno,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                $parno++;

                }
                if($data['Movimiento'] == 4)
                {
                    $poliza = CatPolizas::create([
                        'tipo'=>'Ig',
                        'folio'=>$nopoliza,
                        'fecha'=>$record->fecha,
                        'concepto'=>'Otros Ingresos',
                        'cargos'=>$record->importe,
                        'abonos'=>$record->importe,
                        'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,
                        'referencia'=>'Otros Ingresos',
                        'uuid'=>'',
                        'tiposat'=>'Ig',
                        'team_id'=>Filament::getTenant()->id,
                        'idmovb'=>$record->id
                    ]);
                    $polno = $poliza['id'];
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>'40301000',
                            'cuenta'=>'Otros Ingresos',
                            'concepto'=>'Otros Ingresos',
                            'cargo'=>0,
                            'abono'=>$record->importe,
                            'factura'=>'Otros Ingresos',
                            'nopartida'=>1,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$ban[0]->codigo,
                            'cuenta'=>$ban[0]->cuenta,
                            'concepto'=>'Otros Ingresos',
                            'cargo'=>$record->importe,
                            'abono'=>0,
                            'factura'=>'Otros Ingresos',
                            'nopartida'=>2,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                    }
                    if($data['Movimiento'] == 5)
                    {
                        $day = Carbon::now()->day;
                        $month = Filament::getTenant()->periodo;
                        $year = Filament::getTenant()->ejercicio;
                        $fecha = date('Y-m-d', strtotime("$year-$month-$day"));
                        $detalles = $data['detalle'];
                        $poliza = CatPolizas::create([
                            'tipo'=>$data['tipo'],
                            'folio'=>$data['folio'],
                            'fecha'=>$fecha,
                            'concepto'=>$data['concepto'],
                            'cargos'=>$record->importe,
                            'abonos'=>$record->importe,
                            'periodo'=>Filament::getTenant()->periodo,
                            'ejercicio'=>Filament::getTenant()->ejercicio,
                            'referencia'=>'F-'.$data['referencia'],
                            'tiposat'=>$data['tipo'],
                            'team_id'=>Filament::getTenant()->id,
                            'idmovb'=>$record->id
                        ]);
                        $polno = $poliza['id'];
                        $nopar =0;
                        foreach ($detalles as $detalle) {
                            $nopar++;
                            $aux = Auxiliares::create([
                                'cat_polizas_id' => $polno,
                                'codigo' => $detalle['codigo'],
                                'cuenta' => $detalle['cuenta'],
                                'concepto' => $detalle['concepto'],
                                'cargo' => $detalle['cargo'],
                                'abono' => $detalle['abono'],
                                'factura' => $detalle['factura'],
                                'nopartida' => $nopar,
                                'team_id' => Filament::getTenant()->id
                            ]);
                            DB::table('auxiliares_cat_polizas')->insert([
                                'auxiliares_id' => $aux['id'],
                                'cat_polizas_id' => $polno
                            ]);
                        }
                    }

        Notification::make('Concluido')
        ->title('Proceso Concluido. Poliza Ig'.$nopoliza.' Grabada')
        ->success()
        ->send();
    }

    public static function sumas_partidas_manual(Get $get,Set $set) :void
    {
        $detalle = $get('../../detalle');
        $cargos = 0;
        $abonos = 0;
        foreach ($detalle as $det) {
            $cargos += floatval($det['cargo'] ?? 0);
            $abonos += floatval($det['abono'] ?? 0);
        }
        $set('../../cargos_tot',number_format($cargos,2));
        $set('../../abonos_tot',number_format($abonos,2));
    }
    public static function procesa_s_f($record,$data)
    {
        //dd($data);
        $facts =$data['Facturas'] ?? [['Emisor'=>'','Factura'=>'','UUID'=>'','FacId'=>0]];
        $tmov = $data['Movimiento'];
        DB::table('movbancos')->where('id',$record->id)->update([
            'tercero'=>$facts[0]['Emisor'],
            'factura'=>$facts[0]['desfactura'] ?? 'N/A',
            'uuid'=>$facts[0]['UUID'],
            'contabilizada'=>'SI'
        ]);
        $fss = DB::table('almacencfdis')->where('id',$facts[0]['FacId'])->get();
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->get();
        $ter = DB::table('terceros')->where('rfc',$facts[0]['Emisor'])->get();
        $nom = $fss[0]->Emisor_Nombre ?? '';
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Eg')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        if($tmov < 4) {
            $ingegid = $facts[0]['ingengid'] ?? 0;
            $pags = 0;
            if (count(DB::table('ingresos_egresos')->where('id', $ingegid)->get()) > 0) $pags = DB::table('ingresos_egresos')->where('id', $ingegid)->get()[0];
            $tc_n = DB::table('historico_tcs')->latest('id')->first()->tipo_cambio;
            $npagusd = $pags->pagadousd + $record->importe / $tc_n;
            $npendusd = $pags->pendienteusd - ($record->importe / $tc_n);
            $npag = $pags->pagadomxn + $record->importe;
            $npend = $pags->pendientemxn - $record->importe;
            if ($fss[0]->Moneda == 'USD') {
                $npag = $pags->pagadomxn + ($record->importe);
                $npend = $pags->pendientemxn - ($record->importe);
            } else {
                $tc_n = 1;
            }
            if ($npend < 0) $npend = 0;
            if ($npendusd < 0) $npendusd = 0;
            DB::table('ingresos_egresos')->where('id', $ingegid)
                ->update([
                    'pagadomxn' => $npag,
                    'pendientemxn' => $npend,
                    'pagadousd' => $npagusd,
                    'pendienteusd' => $npendusd,
                ]);
        }
        //-------------------------------------------------------------------
        if($tmov == 1)
        {
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>$nom,
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>$facts[0]['desfactura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
            $par_num = 1;
            if($fss[0]->Moneda == 'USD'){
                $impor_usd = $fss[0]->Total;
                $impor_usd_mxn = $fss[0]->Total * $fss[0]->TipoCambio;
                $impor_complem = $impor_usd_mxn - $impor_usd;
                $iva_por_pag = ($impor_usd_mxn / 1.16) * 0.16;
                $impor_mxn = $record->importe;
                $iva_pend = ($record->importe /1.16) * 0.16;
                $diferencia_cam = ($impor_usd + $impor_complem + $iva_pend) - ($impor_mxn + $iva_por_pag);

                $mon_usd = $record->importe / $tc_n;
                $mon_com = $record->importe - $mon_usd;
                $conce = $ter[0]->nombre;

                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ter[0]->cuenta,
                    'cuenta'=>$ter[0]->nombre,
                    'concepto'=>$conce.' USD',
                    'cargo'=>$impor_usd,
                    'abono'=>0,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $par_num++;
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ter[0]->cuenta,
                    'cuenta'=>$ter[0]->nombre,
                    'concepto'=>$conce.' Complementaria',
                    'cargo'=>$impor_complem,
                    'abono'=>0,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $par_num++;
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'11801000',
                    'cuenta'=>'IVA acreditable pagado',
                    'concepto'=>$conce,
                    'cargo'=>$iva_pend,
                    'abono'=>0,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $par_num++;
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'11901000',
                    'cuenta'=>'IVA pendiente de pago',
                    'concepto'=>$conce,
                    'cargo'=>0,
                    'abono'=>$iva_por_pag,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $par_num++;
                if($diferencia_cam > 0) {
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '70101000',
                        'cuenta' => 'Perdida Cambiaria',
                        'concepto' => $nom,
                        'cargo' => $diferencia_cam,
                        'abono' => 0,
                        'factura' => $facts[0]['desfactura'],
                        'nopartida' => $par_num,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $par_num++;

                }
                else{
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '70201000',
                        'cuenta' => 'Utilidad Cambiaria',
                        'concepto' => $nom,
                        'cargo' => 0,
                        'abono' => $diferencia_cam * -1,
                        'factura' => $facts[0]['desfactura'],
                        'nopartida' => $par_num,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                }
            }
            else{
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ter[0]->cuenta,
                    'cuenta'=>$ter[0]->nombre,
                    'concepto'=>$nom,
                    'cargo'=>$record->importe * $tc_n,
                    'abono'=>0,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $par_num++;
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'11801000',
                    'cuenta'=>'IVA acreditable pagado',
                    'concepto'=>$nom,
                    'cargo'=>(($record->importe) /1.16) * 0.16,
                    'abono'=>0,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $par_num++;
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'11901000',
                    'cuenta'=>'IVA pendiente de pago',
                    'concepto'=>$nom,
                    'cargo'=>0,
                    'abono'=>(($record->importe) /1.16) * 0.16,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $par_num++;
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ban[0]->codigo,
                    'cuenta'=>$ban[0]->cuenta,
                    'concepto'=>$nom,
                    'cargo'=>0,
                    'abono'=>$record->importe,
                    'factura'=>$facts[0]['desfactura'],
                    'nopartida'=>$par_num,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
            }

        }
        if($tmov == 2)
        {
            $dater = explode('|',$data['Tercero']);
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>'Reembolso de gastos',
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>$facts[0]['desfactura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$dater[0],
                'cuenta'=>$dater[1],
                'concepto'=>'Reembolso de gastos',
                'cargo'=>$record->importe,
                'abono'=>0,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>1,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'11801000',
                'cuenta'=>'IVA acreditable pagado',
                'concepto'=>'Reembolso de gastos',
                'cargo'=>($record->importe /1.16) * 0.16,
                'abono'=>0,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'11901000',
                'cuenta'=>'IVA pendiente de pago',
                'concepto'=>'Reembolso de gastos',
                'cargo'=>0,
                'abono'=>($record->importe /1.16) * 0.16,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>3,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ban[0]->codigo,
                'cuenta'=>$ban[0]->cuenta,
                'concepto'=>'Reembolso de gastos',
                'cargo'=>0,
                'abono'=>$record->importe,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>4,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
        }
        if($tmov == 3)
        {
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>'Pago Activo Fijo',
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>$facts[0]['desfactura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ter[0]->cuenta,
                'cuenta'=>$ter[0]->nombre,
                'concepto'=>'Pago Activo Fijo',
                'cargo'=>$record->importe,
                'abono'=>0,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>1,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'11801000',
                'cuenta'=>'IVA acreditable pagado',
                'concepto'=>'Pago Activo Fijo',
                'cargo'=>($record->importe /1.16) * 0.16,
                'abono'=>0,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'11901000',
                'cuenta'=>'IVA pendiente de pago',
                'concepto'=>'Pago Activo Fijo',
                'cargo'=>0,
                'abono'=>($record->importe /1.16) * 0.16,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>3,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ban[0]->codigo,
                'cuenta'=>$ban[0]->cuenta,
                'concepto'=>'Pago Activo Fijo',
                'cargo'=>0,
                'abono'=>$record->importe,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>4,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
        }
        if($tmov == 4)
        {
            $dater = explode('|',$data['Tercero']);
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>$dater[0].'ACREEDOR',
                'cargos'=>$record->importe*$record->tcambio,
                'abonos'=>$record->importe*$record->tcambio,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>'Prestamo',
                'uuid'=>'',
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$dater[1],
                    'cuenta'=>$dater[0],
                    'concepto'=>$dater[0].'ACREEDOR',
                    'cargo'=>$record->importe*$record->tcambio,
                    'abono'=>0,
                    'factura'=>'Prestamo',
                    'nopartida'=>1,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ban[0]->codigo,
                    'cuenta'=>$ban[0]->cuenta,
                    'concepto'=>$dater[0].'ACREEDOR',
                    'cargo'=>0,
                    'abono'=>$record->importe,
                    'factura'=>'Prestamo',
                    'nopartida'=>2,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                if($record->tcambio > 1)
                {
                    $cta_com = CatCuentas::where('codigo',$ban[0]->complementaria)->first();
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>$cta_com?->codigo ?? $ban[0]->codigo,
                        'cuenta'=>$cta_com?->nombre ?? $ban[0]->cuenta,
                        'concepto'=>$dater[0].'ACREEDOR',
                        'cargo'=>0,
                        'abono'=>($record->importe*$record->tcambio)-$record->importe,
                        'factura'=>'Prestamo',
                        'nopartida'=>3,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id'=>$aux['id'],
                        'cat_polizas_id'=>$polno
                    ]);
                }
        }
        if($tmov == 5)
        {
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>'Gasto no Deducible',
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>'Gasto no Deducible',
                'uuid'=>'',
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>'50125000',
                    'cuenta'=>'Gasto no Deducible',
                    'concepto'=>'Gasto no Deducible',
                    'cargo'=>$record->importe,
                    'abono'=>0,
                    'factura'=>'Gasto no Deducible',
                    'nopartida'=>1,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ban[0]->codigo,
                    'cuenta'=>$ban[0]->cuenta,
                    'concepto'=>'Gasto no Deducible',
                    'cargo'=>0,
                    'abono'=>$record->importe,
                    'factura'=>'Gasto no Deducible',
                    'nopartida'=>2,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
        }
        if($tmov == 6)
        {
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>'Pago de Nomina',
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>'Pago de Nomina',
                'uuid'=>'',
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['nom_reggasto_cta'],
                'cuenta'=>'Registro del Gasto',
                'concepto'=>'Registro del Gasto',
                'cargo'=>$data['nom_reggasto'],
                'abono'=>0,
                'factura'=>'Pago de Nomina',
                'nopartida'=>1,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['nom_retisr_cta'],
                'cuenta'=>'Retencion de ISR',
                'concepto'=>'Retencion de ISR',
                'cargo'=>$data['nom_retisr'],
                'abono'=>0,
                'factura'=>'Pago de Nomina',
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['nom_retimss_cta'],
                'cuenta'=>'Retencion IMSS',
                'concepto'=>'Retencion IMSS',
                'cargo'=>$data['nom_retimss'],
                'abono'=>0,
                'factura'=>'Pago de Nomina',
                'nopartida'=>3,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['nom_infonavit_cta'],
                'cuenta'=>'Credito Infonavit',
                'concepto'=>'Credito Infonavit',
                'cargo'=>$data['nom_infonavit'],
                'abono'=>0,
                'factura'=>'Pago de Nomina',
                'nopartida'=>4,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['nom_presempre_cta'],
                'cuenta'=>'Prestamo Empresa',
                'concepto'=>'Prestamo Empresa',
                'cargo'=>$data['nom_presempre'],
                'abono'=>0,
                'factura'=>'Pago de Nomina',
                'nopartida'=>5,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['nom_banco_cta'],
                'cuenta'=>$ban[0]->cuenta,
                'concepto'=>'Pago de Nomina',
                'cargo'=>0,
                'abono'=>$record->importe,
                'factura'=>'Pago de Nomina',
                'nopartida'=>6,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
        }
        if($tmov == 7)
        {
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>'Anticipo gastos adunales',
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>'Anticipo gastos adunales',
                'uuid'=>'',
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['aduana_dta_cta'],
                'cuenta'=>'DTA',
                'concepto'=>'DTA',
                'cargo'=>$data['aduana_dta'],
                'abono'=>0,
                'factura'=>'Anticipo gastos adunales',
                'nopartida'=>1,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['aduana_ivaprv_cta'],
                'cuenta'=>'IVA/PRV',
                'concepto'=>'IVA/PRV',
                'cargo'=>$data['aduana_ivaprv'],
                'abono'=>0,
                'factura'=>'Anticipo gastos adunales',
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['aduana_igi_cta'],
                'cuenta'=>'IGI/EGE',
                'concepto'=>'IGI/EGE',
                'cargo'=>$data['aduana_igi'],
                'abono'=>0,
                'factura'=>'Anticipo gastos adunales',
                'nopartida'=>3,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['aduana_prv_cta'],
                'cuenta'=>'PRV',
                'concepto'=>'PRV',
                'cargo'=>$data['aduana_prv'],
                'abono'=>0,
                'factura'=>'Anticipo gastos adunales',
                'nopartida'=>4,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['aduana_iva_cta'],
                'cuenta'=>'IVA',
                'concepto'=>'IVA',
                'cargo'=>$data['aduana_iva'],
                'abono'=>0,
                'factura'=>'Anticipo gastos adunales',
                'nopartida'=>5,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['aduana_pagos_cta'],
                'cuenta'=>'Pagos en el Extranjero',
                'concepto'=>'Pagos en el Extranjero',
                'cargo'=>$data['aduana_pagos'],
                'abono'=>0,
                'factura'=>'Anticipo gastos adunales',
                'nopartida'=>6,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$data['nom_banco_cta'],
                'cuenta'=>$ban[0]->cuenta,
                'concepto'=>'Anticipo gastos adunales',
                'cargo'=>0,
                'abono'=>$record->importe,
                'factura'=>'Anticipo gastos adunales',
                'nopartida'=>7,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
        }

        if($tmov == 8)
        {
            $day = Carbon::now()->day;
            $month = Filament::getTenant()->periodo;
            $year = Filament::getTenant()->ejercicio;
            $fecha = date('Y-m-d', strtotime("$year-$month-$day"));
            $detalles = $data['detalle'];
            $poliza = CatPolizas::create([
                'tipo'=>$data['tipo'],
                'folio'=>$data['folio'],
                'fecha'=>$fecha,
                'concepto'=>$data['concepto'],
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
                'periodo'=>Filament::getTenant()->periodo,
                'ejercicio'=>Filament::getTenant()->ejercicio,
                'referencia'=>'F-'.$data['referencia'],
                'tiposat'=>$data['tipo'],
                'team_id'=>Filament::getTenant()->id,
                'idmovb'=>$record->id
            ]);
            $polno = $poliza['id'];
            $nopar =0;
            foreach ($detalles as $detalle) {
                $nopar++;
                $aux = Auxiliares::create([
                    'cat_polizas_id' => $polno,
                    'codigo' => $detalle['codigo'],
                    'cuenta' => $detalle['cuenta'],
                    'concepto' => $detalle['concepto'],
                    'cargo' => $detalle['cargo'],
                    'abono' => $detalle['abono'],
                    'factura' => $detalle['factura'],
                    'nopartida' => $nopar,
                    'team_id' => Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id' => $aux['id'],
                    'cat_polizas_id' => $polno
                ]);
            }
        }

        Notification::make('Concluido')
        ->title('Proceso Concluido. Poliza Eg'.$nopoliza.' Grabada')
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
            'pagos' => Pages\Pagos::route('/pagos/{record}'),
            'cobros' => Pages\Cobros::route('/cobros/{record}')
        ];
    }
}

