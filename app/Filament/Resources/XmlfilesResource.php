<?php

namespace App\Filament\Resources;

use App\Filament\Resources\XmlfilesResource\Pages;
use App\Filament\Resources\XmlfilesResource\RelationManagers;
use App\Models\Xmlfiles;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class XmlfilesResource extends Resource
{
    protected static ?string $model = Xmlfiles::class;
    protected static ?string $navigationGroup = 'Descargas CFDI1';
    protected static ?string $pluralLabel = 'Descargas CFDI1';
    protected static ?string $label = 'CFDI';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('taxid')
                    ->maxLength(45),
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->maxLength(500),
                Forms\Components\Textarea::make('content')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('periodo')
                    ->numeric(),
                Forms\Components\TextInput::make('ejercicio')
                    ->numeric(),
                Forms\Components\TextInput::make('tipo')
                    ->maxLength(45),
                Forms\Components\TextInput::make('solicitud')
                    ->maxLength(500),
                Forms\Components\Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->columns([
                Tables\Columns\TextColumn::make('taxid')
                    ->label('Emisor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('solicitud')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([

            ])
            ->bulkActions([

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
            'index' => Pages\ListXmlfiles::route('/'),
            'create' => Pages\CreateXmlfiles::route('/create'),
            'edit' => Pages\EditXmlfiles::route('/{record}/edit'),
        ];
    }
}
