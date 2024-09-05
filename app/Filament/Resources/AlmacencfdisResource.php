<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlmacencfdisResource\Pages;
use App\Filament\Resources\AlmacencfdisResource\RelationManagers;
use App\Models\Almacencfdis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AlmacencfdisResource extends Resource
{
    protected static ?string $model = Almacencfdis::class;
    protected static ?string $navigationGroup = 'CFDI';
    protected static ?string $pluralLabel = 'Almacen CFDI';
    protected static ?string $label = 'CFDI';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('Serie')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Folio')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Version')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Fecha')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Moneda')
                    ->maxLength(255),
                Forms\Components\TextInput::make('TipoDeComprobante')
                    ->maxLength(255),
                Forms\Components\TextInput::make('MetodoPago')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Emisor_Rfc')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Emisor_Nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Emisor_RegimenFiscal')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Receptor_Rfc')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Receptor_Nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Receptor_RegimenFiscal')
                    ->maxLength(255),
                Forms\Components\TextInput::make('UUID')
                    ->maxLength(255),
                Forms\Components\TextInput::make('Total')
                    ->numeric(),
                Forms\Components\TextInput::make('SubTotal')
                    ->numeric(),
                Forms\Components\TextInput::make('TipoCambio')
                    ->numeric(),
                Forms\Components\TextInput::make('TotalImpuestosTrasladados')
                    ->numeric(),
                Forms\Components\TextInput::make('TotalImpuestosRetenidos')
                    ->numeric(),
                Forms\Components\Textarea::make('content')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('user_tax')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('used')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('xml_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('metodo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ejercicio')
                    ->numeric(),
                Forms\Components\TextInput::make('periodo')
                    ->numeric(),
                Forms\Components\TextInput::make('team_id')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Serie')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Folio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Fecha')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Moneda')
                    ->searchable(),
                Tables\Columns\TextColumn::make('TipoDeComprobante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Emisor_Rfc')
                    ->label('RFC Emisor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Emisor_Nombre')
                    ->label('Nombre Emisor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Receptor_Rfc')
                    ->label('RFC Receptor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Receptor_Nombre')
                    ->label('Nombre Receptor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('UUID')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Total')
                    ->currency()
                    ->sortable(),
                Tables\Columns\TextColumn::make('used')
                    ->label('Utilizado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('xml_type')
                    ->label('Tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
            ])
            ->filters([
                //
            ])
            ->actions([

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

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
            'index' => Pages\ListAlmacencfdis::route('/'),
            'create' => Pages\CreateAlmacencfdis::route('/create'),
            'edit' => Pages\EditAlmacencfdis::route('/{record}/edit'),
        ];
    }
}
