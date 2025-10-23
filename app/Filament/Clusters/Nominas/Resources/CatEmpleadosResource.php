<?php

namespace App\Filament\Clusters\Nominas\Resources;

use App\Filament\Clusters\Nominas;
use App\Filament\Clusters\Nominas\Resources\CatEmpleadosResource\Pages;
use App\Filament\Clusters\Nominas\Resources\CatEmpleadosResource\RelationManagers;
use App\Models\CatEmpleados;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CatEmpleadosResource extends Resource
{
    protected static ?string $model = CatEmpleados::class;

    protected static ?string $navigationIcon = 'fas-users';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'Empleado';
    protected static ?string $pluralLabel = 'Empleados';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $cluster = Nominas::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->maxLength(255)->columnSpanFull(),
                Forms\Components\TextInput::make('rfc')
                    ->maxLength(255),
                Forms\Components\TextInput::make('curp')
                    ->maxLength(255),
                Forms\Components\TextInput::make('imss')
                    ->maxLength(255),
                Forms\Components\TextInput::make('estado')
                    ->maxLength(255)->readOnly()->default('Activo'),
                Forms\Components\TextInput::make('sueldo')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('ret_isr')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('ret_imss')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('subsidio')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\Hidden::make('team_id')
                ->default(Filament::getTenant()->id)
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rfc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('curp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('imss')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sueldo')
                    ->numeric(decimalPlaces: 2,decimalSeparator: '.')
                    ->prefix('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable()
            ])->actions([
           Tables\Actions\ActionGroup::make([
               Tables\Actions\EditAction::make()
                   ->icon('fas-edit')
                   ->label('Editar')
                   ->closeModalByEscaping(false)
                   ->modalWidth('6xl')
                   ->modalSubmitActionLabel('Aceptar')
                   ->modalCancelActionLabel('Cancelar')
               ])
               ])
               ->actionsPosition(Tables\Enums\ActionsPosition::BeforeCells)
               ->headerActions([
                   Tables\Actions\CreateAction::make()
                       ->icon('fas-circle-plus')
                       ->label('Agregar')
                       ->closeModalByEscaping(false)
                       ->createAnother(false)
                       ->modalWidth('6xl')
                       ->modalSubmitActionLabel('Aceptar')
                       ->modalCancelActionLabel('Cancelar'),
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
            'index' => Pages\ListCatEmpleados::route('/'),
            //'create' => Pages\CreateCatEmpleados::route('/create'),
            //'edit' => Pages\EditCatEmpleados::route('/{record}/edit'),
        ];
    }
}
