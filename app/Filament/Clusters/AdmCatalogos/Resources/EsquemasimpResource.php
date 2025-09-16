<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources;

use App\Filament\Clusters\AdmCatalogos;
use App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource\Pages;
use App\Filament\Clusters\AdmCatalogos\Resources\EsquemasimpResource\RelationManagers;
use App\Models\Esquemasimp;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EsquemasimpResource extends Resource
{
    protected static ?string $model = Esquemasimp::class;

    protected static ?string $navigationIcon = 'fas-percent';

    protected static ?string $cluster = AdmCatalogos::class;
    protected static ?int $navigationSort = 5;
    protected static ?string $label = 'Esquema de Impuestos';
    protected static ?string $pluralLabel = 'Esquemas de Impuestos';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('clave')
                    ->required()->readOnly()
                    ->maxLength(255)
                    ->default(Esquemasimp::where('team_id',Filament::getTenant()->id)->max('clave') + 1),
                Forms\Components\TextInput::make('descripcion')
                    ->required()
                    ->maxLength(255)->columnSpan(3),
                Forms\Components\TextInput::make('iva')
                    ->required()->label('IVA')
                    ->numeric()
                    ->default(0.00000000)->live(onBlur: true),
                Forms\Components\TextInput::make('retiva')
                    ->required()->label('Ret. IVA')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('retisr')
                    ->required()->label('Ret. ISR')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('ieps')
                    ->required()->label('IEPS')
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\Select::make('exento')
                    ->options(['SI'=>'SI','NO'=>'NO'])
                    ->default('NO')
                    ->disabled(function (Forms\Get $get){
                        if($get('iva') > 0) return true;
                        else return false;
                    })->reactive(),
                Forms\Components\Hidden::make('team_id')
                    ->default(Filament::getTenant()->id),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('iva')
                    ->numeric()->label('IVA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('retiva')
                    ->numeric()->label('Ret. IVA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('retisr')
                    ->numeric()->label('Ret. ISR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ieps')
                    ->numeric()->label('IEPS')
                    ->sortable()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label('Agregar')->icon('fas-circle-plus')
            ],HeaderActionsPosition::Bottom);
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
            'index' => Pages\ListEsquemasimps::route('/'),
            //'create' => Pages\CreateEsquemasimp::route('/create'),
            //'edit' => Pages\EditEsquemasimp::route('/{record}/edit'),
        ];
    }
}
