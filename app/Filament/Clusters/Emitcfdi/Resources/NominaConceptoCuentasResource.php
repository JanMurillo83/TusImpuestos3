<?php

namespace App\Filament\Clusters\Emitcfdi\Resources;

use App\Filament\Clusters\Emitcfdi;
use App\Filament\Clusters\Emitcfdi\Resources\NominaConceptoCuentasResource\Pages;
use App\Models\CatCuentas;
use App\Models\NominaConceptoCuenta;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class NominaConceptoCuentasResource extends Resource
{
    protected static ?string $model = NominaConceptoCuenta::class;
    protected static ?string $navigationIcon = 'fas-list-check';
    protected static ?int $navigationSort = 10;
    protected static ?string $label = 'Cuenta Nomina';
    protected static ?string $pluralLabel = 'Cuentas Nomina';
    protected static ?string $navigationGroup = 'Configuracion';

    protected static ?string $cluster = Emitcfdi::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'PERCEPCION' => 'Percepcion',
                        'DEDUCCION' => 'Deduccion',
                        'OTRO_PAGO' => 'Otro Pago',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('codigo_sat')
                    ->label('Codigo SAT')
                    ->maxLength(20),
                Forms\Components\TextInput::make('clave')
                    ->label('Clave')
                    ->maxLength(20),
                Forms\Components\TextInput::make('descripcion')
                    ->label('Descripcion')
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Select::make('cat_cuentas_id')
                    ->label('Cuenta Contable')
                    ->searchable()
                    ->required()
                    ->options(
                        CatCuentas::where('team_id', Filament::getTenant()->id)
                            ->select(DB::raw("CONCAT(codigo,' - ',nombre) as cuenta,id"))
                            ->pluck('cuenta', 'id')
                    ),
                Forms\Components\Select::make('naturaleza')
                    ->label('Naturaleza')
                    ->options([
                        'D' => 'Cargo',
                        'A' => 'Abono',
                    ])
                    ->required()
                    ->default('D'),
                Forms\Components\Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codigo_sat')
                    ->label('Codigo SAT')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clave')
                    ->label('Clave')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripcion')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('catCuenta.codigo')
                    ->label('Cuenta')
                    ->sortable(),
                Tables\Columns\TextColumn::make('catCuenta.nombre')
                    ->label('Nombre Cuenta')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('naturaleza')
                    ->label('Nat.')
                    ->sortable(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->icon('fas-edit')
                        ->label('Editar')
                        ->closeModalByEscaping(false)
                        ->modalWidth('6xl')
                        ->modalSubmitActionLabel('Aceptar')
                        ->modalCancelActionLabel('Cancelar'),
                ]),
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
            ], Tables\Actions\HeaderActionsPosition::Bottom);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('team_id', Filament::getTenant()->id);
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
            'index' => Pages\ListNominaConceptoCuentas::route('/'),
            //'create' => Pages\CreateNominaConceptoCuentas::route('/create'),
            //'edit' => Pages\EditNominaConceptoCuentas::route('/{record}/edit'),
        ];
    }
}
