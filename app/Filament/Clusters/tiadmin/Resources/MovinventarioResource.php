<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\MovinventarioResource\Pages;
use App\Filament\Clusters\tiadmin\Resources\MovinventarioResource\RelationManagers;
use App\Models\Conceptosmi;
use App\Models\Inventario;
use App\Models\Movinventario;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class MovinventarioResource extends Resource
{
    protected static ?string $model = Movinventario::class;
    protected static ?string $navigationIcon = 'fas-dolly';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $label = 'Movimiento';
    protected static ?string $pluralLabel = 'Movimientos al inventario';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Forms\Components\Select::make('producto')
                    ->required()
                    ->searchable()
                    ->options(
                        DB::table('inventarios')->where('team_id',Filament::getTenant()->id)->where('servicio','NO')->select(DB::raw("CONCAT(clave,' ',descripcion) as Producto"),'id')->pluck('Producto','id')
                    )->columnSpan(3),
                Forms\Components\DatePicker::make('fecha')
                    ->required()
                    ->default(Carbon::now()),
                Forms\Components\Select::make('tipo')
                    ->label('Tipo de Movimiento')
                    ->required()
                    ->live()
                    ->options(['Entrada'=>'Entrada','Salida'=>'Salida']),
                Forms\Components\Select::make('concepto')
                    ->required()
                    ->live()
                    ->options(function(Get $get){
                        if($get('tipo') == 'Entrada') return Conceptosmi::where('tipo','Entrada')->pluck('descripcion','id');
                        if($get('tipo') == 'Salida') return Conceptosmi::where('tipo','Salida')->pluck('descripcion','id');
                    })
                    ->afterStateUpdated(function(Get $get,Set $set){
                        $concep = Conceptosmi::where('id',$get('concepto'))->get();
                        $concep = $concep[0];
                        $set('tipoter',$concep->tercero);
                    })
                    ->columnSpan(2),
                Forms\Components\TextInput::make('cant')
                    ->label('Cantidad')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('costo')
                    ->required()
                    ->numeric()
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->default(0.00),
                Forms\Components\TextInput::make('precio')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 2)
                    ->prefix('$')
                    ->default(0.00),
                Forms\Components\Hidden::make('tipoter')
                    ->default('N'),
                Forms\Components\Select::make('tercero')
                    ->visible(function(Get $get){
                        if($get('tipoter') == 'C'||$get('tipoter') == 'P') return true;
                        else return false;
                    })->options(function(Get $get){
                        if($get('tipoter') == 'C') return DB::table('clientes')->select(DB::raw("CONCAT(clave,' ',nombre) as Cliente"),'id')->pluck('Cliente','id');
                        if($get('tipoter') == 'P') return DB::table('proveedores')->select(DB::raw("CONCAT(clave,' ',nombre) as Cliente"),'id')->pluck('Cliente','id');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5,'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('producto')
                    ->formatStateUsing(function(Model $record){
                        $inve = Inventario::where('id',$record->producto)->first();
                        return $inve?->descripcion ?? 'Sin descripcion';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha')
                ->date('d-m-Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cant')
                ->label('Cantidad')
                    ->numeric(decimalPlaces:2,decimalSeparator:'.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('costo')
                    ->numeric()
                    ->currency('USD',true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio')
                    ->numeric()
                    ->currency('USD',true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('concepto')
                    ->searchable()
                    ->formatStateUsing(function(Model $record){
                        $inve = Conceptosmi::where('id',$record->concepto)->get();
                        $inve = $inve[0];
                        return $inve->descripcion;
                })
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                ->label('')->icon(null)
                //->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                //->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left),
            ])
            ->headerActions([
                CreateAction::make('Agregar')
                ->createAnother(false)
                ->tooltip('Nuevo Producto')
                ->label('Agregar')->icon('fas-circle-plus')->badge()
                ->modalSubmitActionLabel('Grabar')
                ->modalCancelActionLabel('Cerrar')
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                ->modalFooterActionsAlignment(Alignment::Left)
                ->after(function($record){
                    $arti = $record->producto;
                    $tipo = $record->tipo;
                    $cant = 0;
                    $cost = $record->costo;
                    $inve = Inventario::where('id',$arti)->get();
                    $inve = $inve[0];
                    if($tipo == 'Entrada') $cant = $inve->exist + $record->cant;
                    else $cant = $inve->exist - $record->cant;

                    $avg = $inve->p_costo * $inve->exist;
                    $avgp = 0;
                    if($avg == 0) $avgp = $cost;
                    else $avgp = (($inve->p_costo + $cost) * ($inve->exist + $cant)) / ($inve->exist + $cant);
                    Inventario::where('id',$arti)->update([
                        'exist' => $cant,
                        'u_costo'=>$cost,
                        'p_costo'=>$avgp
                    ]);
                })
            ],HeaderActionsPosition::Bottom)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListMovinventarios::route('/'),
            //'create' => Pages\CreateMovinventario::route('/create'),
            //'edit' => Pages\EditMovinventario::route('/{record}/edit'),
        ];
    }
}
