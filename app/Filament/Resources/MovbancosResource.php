<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovbancosResource\Pages;
use App\Filament\Resources\MovbancosResource\RelationManagers;
use App\Livewire\IngEgWidget;
use App\Models\Activosfijos;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\IngresosEgresos;
use App\Models\Movbancos;
use App\Models\Regimenes;
use App\Models\Terceros;
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
use Filament\Support\Colors\Color;
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
use phpDocumentor\Reflection\Types\Parent_;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum as MathTrigSum;

class MovbancosResource extends Resource
{
    protected static ?string $model = Movbancos::class;
    protected static ?string $navigationGroup = 'Bancos';
    protected static ?string $label = 'Movimiento Bancario';
    protected static ?string $pluralLabel = 'Movimientos Bancarios';
    protected static ?string $navigationIcon ='fas-money-bill-transfer';
    public ?float $saldo_cuenta = 0;
    public ?float $saldo_cuenta_act = 0;
    public static ?array $selected_records = [];


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
                                        ->numeric()->prefix('$'),
                                Forms\Components\Select::make('moneda')
                                ->options(['MXN'=>'MXN','USD'=>'USD'])
                                ->default('MXN'),
                                Forms\Components\TextInput::make('concepto')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(3),
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
            ->heading(function(Table $table,$livewire){
                $record = $table->getRecords();
                $text1 = DB::table('banco_cuentas')->where('team_id',Filament::getTenant()->id)->get();
                $text2 = count($text1);
                $text3 = 1;
                if($text2 > 0) $text3 = $text1[0]->cuenta;
                    $q_cuenta = $record[0]->cuenta ?? $text3;
                    $q_periodo = Filament::getTenant()->periodo ?? 1;
                    $q_ejercicio = Filament::getTenant()->ejercicio ?? 2020;
                    $sdos_ac = DB::select("SELECT cuenta,
                    (SUM(inicial) + (SELECT SUM(importe) FROM movbancos WHERE periodo < $q_periodo AND ejercicio = $q_ejercicio AND tipo = 'E' and cuenta = $q_cuenta) - (SELECT SUM(importe) FROM movbancos WHERE periodo < $q_periodo AND ejercicio = $q_ejercicio AND tipo = 'S' and cuenta = 2)) saldo
                    FROM  saldosbancos WHERE cuenta = $q_cuenta GROUP BY cuenta");
                        $valo = floatval($sdos_ac[0]->saldo ?? 0);
                        $valor ='$ '. number_format($valo,2).' MXN';
                    $livewire->saldo_cuenta = floatval($sdos_ac[0]->saldo ?? 0);
                    $livewire->saldo_cuenta_act = floatval($sdos_ac[0]->saldo ?? 0);
                return 'Saldo Inicial del Periodo: '.$valor;
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
            //--------------------------------------------------------
                    Action::make('procesa_s')
                        ->visible(function($record){
                            if($record->contabilizada == 'SI') return false;
                            if($record->contabilizada == 'NO'&&$record->tipo == 'S') return true;
                        })
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
                                    '1'=>'Pago de Factura',
                                    '2'=>'Reembolso de Gastos',
                                    '3'=>'Compra de Activo',
                                    '4'=>'Prestamo',
                                    '5'=>'Gasto no Deducible',
                                    '6'=>'Pago de Nomina',
                                    '7'=>'Anticipo agencia aduanal'
                                ])->columnSpan(2),
                                TableRepeater::make('Facturas')
                                ->visible(function(Get $get){
                                    $mov = $get('Movimiento');
                                    if($mov == 1||$mov == 2||$mov == 3) return true;
                                    else return false;
                                })->headers([
                                    Header::make('Factura')->width('300px'),
                                    Header::make('Emisor')->width('100px'),
                                    Header::make('Receptor')->width('100px'),
                                    Header::make('Importe')->width('100px')
                                ])
                                ->schema([
                                    Select::make('Factura')
                                    ->searchable()
                                    ->options(function (){
                                        $ing_ret = IngresosEgresos::where('team_id',Filament::getTenant()->id)->where('tipo',0)->where('pendientemxn','>',0)->get();
                                        $data = [];
                                        foreach ($ing_ret as $item){
                                            $tot = '$'.number_format($item->totalmxn,2);
                                            $pend = '$'.number_format($item->pendientemxn,2);
                                            $monea = 'MXN';
                                            $alm = Almacencfdis::where('id',$item->xml_id)->first();
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
                                                <td class='border' style='padding: 10px'>$alm->Emisor_Nombre</td>
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
                                    Hidden::make('ingengid')
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
                                    ->options(Terceros::where('tipo','Proveedor')->select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
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
                                    ->createOptionUsing(function(array $data){
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
                                    ->options(Terceros::where('tipo','Acreedor')->select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
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
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('nom_reggasto')->numeric()->prefix('$')->hiddenLabel()->default(fn(Get $get)=>$get('importe')),
                                Select::make('nom_retisr_cta')->label('Retencion de ISR')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('nom_retisr')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_retimss_cta')->label('Retencion IMSS')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('nom_retimss')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_infonavit_cta')->label('Credito Infonavit')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('nom_infonavit')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_presempre_cta')->label('Prestamo Empresa')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable(),
                                TextInput::make('nom_presempre')->numeric()->prefix('$')->hiddenLabel()->default(0),
                                Select::make('nom_banco_cta')->label('Cuenta Bancaria')->inlineLabel()->columnSpan(2)
                                    ->options(DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo'))->searchable()
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
                            ])->columnSpan(3)->columns(3)
                        ])->columns(4);
                    })
                    ->modalWidth('7xl')
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
                                    '1'=>'Cobro de Factura',
                                    '2'=>'Cobro no identificado',
                                    '3'=>'Prestamo',
                                    '4'=>'Otros Ingresos'
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
                                            $ing_ret = IngresosEgresos::where('team_id',Filament::getTenant()->id)->where('tipo',1)->where('pendientemxn','>',0)->get();
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
                                    ->options(Terceros::where('tipo','Deudor')->select('nombre',DB::raw("concat(nombre,'|',cuenta) as cuenta"))->pluck('nombre','cuenta'))
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
                                ])
                        ])->columns(4);
                    })
                    ->modalWidth('7xl')
                    ->label('Procesar')
                    ->accessSelectedRecords()
                    ->modalSubmitActionLabel('Grabar')
                    ->icon('fas-check-to-slot')
                    ->action(function (Model $record,$data,Get $get, Set $set) {
                        Self::procesa_e_f($record,$data,$get,$set);
                    })
                ])->color('primary')
            ])->actionsPosition(ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped()
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
        $facts[0]['ingengid'];
        $pags = DB::table('ingresos_egresos')->where('id',$facts[0]['ingengid'])->get()[0];
        $tc_n = DB::table('historico_tcs')->latest('id')->first()->tipo_cambio;
        $npagusd = $pags->pagadousd + $record->importe;
        $npendusd = $pags->pendienteusd - $record->importe;
        $npag = $pags->pagadomxn + $record->importe;
        $npend = $pags->pendientemxn - $record->importe;
        if($fss[0]->Moneda == 'USD') {
            $npag = $pags->pagadomxn + ($record->importe * $tc_n);
            $npend = $pags->pendientemxn - ($record->importe * $tc_n);
        }
        else{
            $tc_n = 1;
        }
        if($npend < 0) $npend = 0;
        if($npendusd < 0) $npendusd = 0;
        DB::table('ingresos_egresos')->where('id',$facts[0]['ingengid'])
            ->update([
                'pagadomxn' => $npag,
                'pendientemxn' => $npend,
                'pagadousd' => $npagusd,
                'pendienteusd' => $npendusd,
            ]);
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
                $poliza = CatPolizas::create([
                    'tipo'=>'Ig',
                    'folio'=>$nopoliza,
                    'fecha'=>$record->fecha,
                    'concepto'=>'Prestamo',
                    'cargos'=>$record->importe,
                    'abonos'=>$record->importe,
                    'periodo'=>Filament::getTenant()->periodo,
                    'ejercicio'=>Filament::getTenant()->ejercicio,
                    'referencia'=>'Prestamo',
                    'uuid'=>'',
                    'tiposat'=>'Ig',
                    'team_id'=>Filament::getTenant()->id,
                    'idmovb'=>$record->id
                ]);
                $polno = $poliza['id'];
                    $aux = Auxiliares::create([
                        'cat_polizas_id'=>$polno,
                        'codigo'=>$dater[1],
                        'cuenta'=>$dater[0],
                        'concepto'=>'Prestamo',
                        'cargo'=>$record->importe,
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
                        'concepto'=>'Prestamo',
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
                            'cargo'=>0,
                            'abono'=>$record->importe,
                            'factura'=>'Otros Ingresos',
                            'nopartida'=>2,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                    }

        Notification::make('Concluido')
        ->title('Proceso Concluido. Poliza Ig'.$nopoliza.' Grabada')
        ->success()
        ->send();
    }

    public static function procesa_s_f($record,$data)
    {
        //dd($data);
        $facts =$data['Facturas'] ?? [['Emisor'=>'','Factura'=>'','UUID'=>'','FacId'=>0]];
        $tmov = $data['Movimiento'];
        DB::table('movbancos')->where('id',$record->id)->update([
            'tercero'=>$facts[0]['Emisor'],
            'factura'=>$facts[0]['desfactura'],
            'uuid'=>$facts[0]['UUID'],
            'contabilizada'=>'SI'
        ]);
        $fss = DB::table('almacencfdis')->where('id',$facts[0]['FacId'])->get();
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->get();
        $ter = DB::table('terceros')->where('rfc',$facts[0]['Emisor'])->get();
        $nom = $fss[0]->Emisor_Nombre ?? '';
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Eg')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
        $facts[0]['ingengid'];
        $pags = DB::table('ingresos_egresos')->where('id',$facts[0]['ingengid'])->get()[0];
        $tc_n = DB::table('historico_tcs')->latest('id')->first()->tipo_cambio;
        $npagusd = $pags->pagadousd + $record->importe;
        $npendusd = $pags->pendienteusd - $record->importe;
        $npag = $pags->pagadomxn + $record->importe;
        $npend = $pags->pendientemxn - $record->importe;
        if($fss[0]->Moneda == 'USD') {
            $npag = $pags->pagadomxn + ($record->importe * $tc_n);
            $npend = $pags->pendientemxn - ($record->importe * $tc_n);
        }
        else{
            $tc_n = 1;
        }
        if($npend < 0) $npend = 0;
        if($npendusd < 0) $npendusd = 0;
        DB::table('ingresos_egresos')->where('id',$facts[0]['ingengid'])
        ->update([
            'pagadomxn' => $npag,
            'pendientemxn' => $npend,
            'pagadousd' => $npagusd,
            'pendienteusd' => $npendusd,
        ]);
        //-------------------------------------------------------------------
        if($tmov == 1)
        {
            $poliza = CatPolizas::create([
                'tipo'=>'Eg',
                'folio'=>$nopoliza,
                'fecha'=>$record->fecha,
                'concepto'=>$nom,
                'cargos'=>$record->importe * $tc_n,
                'abonos'=>$record->importe * $tc_n,
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
                'concepto'=>$nom,
                'cargo'=>$record->importe * $tc_n,
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
                'concepto'=>$nom,
                'cargo'=>(($record->importe * $tc_n) /1.16) * 0.16,
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
                'concepto'=>$nom,
                'cargo'=>0,
                'abono'=>(($record->importe* $tc_n) /1.16) * 0.16,
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
                'cargo'=>0,
                'abono'=>$record->importe* $tc_n,
                'factura'=>$facts[0]['desfactura'],
                'nopartida'=>4,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            if($record->moneda=='USD'){
                $impnue = $record->importe * $tc_n;
                $imp_ant = $record->importe * $fss[0]->TipoCambio;
                $difer = $impnue - $imp_ant;
                if($difer > 0) {
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '70101000',
                        'cuenta' => 'Perdida Cambiaria',
                        'concepto' => $nom,
                        'cargo' => $difer,
                        'abono' => 0,
                        'factura' => $facts[0]['desfactura'],
                        'nopartida' => 5,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo'=>$ter[0]->cuenta,
                        'cuenta'=>$ter[0]->nombre,
                        'concepto' => $nom,
                        'cargo' => 0,
                        'abono' => $difer,
                        'factura' => $facts[0]['desfactura'],
                        'nopartida' => 6,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                }
                else{
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo' => '70201000',
                        'cuenta' => 'Utilidad Cambiaria',
                        'concepto' => $nom,
                        'cargo' => 0,
                        'abono' => $difer,
                        'factura' => $facts[0]['desfactura'],
                        'nopartida' => 5,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                    $aux = Auxiliares::create([
                        'cat_polizas_id' => $polno,
                        'codigo'=>$ter[0]->cuenta,
                        'cuenta'=>$ter[0]->nombre,
                        'concepto' => $nom,
                        'cargo' => $difer,
                        'abono' => 0,
                        'factura' => $facts[0]['desfactura'],
                        'nopartida' => 6,
                        'team_id' => Filament::getTenant()->id
                    ]);
                    DB::table('auxiliares_cat_polizas')->insert([
                        'auxiliares_id' => $aux['id'],
                        'cat_polizas_id' => $polno
                    ]);
                }
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
                'concepto'=>'Prestamo',
                'cargos'=>$record->importe,
                'abonos'=>$record->importe,
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
                    'concepto'=>'Prestamo',
                    'cargo'=>0,
                    'abono'=>$record->importe,
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
                    'concepto'=>'Prestamo',
                    'cargo'=>$record->importe,
                    'abono'=>0,
                    'factura'=>'Prestamo',
                    'nopartida'=>2,
                    'team_id'=>Filament::getTenant()->id
                ]);
                DB::table('auxiliares_cat_polizas')->insert([
                    'auxiliares_id'=>$aux['id'],
                    'cat_polizas_id'=>$polno
                ]);
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
                    'codigo'=>'40301000',
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
                    'cargo'=>$record->importe,
                    'abono'=>0,
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
        Notification::make('Concluido')
        ->title('Proceso Concluido. Poliza Eg'.$nopoliza.' Grabada')
        ->success()
        ->send();
    }


    public static function setFactData(){

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

