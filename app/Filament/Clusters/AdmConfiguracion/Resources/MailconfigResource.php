<?php

namespace App\Filament\Clusters\AdmConfiguracion\Resources;

use App\Filament\Clusters\AdmConfiguracion;
use App\Filament\Clusters\AdmConfiguracion\Resources\MailconfigResource\Pages;
use App\Filament\Clusters\AdmConfiguracion\Resources\MailconfigResource\RelationManagers;
use App\Models\Mailconfig;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MailconfigResource extends Resource
{
    protected static ?string $model = Mailconfig::class;

    protected static ?string $navigationIcon = 'fas-envelope';

    protected static ?string $cluster = AdmConfiguracion::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Configuracion de Correo';
    protected static ?string $pluralLabel = 'Configuracion de Correo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('host')
                    ->maxLength(255),
                Forms\Components\TextInput::make('port')
                    ->numeric(),
                Forms\Components\TextInput::make('username')
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->maxLength(255),
                Forms\Components\TextInput::make('from_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('from_address')
                    ->maxLength(255),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('host')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('from_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('from_address')
                    ->searchable(),
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])->headerActions([
                Tables\Actions\CreateAction::make()
            ],Tables\Actions\HeaderActionsPosition::Bottom);
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
            'index' => Pages\ListMailconfigs::route('/'),
            //'create' => Pages\CreateMailconfig::route('/create'),
            //'edit' => Pages\EditMailconfig::route('/{record}/edit'),
        ];
    }
}
