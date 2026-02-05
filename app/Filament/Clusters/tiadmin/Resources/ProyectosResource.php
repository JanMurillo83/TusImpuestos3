<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\ProyectosResource\Pages;
use App\Models\Proyectos;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;

class ProyectosResource extends Resource
{
    protected static ?string $model = Proyectos::class;
    protected static ?string $navigationIcon = 'fas-diagram-project';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $label = 'Proyecto';
    protected static ?string $pluralLabel = 'Proyectos';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Forms\Components\TextInput::make('clave')
                    ->required()
                    ->maxLength(255)
                    ->default(function () {
                        return Proyectos::where('team_id', Filament::getTenant()->id)->count() + 1;
                    })
                    ->readOnlyOn('edit'),
                Forms\Components\TextInput::make('descripcion')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Forms\Components\TextInput::make('compras')
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00)
                    ->currencyMask(decimalSeparator: '.', precision: 2),
                Forms\Components\TextInput::make('ventas')
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00)
                    ->currencyMask(decimalSeparator: '.', precision: 2),
                Forms\Components\Select::make('estado')
                    ->options([
                        'Activa' => 'Activa',
                        'Cerrada' => 'Cerrada',
                        'Cancelada' => 'Cancelada',
                    ])
                    ->default('Activa')
                    ->required(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('compras')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->prefix('$'),
                Tables\Columns\TextColumn::make('ventas')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->prefix('$'),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')->icon(null)
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left),
            ])
            ->headerActions([
                CreateAction::make('Agregar')
                    ->createAnother(false)
                    ->tooltip('Nuevo Proyecto')
                    ->label('Agregar')->icon('fas-circle-plus')->badge()
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left),
            ], HeaderActionsPosition::Bottom)
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
            'index' => Pages\ListProyectos::route('/'),
            //'create' => Pages\CreateProyecto::route('/create'),
            //'edit' => Pages\EditProyecto::route('/{record}/edit'),
        ];
    }
}
