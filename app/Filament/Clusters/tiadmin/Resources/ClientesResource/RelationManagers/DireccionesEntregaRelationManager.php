<?php

namespace App\Filament\Clusters\tiadmin\Resources\ClientesResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;

class DireccionesEntregaRelationManager extends RelationManager
{
    protected static string $relationship = 'direccionesEntrega';

    protected static ?string $recordTitleAttribute = 'nombre_sucursal';

    protected static ?string $title = 'Direcciones de Entrega';

    protected static ?string $label = 'Dirección de Entrega';

    protected static ?string $pluralLabel = 'Direcciones de Entrega';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre_sucursal')
                    ->label('Nombre de Sucursal')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\TextInput::make('calle')
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('no_exterior')
                            ->label('No. Exterior')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('no_interior')
                            ->label('No. Interior')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('colonia')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('municipio')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('estado')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('codigo_postal')
                            ->label('Código Postal')
                            ->maxLength(10),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('telefono')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contacto')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('es_principal')
                            ->label('Dirección Principal')
                            ->default(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->columns([
                Tables\Columns\TextColumn::make('nombre_sucursal')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('calle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('municipio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\IconColumn::make('es_principal')
                    ->label('Principal')
                    ->boolean()
                    ->trueIcon('fas-check-circle')
                    ->falseIcon('fas-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar')
                    ->icon('fas-circle-plus')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('fas-edit')
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('fas-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
