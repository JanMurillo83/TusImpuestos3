<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatPolizasResource\Pages;
use App\Filament\Resources\CatPolizasResource\RelationManagers;
use App\Models\CatCuentas;
use App\Models\CatPolizas;
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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
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
                Forms\Components\DatePicker::make('fecha')
                    ->required()
                    ->default(function(){
                        $fecha = new DateTime();
                        $dia = Carbon::now()->format('d');
                        $fecha->setDate(Filament::getTenant()->ejercicio, Filament::getTenant()->periodo, intval($dia));
                        //dd($fecha);
                        return date_format($fecha,'Y-m-d');
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
                    TableRepeater::make('partidas')
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
                        TextInput::make('cargo')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',','$'])
                            ->default(0)
                            ->live(onBlur:true)
                            ->prefix('$'),
                        TextInput::make('abono')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',','$'])
                            ->default(0)
                            ->live(onBlur:true)
                            ->prefix('$'),
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
                    ])->columnSpanFull()->streamlined()
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
                Tables\Actions\EditAction::make()
                ->label('')
                ->icon(null)
                ->modalSubmitActionLabel('Grabar')
                ->modalWidth('7xl')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped()->defaultPaginationPageOption(8)
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

