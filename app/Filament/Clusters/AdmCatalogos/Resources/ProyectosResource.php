<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources;

use App\Filament\Clusters\AdmCatalogos;
use App\Filament\Clusters\AdmCatalogos\Resources\ProyectosResource\Pages;
use App\Filament\Clusters\AdmCatalogos\Resources\ProyectosResource\RelationManagers;
use App\Models\Proyectos;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProyectosResource extends Resource
{
    protected static ?string $model = Proyectos::class;

    protected static ?string $navigationIcon = 'fas-cog';
    protected static ?int $navigationSort = 6;
    protected static ?string $cluster = AdmCatalogos::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('clave')
                    ->maxLength(255)->disabledOn('edit'),
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(255)->columnSpanFull(),
                Forms\Components\TextInput::make('compras')
                    ->required()
                    ->numeric()->prefix('$')
                    ->default(0.00000000)->currencyMask()->readOnly(),
                Forms\Components\TextInput::make('ventas')
                    ->required()
                    ->numeric()->prefix('$')
                    ->default(0.00000000)->currencyMask()->readOnly(),
                Forms\Components\Hidden::make('estado')
                    ->default('Activo'),
                Forms\Components\Hidden::make('team_id')
                   ->default(Filament::getTenant()->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('compras')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ventas')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                ])
            ],Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label('Agregar')->icon('fas-circle-plus')
                ->modalWidth('md')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitActionLabel('Grabar')
                ->createAnother(false)
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
            'index' => Pages\ListProyectos::route('/'),
            //'create' => Pages\CreateProyectos::route('/create'),
            //'edit' => Pages\EditProyectos::route('/{record}/edit'),
        ];
    }
}
