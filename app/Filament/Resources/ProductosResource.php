<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductosResource\Pages;
use App\Filament\Resources\ProductosResource\RelationManagers;
use App\Models\BuscaSat;
use App\Models\Productos;
use App\Models\UnidProd;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductosResource extends Resource
{
    protected static ?string $model = Productos::class;
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?string $label = 'Producto';
    protected static ?string $pluralLabel = 'Productos';
    protected static ?string $navigationIcon = 'fas-box';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('team_id')
                ->default(Filament::getTenant()->id),
                Forms\Components\TextInput::make('clave')
                    ->label('Identificacion')
                    ->maxLength(255),
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Forms\Components\Select::make('unidad')
                    ->label('unidad de Medida')
                    ->searchable()
                    ->options(UnidProd::all()->pluck('descripcion','unidad')),
                Forms\Components\TextInput::make('clavesat')
                ->label('Clave SAT')
                ->default('01010101')
                ->required()
                ->suffixAction(
                    Action::make('Cat_cve_sat')
                        ->label('Buscador')
                        ->icon('fas-circle-question')
                        ->form([
                            Forms\Components\Select::make('CatCveSat')
                                ->default(function(Get $get): string{
                                    if($get('cvesat'))
                                        $val = $get('cvesat');
                                    else
                                        $val = '01010101';
                                    return $val;
                                })
                        ->label('Claves SAT')
                        ->searchable()
                        ->searchDebounce(100)
                        ->getSearchResultsUsing(fn (string $search): array => BuscaSat::where('nombre', 'like', "%{$search}%")->limit(50)->pluck('nombre', 'clave')->toArray())
                    ])
                        ->modalCancelAction(false)
                        ->modalSubmitActionLabel('Seleccionar')
                        ->modalWidth('sm')
                        ->action(function(Set $set,$data){
                            $set('clavesat',$data['CatCveSat']);
                     })),
                Forms\Components\TextInput::make('existencia')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('precio')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('costo_u')
                    ->label('Ultimo Costo')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('costo_p')
                    ->label('Costo Promedio')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('codigo')
                    ->maxLength(255)
                    ->default('11501000')
                    ->label('Cuenta Contable'),
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
                Tables\Columns\TextColumn::make('unidad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('clavesat')
                    ->searchable()
                    ->label('Clave Sat'),
                Tables\Columns\TextColumn::make('existencia')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('costo_u')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('costo_p')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable()
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
            'index' => Pages\ListProductos::route('/'),
            //'create' => Pages\CreateProductos::route('/create'),
            //'edit' => Pages\EditProductos::route('/{record}/edit'),
        ];
    }
}
