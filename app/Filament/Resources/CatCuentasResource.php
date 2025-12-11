<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatCuentasResource\Pages;
use App\Filament\Resources\CatCuentasResource\RelationManagers;
use App\Models\CatCuentas;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Navigation\NavigationGroup;
use Illuminate\Database\Eloquent\Model;

class CatCuentasResource extends Resource
{
    protected static ?string $model = CatCuentas::class;
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $label = 'Cuenta Contable';
    protected static ?string $pluralLabel = 'Cuentas Contables';
    protected static ?string $navigationIcon ='fas-list-ol';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('codigo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('nombre')
                    ->maxLength(255)
                    ->columnSpan(3),
                Forms\Components\TextInput::make('acumula')
                    ->maxLength(255),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'D'=>'Detalle',
                        'A'=>'Acumulativa'
                    ]),
                Forms\Components\Select::make('naturaleza')
                    ->required()
                    ->options([
                        'D'=>'Deudora',
                        'A'=>'Acreedora'
                    ]),
                Forms\Components\TextInput::make('csat')
                    ->label('Clave SAT')
                    ->maxLength(255),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable()
                    //->action(fn (Model $record,$livewire) => $livewire->selectRecord($record->id))
                    ->sortable()
                    ->formatStateUsing(function ($record){
                        $sr1 = substr($record->codigo, 0, 3);
                        $sr2 = substr($record->codigo, 3, 2);
                        $sr3 = substr($record->codigo, 5, 3);
                        return $sr1.'-'.$sr2.'-'.$sr3;
                    }),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable()
                    ->getStateUsing( function (Model $record){
                        $curr = $record['tipo'];
                        $neww = $curr;
                        if($curr == 'D') $neww = 'Detalle';
                        if($curr == 'A') $neww = 'Acumulativa';
                        return $neww;
                 }),
                Tables\Columns\TextColumn::make('naturaleza')
                    ->getStateUsing( function (Model $record){
                        $curr = $record['naturaleza'];
                        $neww = $curr;
                        if($curr == 'D') $neww = 'Deudora';
                        if($curr == 'A') $neww = 'Acreedora';
                        return $neww;
                 }),
                Tables\Columns\TextColumn::make('csat')
                    ->label('Clave SAT')
                    ->searchable()
            ])
            ->defaultSort('codigo', 'asc')
            ->striped()->defaultPaginationPageOption(8)
            ->paginated([8, 'all'])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->iconButton()
                ->icon('fas-edit'),
                Tables\Actions\DeleteAction::make()
                ->iconButton()->requiresConfirmation()
                ->icon('fas-trash'),
            ],Tables\Enums\ActionsPosition::BeforeColumns)
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
            'index' => Pages\ListCatCuentas::route('/'),
            //'create' => Pages\CreateCatCuentas::route('/create'),
            //'edit' => Pages\EditCatCuentas::route('/{record}/edit'),
        ];
    }
}
