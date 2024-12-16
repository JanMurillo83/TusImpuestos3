<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovbancosResource\Pages;
use App\Filament\Resources\MovbancosResource\RelationManagers;
use App\Models\Activosfijos;
use App\Models\Almacencfdis;
use App\Models\Auxiliares;
use App\Models\BancoCuentas;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
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
            //--------------------------------------------------------
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
                                    '5'=>'Gasto no Deducible'
                                ])->columnSpan(2),
                                TableRepeater::make('Facturas')
                                ->visible(function(Get $get){
                                    $mov = $get('Movimiento');
                                    if($mov == 1||$mov == 2||$mov == 3) return true;
                                    else return false;
                                })->headers([
                                    Header::make('Factura')->width('100px'),
                                    Header::make('Emisor')->width('100px'),
                                    Header::make('Receptor')->width('100px'),
                                    Header::make('Importe')->width('100px')
                                ])
                                ->schema([
                                    Select::make('Factura')
                                    ->options(
                                        function(){
                                            $cfdis = DB::table('almacencfdis')->where(['team_id'=>Filament::getTenant()->id,'xml_type'=>'Recibidos','used'=>'SI'])
                                            ->select('id',DB::Raw("concat('Factura: ',serie,folio,'  Fecha: ',
                                            DATE_FORMAT(fecha,'%d-%m-%Y'),'  Emisor: ',Emisor_Nombre,
                                            '  Importe: $',FORMAT(Total,2)) CFDI"))->get();
                                            $resultado =[];
                                            foreach($cfdis as $cfdi)
                                            {
                                                array_push($resultado,[$cfdi->id=>$cfdi->CFDI]);
                                            }
                                            return $resultado;
                                        })
                                    ->afterStateUpdated(function(Get $get,Set $set){
                                        $factura = $get('Factura');
                                        $facts = DB::table('almacencfdis')->where('id',$factura)->get();
                                        $fac = $facts[0];
                                        $set('Emisor',$fac->Emisor_Rfc);
                                        $set('Receptor',$fac->Receptor_Rfc);
                                        $set('Importe',$fac->Total);
                                        $set('FacId',$fac->id);
                                    })->live(onBlur:true),
                                    TextInput::make('Emisor')->readOnly(),
                                    TextInput::make('Receptor')->readOnly(),
                                    TextInput::make('Importe')->readOnly()
                                    ->numeric()->prefix('$'),
                                    Hidden::make('FacId'),
                                    Hidden::make('UUID')
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
                                ])
                        ])->columns(4);
                    })
                    ->modalWidth('7xl')
                    ->visible(fn ($record) => $record->tipo == 'S')
                    ->label('Procesar')
                    ->accessSelectedRecords()
                    ->icon('fas-check-to-slot')
                    ->action(function (Model $record,$data,Get $get, Set $set) {
                        Self::procesa_s_f($record,$data,$get,$set);
                    }),
                //--------------------------------------------------------------------
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
                                    Header::make('Factura')->width('100px'),
                                    Header::make('Emisor')->width('100px'),
                                    Header::make('Receptor')->width('100px'),
                                    Header::make('Importe')->width('100px')
                                ])
                                ->schema([
                                    Select::make('Factura')
                                    ->required(function(Get $get){
                                        if($get('Movimiento') == 1) return true;
                                        else return false;
                                    })
                                    ->options(
                                        function(){
                                            $cfdis = DB::table('almacencfdis')->where(['team_id'=>Filament::getTenant()->id,'xml_type'=>'Emitidos','used'=>'SI'])
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
                                    ->afterStateUpdated(function(Get $get,Set $set){
                                        $factura = $get('Factura');
                                        $facts = DB::table('almacencfdis')->where('id',$factura)->get();
                                        $fac = $facts[0];
                                        $set('Emisor',$fac->Emisor_Rfc);
                                        $set('Receptor',$fac->Receptor_Rfc);
                                        $set('Importe',$fac->Total);
                                        $set('FacId',$fac->id);
                                    })->live(onBlur:true),
                                    TextInput::make('Emisor')->readOnly(),
                                    TextInput::make('Receptor')->readOnly(),
                                    TextInput::make('Importe')->readOnly()
                                    ->numeric()->prefix('$'),
                                    Hidden::make('FacId'),
                                    Hidden::make('UUID')
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
                    ->visible(fn ($record) => $record->tipo == 'E')
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
        //dd($data);
        $facts =$data['Facturas'] ?? 0;
        //dd($facts[0]);
        DB::table('movbancos')->where('id',$record->id)->update([
            'tercero'=>$facts[0]['Receptor'] ?? 'N/A',
            'factura'=>$facts[0]['Factura'] ?? 'N/A',
            'uuid'=>$facts[0]['UUID'] ?? 'N/A',
            'contabilizada'=>'SI'
        ]);
        if($data['Movimiento'] == 1) $fss = DB::table('almacencfdis')->where('id',$facts[0]['FacId'])->get();
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->get();
        if($data['Movimiento'] == 1) $ter = DB::table('terceros')->where('rfc',$facts[0]['Receptor'])->get();
        if($data['Movimiento'] == 1) $nom = $fss[0]->Receptor_Nombre;
        //-------------------------------------------------------------------
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Ig')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
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
                'referencia'=>$facts[0]['Factura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Ig',
                'team_id'=>Filament::getTenant()->id
            ]);
            $polno = $poliza['id'];
                $aux = Auxiliares::create([
                    'cat_polizas_id'=>$polno,
                    'codigo'=>$ter[0]->cuenta,
                    'cuenta'=>$ter[0]->nombre,
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
                    'codigo'=>'20801000',
                    'cuenta'=>'IVA trasladado cobrado',
                    'concepto'=>$nom,
                    'cargo'=>0,
                    'abono'=>($record->importe /1.16) * 0.16,
                    'factura'=>$facts[0]['Factura'],
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
                    'factura'=>$facts[0]['Factura'],
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
                    'factura'=>$facts[0]['Factura'],
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
                    'team_id'=>Filament::getTenant()->id
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
                    'team_id'=>Filament::getTenant()->id
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
                        'team_id'=>Filament::getTenant()->id
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
        $facts =$data['Facturas'];
        $tmov = $data['Movimiento'];
        DB::table('movbancos')->where('id',$record->id)->update([
            'tercero'=>$facts[0]['Emisor'],
            'factura'=>$facts[0]['Factura'],
            'uuid'=>$facts[0]['UUID'],
            'contabilizada'=>'SI'
        ]);
        $fss = DB::table('almacencfdis')->where('id',$facts[0]['FacId'])->get();
        $ban = DB::table('banco_cuentas')->where('id',$record->cuenta)->get();
        $ter = DB::table('terceros')->where('rfc',$facts[0]['Emisor'])->get();
        $nom = $fss[0]->Emisor_Nombre;
        $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Eg')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
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
                'referencia'=>$facts[0]['Factura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ter[0]->cuenta,
                'cuenta'=>$ter[0]->nombre,
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
                'codigo'=>'10801000',
                'cuenta'=>'IVA acreditable pagado',
                'concepto'=>$nom,
                'cargo'=>($record->importe /1.16) * 0.16,
                'abono'=>0,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'10901000',
                'cuenta'=>'IVA pendiente de pago',
                'concepto'=>$nom,
                'cargo'=>0,
                'abono'=>($record->importe /1.16) * 0.16,
                'factura'=>$facts[0]['Factura'],
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
                'abono'=>$record->importe,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>4,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);

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
                'referencia'=>$facts[0]['Factura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$dater[0],
                'cuenta'=>$dater[1],
                'concepto'=>'Reembolso de gastos',
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
                'codigo'=>'10801000',
                'cuenta'=>'IVA acreditable pagado',
                'concepto'=>'Reembolso de gastos',
                'cargo'=>($record->importe /1.16) * 0.16,
                'abono'=>0,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'10901000',
                'cuenta'=>'IVA pendiente de pago',
                'concepto'=>'Reembolso de gastos',
                'cargo'=>0,
                'abono'=>($record->importe /1.16) * 0.16,
                'factura'=>$facts[0]['Factura'],
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
                'factura'=>$facts[0]['Factura'],
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
                'referencia'=>$facts[0]['Factura'],
                'uuid'=>$facts[0]['UUID'],
                'tiposat'=>'Eg',
                'team_id'=>Filament::getTenant()->id
            ]);
            $polno = $poliza['id'];
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>$ter[0]->cuenta,
                'cuenta'=>$ter[0]->nombre,
                'concepto'=>'Pago Activo Fijo',
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
                'codigo'=>'10801000',
                'cuenta'=>'IVA acreditable pagado',
                'concepto'=>'Pago Activo Fijo',
                'cargo'=>($record->importe /1.16) * 0.16,
                'abono'=>0,
                'factura'=>$facts[0]['Factura'],
                'nopartida'=>2,
                'team_id'=>Filament::getTenant()->id
            ]);
            DB::table('auxiliares_cat_polizas')->insert([
                'auxiliares_id'=>$aux['id'],
                'cat_polizas_id'=>$polno
            ]);
            $aux = Auxiliares::create([
                'cat_polizas_id'=>$polno,
                'codigo'=>'10901000',
                'cuenta'=>'IVA pendiente de pago',
                'concepto'=>'Pago Activo Fijo',
                'cargo'=>0,
                'abono'=>($record->importe /1.16) * 0.16,
                'factura'=>$facts[0]['Factura'],
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
                'factura'=>$facts[0]['Factura'],
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
                'team_id'=>Filament::getTenant()->id
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
                'team_id'=>Filament::getTenant()->id
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
        ];
    }
}

