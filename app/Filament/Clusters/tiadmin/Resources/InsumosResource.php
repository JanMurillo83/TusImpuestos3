<?php

namespace App\Filament\Clusters\tiadmin\Resources;

use App\Filament\Clusters\tiadmin;
use App\Filament\Clusters\tiadmin\Resources\InsumosResource\Pages;
use App\Models\Claves;
use App\Models\Insumo;
use App\Models\InsumosSalida;
use App\Models\Lineasprod;
use App\Models\Unidades;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;

class InsumosResource extends Resource
{
    protected static ?string $model = Insumo::class;
    protected static ?string $navigationIcon = 'fas-boxes-stacked';
    protected static ?string $cluster = tiadmin::class;
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $label = 'Insumo';
    protected static ?string $pluralLabel = 'Insumos';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['administrador', 'contador', 'compras', 'ventas']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Hidden::make('team_id')->default(Filament::getTenant()->id),
                Fieldset::make('Generales')
                    ->schema([
                        Forms\Components\TextInput::make('clave')
                            ->label('Sku')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('descripcion')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpan(3),
                        Forms\Components\Select::make('linea')
                            ->options(Lineasprod::all()->pluck('descripcion', 'id'))
                            ->default(1),
                        Forms\Components\TextInput::make('marca')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('modelo')
                            ->maxLength(255),
                        Forms\Components\Select::make('servicio')
                            ->options(['SI' => 'SI', 'NO' => 'NO'])
                            ->default('NO')
                            ->required(),
                    ])->columns(4),
                Fieldset::make('Compras')
                    ->schema([
                        Forms\Components\TextInput::make('u_costo')
                            ->label('Ultimo Costo')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->required()
                            ->prefix('$')
                            ->numeric()
                            ->default(0.00000000),
                        Forms\Components\TextInput::make('p_costo')
                            ->label('Costo Promedio')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->prefix('$')
                            ->required()
                            ->numeric()
                            ->default(0.00000000),
                        Forms\Components\TextInput::make('exist')
                            ->label('Existencia')
                            ->readOnly()
                            ->numeric()
                            ->default(0.00000000),
                    ])->columns(3),
                Fieldset::make('Ventas')
                    ->visible(false)
                    ->schema([
                        Forms\Components\TextInput::make('precio1')
                            ->label('Precio Publico')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->prefix('$')
                            ->required()
                            ->numeric()
                            ->default(0.00000000),
                        Forms\Components\TextInput::make('precio2')
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->prefix('$')
                            ->numeric()
                            ->default(0.00000000),
                        Forms\Components\TextInput::make('precio3')
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->prefix('$')
                            ->numeric()
                            ->default(0.00000000),
                        Forms\Components\TextInput::make('precio4')
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->prefix('$')
                            ->numeric()
                            ->default(0.00000000),
                        Forms\Components\TextInput::make('precio5')
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->prefix('$')
                            ->numeric()
                            ->default(0.00000000),
                        Forms\Components\Select::make('esquema')
                            ->label('Esquema de Impuestos')
                            ->required()
                            ->options(DB::table('esquemasimps')->where('team_id', Filament::getTenant()->id)->pluck('descripcion', 'id'))
                            ->default(1),
                        Forms\Components\Select::make('unidad')
                            ->label('Unidad de Medida')
                            ->searchable()
                            ->required()
                            ->options(Unidades::all()->pluck('mostrar', 'clave'))
                            ->default('H87'),
                        Forms\Components\TextInput::make('cvesat')
                            ->label('Clave SAT')
                            ->default('01010101')
                            ->required()
                            ->suffixAction(
                                Action::make('Cat_cve_sat')
                                    ->label('Buscador')
                                    ->icon('fas-circle-question')
                                    ->form([
                                        Forms\Components\Select::make('CatCveSat')
                                            ->default(function (Get $get): string {
                                                if ($get('cvesat')) {
                                                    $val = $get('cvesat');
                                                } else {
                                                    $val = '01010101';
                                                }
                                                return $val;
                                            })
                                            ->label('Claves SAT')
                                            ->searchable()
                                            ->searchDebounce(100)
                                            ->getSearchResultsUsing(fn (string $search): array => Claves::where('mostrar', 'like', "%{$search}%")->limit(50)->pluck('mostrar', 'clave')->toArray()),
                                    ])
                                    ->modalCancelAction(false)
                                    ->modalSubmitActionLabel('Seleccionar')
                                    ->modalWidth('sm')
                                    ->action(function (Set $set, $data) {
                                        $set('cvesat', $data['CatCveSat']);
                                    })
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses('row_gral')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 'all'])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('linea')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function (Model $record) {
                        $lin = $record->linea;
                        $linea = Lineasprod::where('id', $lin)->get();
                        $linea = $linea[0];
                        return $linea->descripcion;
                    }),
                Tables\Columns\TextColumn::make('marca')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('precio1')
                    ->label('Precio Publico')
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exist')
                    ->label('Existencia')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon(null)
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left),
                Tables\Actions\Action::make('SalidaInsumo')
                    ->label('Salida')
                    ->icon('fas-arrow-right')
                    ->modalSubmitActionLabel('Guardar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left)
                    ->form([
                        DatePicker::make('fecha')
                            ->required()
                            ->default(Carbon::now()),
                        Forms\Components\TextInput::make('cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(0.00000001),
                        Textarea::make('observaciones')
                            ->rows(3),
                    ])
                    ->action(function (Model $record, array $data) {
                        $cantidad = (float) $data['cantidad'];
                        if ($cantidad <= 0) {
                            Notification::make()
                                ->title('La cantidad debe ser mayor a cero.')
                                ->danger()
                                ->send();
                            return;
                        }
                        if ($record->exist < $cantidad) {
                            Notification::make()
                                ->title('La cantidad excede la existencia.')
                                ->danger()
                                ->send();
                            return;
                        }
                        DB::transaction(function () use ($record, $data, $cantidad) {
                            InsumosSalida::create([
                                'insumo_id' => $record->id,
                                'cantidad' => $cantidad,
                                'fecha' => $data['fecha'],
                                'user_id' => auth()->id(),
                                'observaciones' => $data['observaciones'] ?? null,
                                'team_id' => Filament::getTenant()->id,
                            ]);
                            Insumo::where('id', $record->id)->decrement('exist', $cantidad);
                        });
                        Notification::make()
                            ->title('Salida registrada.')
                            ->success()
                            ->send();
                    }),
            ], Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([
                CreateAction::make('Agregar')
                    ->createAnother(false)
                    ->tooltip('Nuevo Insumo')
                    ->label('Agregar')
                    ->icon('fas-circle-plus')
                    ->badge()
                    ->modalSubmitActionLabel('Grabar')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Green)->icon('fas-save'))
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color(Color::Red)->icon('fas-ban'))
                    ->modalFooterActionsAlignment(Alignment::Left),
            ], HeaderActionsPosition::Bottom)
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
            'index' => Pages\ListInsumos::route('/'),
            //'create' => Pages\CreateInsumo::route('/create'),
            //'edit' => Pages\EditInsumo::route('/{record}/edit'),
        ];
    }
}
