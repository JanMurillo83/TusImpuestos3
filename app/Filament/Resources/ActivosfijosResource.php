<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivosfijosResource\Pages;
use App\Filament\Resources\ActivosfijosResource\RelationManagers;
use App\Models\Activosfijos;
use App\Models\Terceros;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ActivosfijosResource extends Resource
{
    protected static ?string $model = Activosfijos::class;
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $label = 'Activo Fijo';
    protected static ?string $pluralLabel = 'Activos Fijos';
    protected static ?string $navigationIcon ='fas-car-side';
    protected static ?int $navigationSort =4;

    public static function form(Form $form): Form
    {
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
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serie')
                    ->searchable(),
                Tables\Columns\TextColumn::make('importe')
                    ->prefix('$')
                    ->numeric(decimalPlaces:2,decimalSeparator:'.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('acumulado')
                    ->prefix('$')
                    ->numeric(decimalPlaces:2,decimalSeparator:'.')
                    ->sortable()
                    ->label('Depreciacion Acumulada'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->label('')->icon(null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListActivosfijos::route('/'),
            //'create' => Pages\CreateActivosfijos::route('/create'),
            //'edit' => Pages\EditActivosfijos::route('/{record}/edit'),
        ];
    }
}
