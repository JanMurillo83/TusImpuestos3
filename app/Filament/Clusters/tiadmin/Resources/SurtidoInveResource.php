<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\SurtidoInveResource\RelationManagers;
use App\Models\Facturas;
use App\Models\Inventario;
use App\Models\Movinventario;
use App\Models\SurtidoInve;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SurtidoInveResource extends Resource
{
    protected static ?string $model = SurtidoInve::class;

    protected static ?string $navigationIcon = 'fas-truck-loading';

    protected static ?string $label = 'Surtido';
    protected static ?string $pluralLabel = 'Surtidos';

    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('factura_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('factura_partida_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('item_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('descr')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cant')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('precio_u')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('costo_u')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('precio_total')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('costo_total')
                    ->required()
                    ->numeric()
                    ->default(0.00000000),
                Forms\Components\TextInput::make('team_id')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('factura_id')
                    ->getStateUsing(function ($record){
                        $fac = Facturas::where('id',$record->factura_id)->first();
                        return $fac->docto;
                    })->label('Factura'),
                Tables\Columns\TextColumn::make('descr')
                    ->searchable()->label('Descripcion'),
                Tables\Columns\TextColumn::make('cant')
                    ->numeric()->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->numeric()->label('Estado')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Surtir')
                ->icon('fas-truck-loading')
                ->label('Surtir')
                ->visible(function(SurtidoInve $record){
                    return $record->estado == 'Pendiente';
                })
                ->action(function (SurtidoInve $record){
                    $record->estado = 'Surtido';
                    $record->save();
                    Movinventario::insert([
                        'producto'=>$record->item_id,
                        'tipo'=>'Salida',
                        'fecha'=>Carbon::now(),
                        'cant'=>$record->cant,
                        'costo'=>$record->costo_u,
                        'precio'=>$record->precio_u,
                        'concepto'=>6,
                        'tipoter'=>'C'
                    ]);
                    Inventario::where('id',$record->item_id)->decrement('exist',$record->cant);
                    Notification::make()->title('Surtido')->success()->send();
                }),
                Tables\Actions\Action::make('Cancelar')
                    ->icon('fas-ban')
                    ->visible(function(SurtidoInve $record){
                        return $record->estado == 'Surtido';
                    })
                    ->action(function (SurtidoInve $record){
                        $record->estado = 'Pendiente';
                        $record->save();
                        Movinventario::insert([
                            'producto'=>$record->item_id,
                            'tipo'=>'Entrada',
                            'fecha'=>Carbon::now(),
                            'cant'=>$record->cant,
                            'costo'=>$record->costo_u,
                            'precio'=>$record->precio_u,
                            'concepto'=>5,
                            'tipoter'=>'C'
                        ]);
                        Inventario::where('id',$record->item_id)->increment('exist',$record->cant);
                        Notification::make()->title('Cancelado')->success()->send();
                    })

            ],Tables\Enums\ActionsPosition::BeforeCells);
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
            'index' => Pages\ListSurtidoInves::route('/'),
            'create' => Pages\CreateSurtidoInve::route('/create'),
            'edit' => Pages\EditSurtidoInve::route('/{record}/edit'),
        ];
    }
}
