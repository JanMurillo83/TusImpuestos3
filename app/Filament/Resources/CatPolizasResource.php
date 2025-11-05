<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatPolizasResource\Pages;
use App\Filament\Resources\CatPolizasResource\RelationManagers;
use App\Models\Auxiliares;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use App\Models\ContaPeriodos;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Pelmered\FilamentMoneyField\Tables\Columns\MoneyColumn;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use PHPUnit\Metadata\Group;

class CatPolizasResource extends Resource
{
    protected static ?string $model = CatPolizas::class;
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $label = 'Poliza';
    protected static ?string $pluralLabel = 'Polizas';
    protected static ?string $navigationIcon ='fas-scale-balanced';
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
                                Self::TotalizarCA($get,$set);
                            }),
                        TextInput::make('abono')
                            ->numeric()
                            ->currencyMask(decimalSeparator: '.',precision: 2)
                            ->default(0)
                            ->live(onBlur:true)
                            ->prefix('$')->afterStateUpdated(function(Get $get,Set $set){
                                $abono = $get('abono');
                                if($abono === ''||$abono === null) $set('abono',0);
                                Self::TotalizarCA($get,$set);
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
                        })->numeric()->currencyMask(decimalSeparator: '.',precision: 2),
                        TextInput::make('total_abonos')->hiddenLabel()->prefix('$')->readOnly()
                            ->formatStateUsing(function (Get $get){
                                $partidas = $get('detalle');
                                $columna = array_column($partidas,'abono');
                                $suma = array_sum($columna);
                                return floatval($suma);
                            })->numeric()->currencyMask(decimalSeparator: '.',precision: 2)
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
        $sumaC = array_sum($columnaC);
        $columnaA = array_column($partidas,'abono');
        $sumaA = array_sum($columnaA);
        $set('../../total_cargos',$sumaC);
        $set('../../total_abonos',$sumaA);
    }
    public static function updateTotals(Get $get, Set $set)
    {
        $cargos = collect($get('partidas'))->pluck('cargo')->sum();
        $abonos = collect($get('partidas'))->pluck('abono')->sum();
        $set('cargos',$cargos);
        $set('abonos',$abonos);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->query(CatPolizas::query())
            ->modifyQueryUsing(function ($query) {
                $query->where('team_id',Filament::getTenant()->id)
                    ->where('periodo',Filament::getTenant()->periodo)
                    ->where('ejercicio',Filament::getTenant()->ejercicio)
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
                    ->prefix('F-'),
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
                Tables\Actions\EditAction::make()
                ->label('')
                ->icon(null)
                ->modalSubmitActionLabel('Grabar')
                ->modalWidth('7xl')
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
                ->icon('fas-copy')->tooltip('Copiar Poliza')->iconButton()->requiresConfirmation()
                ->action(function ($record){
                    $enca = CatPolizas::where('id',$record->id)->first();
                    $nopoliza = intval(DB::table('cat_polizas')->where('team_id',Filament::getTenant()->id)->where('tipo',$enca->tipo)->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->max('folio')) + 1;
                    $dats = Carbon::now();
                    $fecha = Filament::getTenant()->ejercicio.'-'.Filament::getTenant()->periodo.'-'.$dats->day;
                    $poliza = CatPolizas::create([
                        'tipo'=>$enca->tipo,
                        'folio'=>$nopoliza,
                        'fecha'=>$fecha,
                        'concepto'=>$enca->concepto,
                        'cargos'=>$enca->cargos,
                        'abonos'=>$enca->abonos,
                        'periodo'=>Filament::getTenant()->periodo,
                        'ejercicio'=>Filament::getTenant()->ejercicio,
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
                    ->before(function ($data){
                        $record = $data;
                        //dd($record);
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
            ])->bulkActions([
                Tables\Actions\DeleteBulkAction::make('Eliminar')
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
                    })
            ])
            ->striped()->defaultPaginationPageOption(8)
            ->paginated([8, 'all']);
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

