<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TercerosResource\Pages;
use App\Filament\Resources\TercerosResource\RelationManagers;
use App\Models\Regimenes;
use App\Models\Terceros;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TercerosResource extends Resource
{
    protected static ?string $model = Terceros::class;
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $label = 'Tercero';
    protected static ?string $pluralLabel = 'Terceros';
    protected static ?string $navigationIcon = 'fas-users';
    public static function shouldRegisterNavigation () : bool
    {
        return auth()->user()->hasRole(['administrador','contador']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rfc')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(3),
                Forms\Components\Select::make('tipo')
                    ->label('Tipo de Tercero')
                    ->options(['Cliente'=>'Cliente','Proveedor'=>'Proveedor',
                    'Deudor'=>'Deudor','Acreedor'=>'Acreedor']),
                Forms\Components\TextInput::make('cuenta')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('telefono')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('correo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contacto')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('regimen')
                    ->searchable()
                    ->label('Regimen Fiscal')
                    ->columnSpan(2)
                    ->options(Regimenes::all()->pluck('mostrar','clave')),
                Forms\Components\Hidden::make('tax_id')
                    ->default(Filament::getTenant()->taxid),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
                Forms\Components\TextInput::make('codigopos')
                    ->label('Codigo Postal')
                    ->required()
                    ->maxLength(255),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->columns([
                Tables\Columns\TextColumn::make('rfc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cuenta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('team_id')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListTerceros::route('/'),
            //'create' => Pages\CreateTerceros::route('/create'),
            //'edit' => Pages\EditTerceros::route('/{record}/edit'),
        ];
    }
}
