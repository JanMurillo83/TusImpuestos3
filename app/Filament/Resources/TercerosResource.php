<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TercerosResource\Pages;
use App\Filament\Resources\TercerosResource\RelationManagers;
use App\Models\Terceros;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TercerosResource extends Resource
{
    protected static ?string $model = Terceros::class;
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $label = 'Tercero';
    protected static ?string $pluralLabel = 'Terceros';
    protected static ?string $navigationIcon = 'fas-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rfc')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tipo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cuenta')
                    ->required()
                    ->maxLength(255),
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
                Forms\Components\TextInput::make('tax_id')
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
                Tables\Columns\TextColumn::make('rfc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cuenta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_id')
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
            'index' => Pages\ListTerceros::route('/'),
            'create' => Pages\CreateTerceros::route('/create'),
            'edit' => Pages\EditTerceros::route('/{record}/edit'),
        ];
    }
}
