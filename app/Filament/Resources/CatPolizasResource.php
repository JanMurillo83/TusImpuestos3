<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatPolizasResource\Pages;
use App\Filament\Resources\CatPolizasResource\RelationManagers;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
use Carbon\Carbon;
use DateTime;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Pelmered\FilamentMoneyField\Tables\Columns\MoneyColumn;

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
                Forms\Components\DatePicker::make('fecha')
                    ->required()
                    ->default(function(){
                        $fecha = new DateTime();
                        $dia = Carbon::now()->format('d');
                        $fecha->setDate(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, intval($dia));
                        //dd($fecha);
                        return date_format($fecha,'Y-m-d');
                    }),
                Forms\Components\TextInput::make('cargos')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->readOnly()
                    ->placeholder(function (Get $get,Set $set) {
                        $valor = collect($get('partidas'))->pluck('cargo')->sum();
                        Self::updateTotals($get,$set);
                        return floatval($valor);
                    }),
                Forms\Components\TextInput::make('abonos')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->readOnly()
                    ->placeholder(function (Get $get,Set $set) {
                        $valor = collect($get('partidas'))->pluck('abono')->sum();
                        Self::updateTotals($get,$set);
                        return floatval($valor);
                    }),
                Forms\Components\TextInput::make('concepto')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(4),
                Forms\Components\TextInput::make('referencia')
                    ->maxLength(255),
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
                Forms\Components\Repeater::make('partidas')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('codigo')
                            ->options(
                                DB::table('PolCuentas')->where('team_id',Filament::getTenant()->id)->orderBy('codigo')->pluck('mostrar','codigo')
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
                        Forms\Components\Hidden::make('cuenta'),
                        Forms\Components\Hidden::make('factura')
                            ->default(''),
                        Forms\Components\TextInput::make('concepto')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('cargo')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur:true),
                        Forms\Components\TextInput::make('abono')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur:true),
                        Forms\Components\Hidden::make('team_id')
                            ->default(Filament::getTenant()->id)
                    ])->columnSpanFull()->columns(7)
                    ->addActionLabel('Agregar')
                    ->extraAttributes(['style' => 'font-size:0.4em;padding:0.1em;'])
            ])->columns(5);
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
        ->query(
            CatPolizas::where('team_id',Filament::getTenant()->id)
                ->where('periodo',Filament::getTenant()->periodo)
                ->where('ejercicio',Filament::getTenant()->ejercicio)
                ->orderBy('tipo', 'ASC')
                ->orderBy('folio', 'ASC')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('folio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('concepto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cargos')
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                Tables\Columns\TextColumn::make('abonos')
                    ->formatStateUsing(function (string $state) {
                        $formatter = (new \NumberFormatter('es_MX', \NumberFormatter::CURRENCY));
                        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
                        return $formatter->formatCurrency($state, 'MXN');
                    }),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('referencia')
                    ->searchable()
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'create' => Pages\CreateCatPolizas::route('/create'),
            'edit' => Pages\EditCatPolizas::route('/{record}/edit'),
        ];
    }
}
