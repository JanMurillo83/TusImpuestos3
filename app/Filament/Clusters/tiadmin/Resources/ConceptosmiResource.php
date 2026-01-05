<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\ConceptosmiResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\ConceptosmiResource\RelationManagers;
use App\Models\Conceptosmi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConceptosmiResource extends Resource
{
    protected static ?string $model = Conceptosmi::class;
    protected static ?string $navigationIcon = 'fas-square-check';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $label = 'Concepto';
    protected static ?string $pluralLabel = 'Conceptos de Movimiento';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('clave')
                    ->required()
                    ->maxLength(255)
                    ->readOnlyOn('edit'),
                Forms\Components\TextInput::make('descripcion')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Forms\Components\Select::make('tipo')
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->options(['Entrada'=>'Entrada','Salida'=>'Salida'])
                    ->afterStateUpdated(function(Get $get, Set $set){
                        if($get('tipo') == 'Entrada') $set('signo',1);
                        else $set('signo',-1);
                    })->default('Entrada'),
                Forms\Components\Select::make('tercero')
                    ->required()
                    ->disabledOn('edit')
                    ->options(['C'=>'Cliente','P'=>'Proveedor','N'=>'Ninguno'])
                    ->default('N'),
                Forms\Components\Hidden::make('signo')
                    ->default(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
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
                ->tooltip('Nuevo Producto')
                ->label('Agregar')->icon('fas-circle-plus')->badge()
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
            ],HeaderActionsPosition::Bottom)
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
            'index' => Pages\ListConceptosmis::route('/'),
            //'create' => Pages\CreateConceptosmi::route('/create'),
            //'edit' => Pages\EditConceptosmi::route('/{record}/edit'),
        ];
    }
}
