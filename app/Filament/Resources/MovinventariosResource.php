<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovinventariosResource\Pages;
use App\Filament\Resources\MovinventariosResource\RelationManagers;
use App\Models\Movinventarios;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovinventariosResource extends Resource
{
    protected static ?string $model = Movinventarios::class;
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $label = 'Movimiento';
    protected static ?string $pluralLabel = 'Movimientos al Inventario';
    protected static ?string $navigationIcon = 'fas-dolly';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('folio')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DatePicker::make('fecha')
                    ->required(),
                Forms\Components\TextInput::make('tipo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('producto')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(255),
                Forms\Components\TextInput::make('concepto')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tipoter')
                    ->maxLength(255),
                Forms\Components\TextInput::make('idter')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('nomter')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cant')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('costou')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('costot')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('preciou')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('preciot')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('periodo')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('ejercicio')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required()
                    ->default(0),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('producto')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('concepto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipoter')
                    ->searchable(),
                Tables\Columns\TextColumn::make('idter')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomter')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cant')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('costou')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('costot')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('preciou')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('preciot')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('team.name')
                    ->numeric()
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
            ->striped()->defaultPaginationPageOption(8)
            ->paginated([8, 'all'])
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
            'index' => Pages\ListMovinventarios::route('/'),
            //'create' => Pages\CreateMovinventarios::route('/create'),
            //'edit' => Pages\EditMovinventarios::route('/{record}/edit'),
        ];
    }
}
