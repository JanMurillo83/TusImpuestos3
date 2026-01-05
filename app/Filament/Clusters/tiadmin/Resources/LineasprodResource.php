<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\LineasprodResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\LineasprodResource\RelationManagers;
use App\Models\Lineasprod;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction as ActionsCreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Hidden;
use Filament\Facades\Filament;

class LineasprodResource extends Resource
{
    protected static ?string $model = Lineasprod::class;
    protected static ?string $navigationIcon = 'fas-list-ol';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $label = 'Linea';
    protected static ?string $pluralLabel = 'Lineas';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Forms\Components\TextInput::make('clave')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('descripcion')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable()
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
                ActionsCreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nuevo Proveedor')
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
            'index' => Pages\ListLineasprods::route('/'),
            //'create' => Pages\CreateLineasprod::route('/create'),
            //'edit' => Pages\EditLineasprod::route('/{record}/edit'),
        ];
    }
}
