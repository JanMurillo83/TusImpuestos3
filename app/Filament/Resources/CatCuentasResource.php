<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatCuentasResource\Pages;
use App\Filament\Resources\CatCuentasResource\RelationManagers;
use App\Models\CatCuentas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Navigation\NavigationGroup;

class CatCuentasResource extends Resource
{
    protected static ?string $model = CatCuentas::class;
    protected static ?string $navigationGroup = 'Contabilidad';
    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('codigo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('acumula')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tipo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('naturaleza')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('csat')
                    ->maxLength(255),
                Forms\Components\TextInput::make('team_id')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('acumula')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('naturaleza')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('csat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('team_id')
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
            'index' => Pages\ListCatCuentas::route('/'),
            'create' => Pages\CreateCatCuentas::route('/create'),
            'edit' => Pages\EditCatCuentas::route('/{record}/edit'),
        ];
    }
}
