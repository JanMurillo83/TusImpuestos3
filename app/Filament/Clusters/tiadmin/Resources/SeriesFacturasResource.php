<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\SeriesFacturasResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\SeriesFacturasResource\RelationManagers;
use App\Models\SeriesFacturas;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SeriesFacturasResource extends Resource
{
    protected static ?string $model = SeriesFacturas::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Configuracion';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador']);
    }
    protected static ?int $navigationSort = 4;
    protected static ?string $label = 'Serie de Facturas';
    protected static ?string $pluralLabel = 'Series de Facturas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('serie')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Hidden::make('tipo')
                    ->default('F'),
                Forms\Components\TextInput::make('folio')
                    ->required()
                    ->numeric()->default(0)
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(255),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serie')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('folio')
                    ->numeric()
                    ->sortable()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSeriesFacturas::route('/'),
            'create' => Pages\CreateSeriesFacturas::route('/create'),
            'edit' => Pages\EditSeriesFacturas::route('/{record}/edit'),
        ];
    }
}
