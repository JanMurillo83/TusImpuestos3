<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatPolizasResource\Pages;
use App\Filament\Resources\CatPolizasResource\RelationManagers;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\ContaPeriodos;
use App\Models\Movbancos;
use App\Models\RegTraspasos;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Carbon\Carbon;
use DateTime;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Pelmered\FilamentMoneyField\Tables\Columns\MoneyColumn;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use PHPUnit\Metadata\Group;
use Shuchkin\SimpleXLSX;

class CatPolizasResource extends Resource
{
    protected static ?string $model = CatPolizas::class;
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $label = 'Poliza';
    protected static ?string $pluralLabel = 'Polizas';
    protected static ?string $navigationIcon ='fas-scale-balanced';
    public ?string $activeTab = 'Todas';
    public static function form(Form $form): Form
    {
        return $form
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
                Forms\Components\DatePicker::make('fecha')
                    ->required()
                    ->default(function(){
                        $fecha = new DateTime();
                        $dia = Carbon::now()->format('d');
                        $fecha->setDate(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, intval($dia));
                        //dd($fecha);
                        return date_format($fecha,'Y-m-d');
                    })->maxDate(function (){
                        $month = Filament::getTenant()->periodo;
                        $year = Filament::getTenant()->ejercicio;
                        $date = Carbon::create($year, $month, 1);
                        $day = $date->lastOfMonth()->day;
                        return  Carbon::create($year, $month,$day);
                    })->minDate(function (){
                        $month = Filament::getTenant()->periodo;
                        $year = Filament::getTenant()->ejercicio;
                        $date = Carbon::create($year, $month, 1);
                        $day = $date->firstOfMonth()->day;
                        return Carbon::create($year, $month,$day);
                    }),
                Forms\Components\Select::make('tipo')
                    ->required()
                    ->live()
                    ->options([
                        'Dr'=>'Dr',
                        'Ig'=>'Ig',
                        'Eg'=>'Eg',
                        'PV'=>'PV',
                        'PG'=>'PG',
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
                    ->columnSpan(4),
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
                Section::make()
                ->columns([
                    'default' => 5,
                    'sm' => 5,
                    'md' => 5,
                    'lg' => 5,
                    'xl' => 5,
                    '2xl' => 5,
                ])->schema([
                    TableRepeater::make('detalle')
                    ->relationship('partidas')
                    ->headers([
                        Header::make('codigo')->width('250px'),
                        Header::make('cargo')->width('100px'),
                        Header::make('abono')->width('100px'),
                        Header::make('factura')->width('100px')
                        ->label('Referencia'),
                        Header::make('concepto')->width('300px'),
                    ])
                    ->schema([
                        Select::make('codigo')
                        ->options(
                            DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                            ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                        )
                        ->searchable()
                        ->columnSpan(2)
                        ->live()
                        ->afterStateUpdated(function(Get $get,Set $set){
                            $cuenta = CatCuentas::where('team_id',Filament::getTenant()->id)->where('codigo',$get('codigo'))->get();
                            $nom = $cuenta[0]->nombre;
                            $set('cuenta',$nom);
                            $set('concepto',$get('../../concepto'));
                        }),
                        TextInput::make('cargo')
                            ->numeric()
                            ->currencyMask(decimalSeparator: '.',precision: 2)
                            ->default(0)
                            ->live(onBlur:true)
                            ->prefix('$')->afterStateUpdated(function(Get $get,Set $set){
                                $cargo = $get('cargo');
                                if($cargo === ''||$cargo === null) $set('cargo',0);
                                self::TotalizarCA($get,$set);
                            }),
                        TextInput::make('abono')
                            ->numeric()
                            ->currencyMask(decimalSeparator: '.',precision: 2)
                            ->default(0)
                            ->live(onBlur:true)
                            ->prefix('$')->afterStateUpdated(function(Get $get,Set $set){
                                $abono = $get('abono');
                                if($abono === ''||$abono === null) $set('abono',0);
                                self::TotalizarCA($get,$set);
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
                    ])->columnSpan('full')->streamlined()
                    ]),
                    Forms\Components\Group::make([
                        Forms\Components\Placeholder::make('Totales')
                            ->label('Total de Cargos y Abonos')->columnSpan(2),
                        TextInput::make('total_cargos')->hiddenLabel()->prefix('$')->readOnly()
                        ->formatStateUsing(function (Get $get){
                            $partidas = $get('detalle');
                            $columna = array_column($partidas,'cargo');
                            $suma = array_sum($columna);
                            return floatval($suma);
                        })->numeric()->currencyMask(decimalSeparator: '.',precision: 2)->columnSpan(2),
                        TextInput::make('total_abonos')->hiddenLabel()->prefix('$')->readOnly()
                            ->formatStateUsing(function (Get $get){
                                $partidas = $get('detalle');
                                $columna = array_column($partidas,'abono');
                                $suma = array_sum($columna);
                                return floatval($suma);
                            })->numeric()->currencyMask(decimalSeparator: '.',precision: 2)->columnSpan(2)
                    ])->columnSpan('full')->columns(7)

                    ]);
            /*->columns([
                'sm' => 1,
                'xl' => 5,
                '2xl' => 5,
            ]);*/
    }

    public static function TotalizarCA(Get $get,Set $set)
    {
        $partidas = $get('../../detalle');
        if(!$partidas) return;
        $columnaC = array_column($partidas,'cargo');
        $sumaC = floatval(array_sum($columnaC));
        $columnaA = array_column($partidas,'abono');
        $sumaA = floatval(array_sum($columnaA));
        $sumaC = bcdiv($sumaC,1,2);
        $sumaA = bcdiv($sumaA,1,2);
        $set('../../total_cargos',$sumaC);
        $set('../../total_abonos',$sumaA);

        //dd($sumaC,$sumaA,$partidas);
    }
    public static function updateTotals(Get $get, Set $set)
    {
        $cargos = collect($get('partidas'))->pluck('cargo')->sum();
        $abonos = collect($get('partidas'))->pluck('abono')->sum();
        $set('cargos',bcdiv($cargos,1,2));
        $set('abonos',bcdiv($abonos,1,2));
    }

    public static function table(Table $table): Table
    {
        return $table
        ->recordClasses('row_gral')
        ->query(CatPolizas::query())
            ->modifyQueryUsing(function ($query) {
                $query->where('team_id',Filament::getTenant()->id)
                    ->orderBy('tipo', 'ASC')
                    ->orderBy('folio', 'ASC');
            })
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                ->dateTime('d-m-Y')
                ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('folio')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('concepto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('referencia')
                    ->searchable()
                    ->prefix('F-')->formatStateUsing(function ($state) {
                        $truncatedValue = Str::limit($state, 50);
                        return new HtmlString("<span title='{$state}'>{$truncatedValue}</span>");
                    })
                    ->action(Action::make('referencia')->form([
                        TextInput::make('Referencia')
                            ->hiddenLabel()->readOnly()
                            ->default(function($record){
                                return $record->referencia;
                            })
                    ])),
                Tables\Columns\TextColumn::make('cargos')
                    ->formatStateUsing(function (?string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                Tables\Columns\TextColumn::make('abonos')
                    ->formatStateUsing(function (?string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tiposat')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon(null)
                    ->modalSubmitAction(false)
                    ->modalWidth('7xl')
                    ->visible(function(){
                        $team = Filament::getTenant()->id;
                        $periodo = Filament::getTenant()->periodo;
                        $ejercicio = Filament::getTenant()->ejercicio;
                        if(!ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->exists())
                        {
                            return false;
                        }
                        else{
                            $estado = ContaPeriodos::where('team_id',$team)->where('periodo',$periodo)->where('ejercicio',$ejercicio)->first()->estado;
                            if($estado == 1) return false;
                            else return true;
                        }
                    }),
                Tables\Actions\ViewAction::make()
                    ->icon('fas-eye')
                    ->iconButton()
                    ->visible(function ($record){
                        if($record->periodo == Filament::getTenant()->periodo && $record->ejercicio == Filament::getTenant()->ejercicio) return false;
                        else return true;
                    })->modalWidth('7xl'),
                Tables\Actions\EditAction::make()
                ->label('')
                ->icon(null)
                ->disabled(function ($record){
                    if($record->periodo == Filament::getTenant()->periodo && $record->ejercicio == Filament::getTenant()->ejercicio) return false;
                    else return true;
                })
                ->modalSubmitActionLabel('Grabar')
                ->modalWidth('7xl')
                    ->before(function ($data,$action){
                        $cargos = round($data['total_cargos'],3);
                        $abonos = round($data['total_abonos'],3);
                        if ($cargos != $abonos){
                            Notification::make()->title('Poliza descuadrada')->warning()->send();
                            $action->halt();
                        }
                    })
                ->after(function ($record){
                    $id = $record->id;
                    //DB::table('auxiliares_cat_polizas')->where('cat_polizas_id',$id)->delete();
                    $cat_aux = DB::table('auxiliares_cat_polizas')->where('cat_polizas_id',$id)->get();
                    $nopar = 1;
                    foreach ($cat_aux as $c_a) {
                        DB::table('auxiliares')->where('id',$c_a->auxiliares_id)->update([
                            'cat_polizas_id'=>$id,
                            'nopartida'=>$nopar
                        ]);
                        $nopar++;
                    }
                    $cargos = DB::table('auxiliares')->where('cat_polizas_id',$id)->sum('cargo');
                    $abonos = DB::table('auxiliares')->where('cat_polizas_id',$id)->sum('abono');
                    CatPolizas::where('id',$id)->update([
                        'cargos'=>$cargos,
                        'abonos'=>$abonos,
                    ]);
                })->visible(function(){
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
                Tables\Actions\DeleteAction::make()
                ->label('')->icon('fas-trash-can')
                    ->disabled(function ($record){
                        if($record->periodo == Filament::getTenant()->periodo && $record->ejercicio == Filament::getTenant()->ejercicio) return false;
                        else return true;
                    })
                ->requiresConfirmation()
                ->before(function ($record) {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    DB::table('cat_polizas_team')
                        ->where('cat_polizas_id',$record->id)->delete();
                    $aux_bancos =DB::table('auxiliares')
                        ->where('cat_polizas_id',$record->id)
                        ->where('igeg_id','>',0)->get();
                    foreach ($aux_bancos as $aux_banco) {
                        $cargo = $aux_banco->cargo;
                        $abono = $aux_banco->abono;
                        $impo = floatval($cargo)+floatval($abono);
                        DB::table('ingresos_egresos')
                        ->where('id',$aux_banco->igeg_id)
                        ->increment('pendientemxn',$impo);
                        DB::table('movbancos')
                        ->where('id',$record->idmovb)
                        ->increment('pendiente_apli',$impo);
                    }

                    DB::table('auxiliares')
                        ->where('cat_polizas_id',$record->id)->delete();
                })
                ->after(function($record){
                    if($record->idmovb > 0){
                        DB::table('movbancos')->where('id',$record->idmovb)->update([
                            'tercero'=>null,
                            'factura'=>null,
                            'uuid'=>null,
                            'contabilizada'=>'NO'
                        ]);
                    }
                    if($record->idcfdi > 0){
                        DB::table('almacencfdis')->where('id',$record->idcfdi)->update([
                            'used'=>'NO'
                        ]);
                    }
                    if(RegTraspasos::where('poliza',$record->id)->exists()){
                        $reg = RegTraspasos::where('poliza',$record->id)->first();
                        Movbancos::where('id',$reg->mov_ent)->update(['contabilizada' => 'NO']);
                        Movbancos::where('id',$reg->mov_sal)->update(['contabilizada' => 'NO']);
                    }
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                })->visible(function(){
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
                Tables\Actions\Action::make('Copiar Poliza')
                ->icon('fas-copy')->tooltip('Copiar Poliza')->iconButton()
                ->form([
                    TextInput::make('dia')->label('Dia')
                        ->numeric()->required()->minValue(1)->maxValue(12)
                        ->default(function($record){
                            return Carbon::create($record->fecha)->day;
                        })->minValue(1)->maxValue(31),
                    TextInput::make('mes')->label('Periodo')
                    ->numeric()->required()->minValue(1)->maxValue(12)
                    ->default(function($record){
                        return Carbon::create($record->fecha)->month;
                    }),
                    TextInput::make('anio')->label('Ejercicio')
                        ->numeric()->required()
                        ->default(function($record){
                            return Carbon::create($record->fecha)->year;
                    }),
                ])
                ->action(function ($record,$data){
                    $enca = CatPolizas::where('id',$record->id)->first();
                    $anio_pol = intval($data['anio']);
                    $mes_pol = intval($data['mes']);
                    $dia_pol = intval($data['dia']);
                    $nopoliza = intval(DB::table('cat_polizas')
                            ->where('team_id',Filament::getTenant()->id)
                            ->where('tipo',$enca->tipo)
                            ->where('periodo',$mes_pol)
                            ->where('ejercicio',$anio_pol)->max('folio')) + 1;
                    $dats = Carbon::now();

                    $fecha = $anio_pol.'-'.$mes_pol.'-'.$dia_pol;
                    $poliza = CatPolizas::create([
                        'tipo'=>$enca->tipo,
                        'folio'=>$nopoliza,
                        'fecha'=>$fecha,
                        'concepto'=>$enca->concepto,
                        'cargos'=>$enca->cargos,
                        'abonos'=>$enca->abonos,
                        'periodo'=>$mes_pol,
                        'ejercicio'=>$anio_pol,
                        'referencia'=>$enca->referencia,
                        'uuid'=>$enca->uuid,
                        'tiposat'=>$enca->tiposat,
                        'idmovb'=>0,
                        'team_id'=>Filament::getTenant()->id
                    ]);
                    $polno = $poliza['id'];
                    $auxiliares = Auxiliares::where('cat_polizas_id',$record->id)->get();
                    foreach ($auxiliares as $aux) {
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$aux->codigo,
                            'cuenta'=>$aux->cuenta,
                            'concepto'=>$aux->concepto,
                            'cargo'=>$aux->cargo,
                            'abono'=>$aux->abono,
                            'factura'=>$aux->factura,
                            'nopartida'=>$aux->nopartida,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                    }
                    Notification::make()
                        ->title('Poliza '.$enca->tipo.' '.$nopoliza.' Grabada')
                        ->success()
                        ->send();
                })->visible(function(){
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
                    })
            ])
            ->actionsPosition(ActionsPosition::BeforeColumns)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->createAnother(false)
                    ->label('Agregar')
                    ->icon('fas-plus')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalWidth('7xl')
                    ->before(function ($data,$action){
                        $cargos = round($data['total_cargos'],3);
                        $abonos = round($data['total_abonos'],3);
                        if ($cargos != $abonos){
                            Notification::make()->title('Poliza descuadrada')->warning()->send();
                            $action->halt();
                        }
                    })
                    ->after(function ($record){
                        $id = $record->id;
                        //DB::table('auxiliares_cat_polizas')->where('cat_polizas_id',$id)->delete();
                        $cat_aux = DB::table('auxiliares_cat_polizas')->where('cat_polizas_id',$id)->get();
                        $nopar = 1;
                        foreach ($cat_aux as $c_a) {
                            DB::table('auxiliares')->where('id',$c_a->auxiliares_id)->update([
                                'cat_polizas_id'=>$id,
                                'nopartida'=>$nopar
                            ]);
                            $nopar++;
                        }
                        $cargos = DB::table('auxiliares')->where('cat_polizas_id',$id)->sum('cargo');
                        $abonos = DB::table('auxiliares')->where('cat_polizas_id',$id)->sum('abono');
                        CatPolizas::where('id',$id)->update([
                            'cargos'=>$cargos,
                            'abonos'=>$abonos,
                        ]);
                    })->visible(function(){
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
                Tables\Actions\Action::make('Póliza de Apertura')
                ->label('Póliza de Apertura')
                ->icon('fas-plus')
                ->modalSubmitActionLabel('Grabar')
                ->modalWidth('7xl')
                ->form(function (Form $form){
                    return $form
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\DatePicker::make('fecha')
                            ->default(function (){
                                return Carbon::create(Filament::getTenant()->ejercicio,Filament::getTenant()->periodo,1)->format('Y-m-d');
                            }),
                            Forms\Components\Select::make('metodo')
                            ->required()
                            ->live(onBlur: true)
                            ->options(['MANUAL'=>'Captura Manual','ARCHIVO'=>'Desde Archivo']),
                        ])->columnSpanFull()->columns(2),
                        Forms\Components\Fieldset::make('Por Archivo')
                        ->schema([
                            Forms\Components\FileUpload::make('archivo')
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state){
                                if($state){
                                    $archivo = $state;
                                    //dd($archivo->getRealPath());
                                }
                            }),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('Procesar')
                                ->icon('fas-file-import')
                                ->action(function (Get $get,Set $set){
                                    $archivos = $get('archivo');
                                    foreach ($archivos as $archivo) {
                                        $file = $archivo->getRealPath();
                                        $fileInfo = pathinfo($file);
                                        $extension = $fileInfo['extension'];
                                        if($extension == 'csv'||$extension == 'CSV'||$extension == 'xls'||$extension == 'XLS'||$extension == 'xlsx'||$extension == 'XLSX') {
                                            if ( $xlsx = SimpleXLSX::parse($file) ) {
                                                $rows = $xlsx->rows();
                                                $datos = [];
                                                $datos_acum = [];
                                                $datos_error = [];
                                                $cargos_tot = 0;
                                                $abonos_tot = 0;
                                                for($i = 7;$i<count($rows);$i++) {
                                                    $row = $rows[$i];
                                                    if($row[0] != ' '&&$row[0] != ''&&$row[1] != '') {
                                                        $codigo = $row[0];
                                                        $cuenta = $row[1];
                                                        $cargo = floatval($row[6]);
                                                        $abono = floatval($row[7]);
                                                        $cargos = 0;
                                                        $abonos = 0;
                                                        if($cargo > 0){
                                                            $cargos+= $cargo;
                                                            $abonos+= 0;
                                                        }else {
                                                            $cargos+= 0;
                                                            $abonos+= ($cargo*-1);
                                                        }
                                                        if($abono > 0){
                                                            $cargos+= 0;
                                                            $abonos+= $abono;
                                                        }else {
                                                            $cargos+= ($abono*-1);
                                                            $abonos+= 0;
                                                        }
                                                        $codigo = str_replace('-','',$codigo);
                                                        $cta = CatCuentas::where('codigo',$codigo)->first();
                                                        $tipo = $cta?->tipo ?? '';
                                                        if($tipo == 'D'){
                                                            $cargos_tot+= $cargos;
                                                            $abonos_tot+= $abonos;
                                                            $datos[] = ['codigo'=>$codigo,'cuenta'=>$cta?->nombre ?? $cuenta,'cargo'=>$cargos,'abono'=>$abonos,'tipo'=>$tipo];
                                                        }else if ($tipo == 'A'){
                                                            $datos_acum[] = ['codigo'=>$codigo,'cuenta'=>$cta?->nombre ?? $cuenta,'cargo'=>$cargos,'abono'=>$abonos,'tipo'=>$tipo];
                                                        }else{
                                                            $cod1 = substr($codigo,0,3);
                                                            $cod2 = substr($codigo,3,2);
                                                            $cod3 = substr($codigo,5,3);
                                                            $acumula = '';
                                                            $tipC = 'E';
                                                            if(intval($cod1) > 0 &&intval($cod3) > 0 && intval($cod2) > 0){
                                                                $acumula = $cod1.$cod2.'000';
                                                                $tipC = 'D';
                                                            }
                                                            if(intval($cod1) > 0 &&intval($cod3) == 0 && intval($cod2) > 0){
                                                                $acumula = $cod1.'00000';
                                                                $tipC = 'D';
                                                            }
                                                            if(intval($cod1) > 0 &&intval($cod3) == 0 && intval($cod2) == 0){
                                                                $acumula = '0';
                                                                $tipC = 'A';
                                                            }
                                                            $exis_acum = 'NO';
                                                            $naturaleza = 'E';
                                                            if(CatCuentas::where('codigo',$acumula)->exists()){
                                                                $naturaleza = CatCuentas::where('codigo',$acumula)->first()->naturaleza;
                                                                $exis_acum = 'SI';
                                                            }
                                                            $datos_error[] = ['codigo'=>$codigo,'cuenta'=>$cta?->nombre ?? $cuenta,'cargo'=>$cargos,'abono'=>$abonos,'tipo'=>$tipC,'acumula'=>$acumula,'exis_acum'=>$exis_acum,'naturaleza'=>$naturaleza];
                                                        }

                                                    }
                                                }
                                                $set('datos',$datos);
                                                $set('datos_acumula',$datos_acum);
                                                $set('datos_error',$datos_error);
                                                $set('cargos_tot',$cargos_tot);
                                                $set('abonos_tot',$abonos_tot);
                                                $set('diferencia',$cargos_tot-$abonos_tot);
                                            } else {
                                                dd(SimpleXLSX::parseError());
                                            }
                                        }else{
                                            Notification::make()->title('Archivo no valido')->danger()->send();
                                        }
                                    }
                                })
                            ]),
                            Forms\Components\Group::make([
                                TableRepeater::make('datos')
                                ->deletable(false)
                                ->reorderable(false)
                                ->streamlined()
                                ->defaultItems(0)
                                ->headers([
                                    Header::make('Codigo'),
                                    Header::make('Cargos'),
                                    Header::make('Abonos'),
                                ])
                                ->schema([
                                    Select::make('codigo')
                                    ->options(
                                        DB::table('cat_cuentas')->where('team_id',Filament::getTenant()->id)
                                        ->select(DB::raw("concat(codigo,'-',nombre) as mostrar"),'codigo')->where('tipo','D')->orderBy('codigo')->pluck('mostrar','codigo')
                                    )
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get,Set $set){
                                        $codigo = $get('codigo');
                                        $cuenta = CatCuentas::where('codigo',$codigo)->first();
                                        $set('cuenta',$cuenta?->nombre ?? '');
                                    }),
                                    Hidden::make('cuenta'),
                                    TextInput::make('cargo')->currencyMask()->prefix('$')->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get,Set $set){
                                        self::suma_apertura($get,$set);
                                    }),
                                    TextInput::make('abono')->currencyMask()->prefix('$')->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get,Set $set){
                                        self::suma_apertura($get,$set);
                                    }),
                                ])
                                ->columnSpanFull(),
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('Totales'),
                                    TextInput::make('cargos_tot')->currencyMask()->prefix('$')->readOnly()
                                    ->label('Cargos'),
                                    TextInput::make('abonos_tot')->currencyMask()->prefix('$')->readOnly()
                                    ->label('Abonos'),
                                    Hidden::make('diferencia'),
                                ])->columnSpanFull()->columns(4),
                                Forms\Components\Section::make('Acumulativas')
                                ->collapsible()->collapsed(true)
                                ->schema([
                                TableRepeater::make('datos_acumula')
                                    ->deletable(false)
                                    ->addable(false)
                                    ->reorderable(false)
                                    ->streamlined()
                                    ->defaultItems(0)
                                    ->headers([
                                        Header::make('Codigo'),
                                        Header::make('Cuenta'),
                                        Header::make('Cargos'),
                                        Header::make('Abonos'),
                                    ])
                                    ->schema([
                                        TextInput::make('codigo')->readOnly(),
                                        TextInput::make('cuenta')->readOnly(),
                                        TextInput::make('cargo')->currencyMask()->prefix('$')->readOnly(),
                                        TextInput::make('abono')->currencyMask()->prefix('$')->readOnly(),
                                    ])
                                    ->columnSpanFull()->collapsible()->collapsed(true),
                                ])->columnSpanFull(),
                                Section::make('Errores')
                                    ->collapsible()->collapsed(true)
                                    ->schema([
                                TableRepeater::make('datos_error')
                                    ->deletable(false)
                                    ->addable(false)
                                    ->reorderable(false)
                                    ->streamlined()
                                    ->defaultItems(0)
                                    ->emptyLabel('No hay errores')
                                    ->headers([
                                        Header::make('Codigo'),
                                        Header::make('Cuenta'),
                                        Header::make('Cargos'),
                                        Header::make('Abonos'),
                                        Header::make('Tipo'),
                                        Header::make('Acumula'),
                                        Header::make('Existe Acumulativa'),
                                        Header::make('Naturaleza'),
                                    ])
                                    ->schema([
                                        TextInput::make('codigo')->readOnly(),
                                        TextInput::make('cuenta')->readOnly(),
                                        TextInput::make('cargo')->currencyMask()->prefix('$')->readOnly(),
                                        TextInput::make('abono')->currencyMask()->prefix('$')->readOnly(),
                                        Select::make('tipo')->options(['D'=>'Detalle','A'=>'Acumulativa','E'=>'Error']),
                                        TextInput::make('acumula')->readOnly(),
                                        TextInput::make('exis_acum')->readOnly(),
                                        Select::make('naturaleza')->options(['D'=>'Deudora','A'=>'Acreedora','E'=>'Error']),
                                    ])
                                    ->columnSpanFull()->collapsible()->collapsed(true),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('Grabar Cuentas')
                                    ->icon('fas-save')
                                    ->action(function (Get $get){
                                        $datos = $get('datos_error');
                                        foreach ($datos as $dato) {
                                            CatCuentas::create([
                                                'codigo'=>$dato['codigo'],
                                                'nombre'=>$dato['cuenta'],
                                                'naturaleza'=>$dato['naturaleza'],
                                                'tipo'=>$dato['tipo'],
                                                'acumula'=>$dato['acumula'],
                                                'team_id'=>Filament::getTenant()->id
                                            ]);
                                        }
                                        Notification::make()->title('Cuentas Grabadas')->success()->send();
                                    })
                                ])->columnSpanFull()
                                ])
                            ])->columnSpanFull()
                        ])
                        ->columnSpanFull()
                        ->visible(function (Get $get){
                            if($get('metodo') == 'ARCHIVO') return true;
                            else return false;
                        })
                    ]);
                })
                ->action(function ($data){
                    $datos = $data['datos'];
                    $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo','Dr')->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                    $poliza = CatPolizas::create([
                        'tipo'=>'Dr',
                        'folio'=>$nopoliza,
                        'fecha'=>$data['fecha'],
                        'concepto'=>'Poliza de Apertura',
                        'cargos'=>round(floatval($data['cargos_tot']),2),
                        'abonos'=>round(floatval($data['abonos_tot']),2),
                        'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,
                        'referencia'=>'F-',
                        'uuid'=>'',
                        'tiposat'=>'Dr',
                        'team_id'=>Filament::getTenant()->id,
                        'idmovb'=>0
                    ]);
                    $polno = $poliza['id'];
                    $par_num = 1;
                    foreach ($datos as $dato) {
                        $aux = Auxiliares::create([
                            'cat_polizas_id'=>$polno,
                            'codigo'=>$dato['codigo'],
                            'cuenta'=>$dato['cuenta'],
                            'concepto'=>'Poliza de Apertura',
                            'cargo'=>round(floatval($dato['cargo']),2),
                            'abono'=>round(floatval($dato['abono']),2),
                            'factura'=>'F-',
                            'nopartida'=>$par_num,
                            'team_id'=>Filament::getTenant()->id
                        ]);
                        DB::table('auxiliares_cat_polizas')->insert([
                            'auxiliares_id'=>$aux['id'],
                            'cat_polizas_id'=>$polno
                        ]);
                        $par_num++;
                    }
                    Notification::make()->title('Poliza de Apertura Dr'.$nopoliza.' Grabada')->success()->send();
                })
            ],Tables\Actions\HeaderActionsPosition::Bottom)
            ->bulkActions([
                /*Tables\Actions\DeleteBulkAction::make('Eliminar')
                ->icon('fas-trash')
                ->requiresConfirmation()
                ->after(function(){

                })->visible(function(){
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
                    })*/
            ])
            ->striped()->defaultPaginationPageOption(8)
            ->paginated([8, 'all'])
            ->filters([
                Tables\Filters\Filter::make('Periodo')
                    ->form([
                        Select::make('Periodo Inicial')
                        ->options([1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12])
                        ->default(Filament::getTenant()->periodo),
                        Select::make('Periodo Final')
                        ->options([1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12])
                        ->default(Filament::getTenant()->periodo),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereBetween('periodo',[$data['Periodo Inicial'],$data['Periodo Final']]);

                    })->visible(function($livewire){
                       if($livewire->activeTab == 'OP') return true;
                       else return false;
                    })
            ], layout: Tables\Enums\FiltersLayout::Modal);
    }

    public static function suma_apertura(Get $get,Set $set): void
    {
        $datos = $get('../../datos');
        $cargos = 0;
        $abonos = 0;
        foreach ($datos as $dato) {
            $cargos+= $dato['cargo'];
            $abonos+= $dato['abono'];
        }
        $set('../../cargos_tot',bcdiv($cargos,1,2));
        $set('../../abonos_tot',bcdiv($abonos,1,2));
        $set('../../diferencia',bcdiv($cargos,1,2)-bcdiv($abonos,1,2));
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
            'index' => Pages\ListCatPolizas::route('/'),
            //'create' => Pages\CreateCatPolizas::route('/create'),
            //'edit' => Pages\EditCatPolizas::route('/{record}/edit'),
        ];
    }
}

