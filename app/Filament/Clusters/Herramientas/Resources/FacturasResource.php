<?php

namespace App\Filament\Clusters\Herramientas\Resources;

use App\Filament\Clusters\Herramientas;
use App\Filament\Clusters\Herramientas\Resources\FacturasResource\Pages;
use App\Filament\Clusters\Herramientas\Resources\FacturasResource\RelationManagers;
use App\Models\Facturas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class FacturasResource extends Resource
{
    protected static ?string $model = Facturas::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Herramientas::class;
    protected static bool $shouldRegisterNavigation = false;
    public static function scopeEloquentQueryToTenant(Builder $query, ?Model $tenant): Builder
    {
        return $query;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('serie')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('folio')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('docto')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('fecha')
                    ->required(),
                Forms\Components\TextInput::make('clie')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('esquema')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('iva')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('retiva')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('retisr')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('ieps')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\Textarea::make('observa')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('estado')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('metodo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('forma')
                    ->maxLength(255),
                Forms\Components\TextInput::make('uso')
                    ->maxLength(255),
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->maxLength(255),
                Forms\Components\TextInput::make('condiciones')
                    ->maxLength(255),
                Forms\Components\TextInput::make('vendedor')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('anterior')
                    ->numeric(),
                Forms\Components\TextInput::make('timbrado')
                    ->maxLength(255),
                Forms\Components\Textarea::make('xml')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('fecha_tim')
                    ->maxLength(255),
                Forms\Components\TextInput::make('moneda')
                    ->required()
                    ->maxLength(255)
                    ->default('MXN'),
                Forms\Components\TextInput::make('tcambio')
                    ->required()
                    ->numeric()
                    ->default(1.00000000),
                Forms\Components\DateTimePicker::make('fecha_cancela'),
                Forms\Components\TextInput::make('motivo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sustituye')
                    ->maxLength(255),
                Forms\Components\Textarea::make('xml_cancela')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('pendiente_pago')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\Select::make('team_id')
                    ->relationship('team', 'name'),
                Forms\Components\Textarea::make('error_timbrado')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serie')
                    ->searchable(),
                Tables\Columns\TextColumn::make('folio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('docto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clie')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('esquema')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('iva')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retiva')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('retisr')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ieps')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('metodo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('forma')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('condiciones')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendedor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('anterior')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('timbrado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_tim')
                    ->searchable(),
                Tables\Columns\TextColumn::make('moneda')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tcambio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_cancela')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('motivo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sustituye')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pendiente_pago')
                    ->numeric()
                    ->sortable(),
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
            ])->headerActions([
                Action::make('Actualizar Facturas')
                    ->icon('fas-sync')
                    ->action(function(){
                        $facturas = DB::table('facturas')->get();

                        //$facturas = Facturas::withoutGlobalScope('all')->get();
                        dd($facturas);
                    })
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
            'index' => Pages\ListFacturas::route('/'),
            'create' => Pages\CreateFacturas::route('/create'),
            'edit' => Pages\EditFacturas::route('/{record}/edit'),
        ];
    }
}
