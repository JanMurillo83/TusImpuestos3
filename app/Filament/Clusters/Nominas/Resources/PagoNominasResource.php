<?php

namespace App\Filament\Clusters\Nominas\Resources;

use App\Filament\Clusters\Nominas;
use App\Filament\Clusters\Nominas\Resources\PagoNominasResource\Pages;
use App\Filament\Clusters\Nominas\Resources\PagoNominasResource\RelationManagers;
use App\Models\PagoNominas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PagoNominasResource extends Resource
{
    protected static ?string $model = PagoNominas::class;

    protected static ?string $navigationIcon = 'fas-wallet';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Pago de Nomina';
    protected static ?string $pluralLabel = 'Pago de Nominas';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $cluster = Nominas::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('fecha')
                    ->required(),
                Forms\Components\TextInput::make('nonom')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('tipo')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('fecha_pa')
                    ->required(),
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
                Forms\Components\TextInput::make('otras_per')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('otras_ded')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('importe')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('movban')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('estado')
                    ->maxLength(255),
                Forms\Components\Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nonom')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_pa')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sueldo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ret_isr')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ret_imss')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subsidio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('otras_per')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('otras_ded')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('movban')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPagoNominas::route('/'),
            'create' => Pages\CreatePagoNominas::route('/create'),
            'edit' => Pages\EditPagoNominas::route('/{record}/edit'),
        ];
    }
}
