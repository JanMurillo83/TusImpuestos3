<?php

namespace App\Filament\Clusters\Herramientas\Resources;

use App\Filament\Clusters\Herramientas;
use App\Filament\Clusters\Herramientas\Resources\IngresosEgresosResource\Pages;
use App\Filament\Clusters\Herramientas\Resources\IngresosEgresosResource\RelationManagers;
use App\Models\Almacencfdis;
use App\Models\CatPolizas;
use App\Models\IngresosEgresos;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IngresosEgresosResource extends Resource
{
    protected static ?string $model = IngresosEgresos::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Herramientas::class;
    protected static bool $shouldRegisterNavigation = false;
    public static function scopeEloquentQueryToTenant(Builder $query, ?Model $tenant): Builder
    {
        return $query->where('team_id',Filament::getTenant()->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('xml_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('poliza')
                    ->numeric(),
                Forms\Components\TextInput::make('subtotalusd')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('ivausd')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('totalusd')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('subtotalmxn')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('ivamxn')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('totalmxn')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('tcambio')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->maxLength(255),
                Forms\Components\TextInput::make('referencia')
                    ->maxLength(255),
                Forms\Components\TextInput::make('pendientemxn')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('pendienteusd')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('pagadousd')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('pagadomxn')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('tipo')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('periodo')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('ejercicio')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('periodo',Filament::getTenant()->periodo)->where('ejercicio',Filament::getTenant()->ejercicio)->where('team_id',Filament::getTenant()->id);
            })
            ->columns([
                Tables\Columns\TextColumn::make('xml_id')
                    ->label('Factura')
                    ->formatStateUsing(function ($record){
                        $poliza = Almacencfdis::where('id', $record->xml_id)->first();
                        if($poliza)
                            return $poliza->Serie.$poliza->Folio;
                        else return null;
                    }),
                Tables\Columns\TextColumn::make('poliza')
                    ->formatStateUsing(function ($record){
                        $poliza = CatPolizas::where('id', $record->poliza)->first();
                        if($poliza)
                        return $poliza->tipo.$poliza->folio;
                        else return null;
                    }),
                Tables\Columns\TextColumn::make('tcambio')
                    ->numeric(decimalPlaces: 4, decimalSeparator: '.')->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('totalusd')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('totalmxn')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('referencia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pendientemxn')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pendienteusd')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->formatStateUsing(function ($record){
                        if($record->tipo === 1)
                            return 'Ingreso';
                        else return 'Egreso';
                    }),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->sortable(),
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
                Tables\Actions\Action::make('Liberar')
                ->icon('fas-rotate')->iconButton()
                ->requiresConfirmation()
                ->action(function ($record) {
                    $totmxn = $record->totalmxn;
                    $totusd = $record->totalusd;
                    IngresosEgresos::where('id', $record->id)->update([
                        'pendientemxn' => $totmxn,
                        'pendienteusd' => $totusd,
                    ]);
                    Notification::make()
                        ->title('Proceso Concluido')
                        ->success()
                        ->send();
                }),
                Tables\Actions\Action::make('Editar Saldo')
                    ->icon('fas-edit')->iconButton()
                    ->form(function ($record,$form) {
                        return $form->schema([
                            Forms\Components\Fieldset::make('Totales')
                            ->schema([
                                Forms\Components\TextInput::make('totalmxn')
                                    ->numeric()->currencyMask(decimalSeparator: '.',precision: 2)->prefix('$')->readOnly()
                                    ->default($record->totalmxn),
                                Forms\Components\TextInput::make('tcambio')
                                    ->numeric()->currencyMask(decimalSeparator: '.',precision: 4)->prefix('$')->readOnly()
                                    ->default($record->tcambio),
                                Forms\Components\TextInput::make('totalusd')
                                    ->numeric()->currencyMask(decimalSeparator: '.',precision: 2)->prefix('$')->readOnly()
                                    ->default($record->totalusd),
                            ])->columns(3)->columnSpanFull(),
                            Forms\Components\Fieldset::make('Pendientes')
                            ->schema([
                                Forms\Components\TextInput::make('pendientemxn')
                                    ->numeric()->currencyMask(decimalSeparator: '.',precision: 2)->prefix('$')
                                    ->default($record->pendientemxn),
                                Forms\Components\TextInput::make('pendienteusd')
                                    ->numeric()->currencyMask(decimalSeparator: '.',precision: 2)->prefix('$')
                                    ->default($record->pendienteusd),
                            ])->columns(2)->columnSpanFull()
                        ]);
                    })->action(function ($record,$data) {
                        IngresosEgresos::where('id', $record->id)->update([
                            'pendientemxn' => $data['pendientemxn'],
                            'pendienteusd' => $data['pendienteusd'],
                        ]);
                        Notification::make()
                            ->title('Proceso Concluido')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make('Eliminar')
                ->icon('fas-trash')->iconButton()->requiresConfirmation()
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->recordAction(null);
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
            'index' => Pages\ListIngresosEgresos::route('/'),
            //'create' => Pages\CreateIngresosEgresos::route('/create'),
            //'edit' => Pages\EditIngresosEgresos::route('/{record}/edit'),
        ];
    }
}
