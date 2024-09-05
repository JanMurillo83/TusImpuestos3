<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitudesResource\Pages;
use App\Filament\Resources\SolicitudesResource\RelationManagers;
use App\Models\Solicitudes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolicitudesResource extends Resource
{
    protected static ?string $model = Solicitudes::class;

    protected static ?string $navigationGroup = 'CFDI';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('request_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('message')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('xml_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ini_date')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ini_hour')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('end_date')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('end_hour')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_tax')
                    ->required()
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
                Tables\Columns\TextColumn::make('request_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('message')
                    ->searchable(),
                Tables\Columns\TextColumn::make('xml_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ini_date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ini_hour')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_hour')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_tax')
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
            'index' => Pages\ListSolicitudes::route('/'),
            'create' => Pages\CreateSolicitudes::route('/create'),
            'edit' => Pages\EditSolicitudes::route('/{record}/edit'),
        ];
    }
}
