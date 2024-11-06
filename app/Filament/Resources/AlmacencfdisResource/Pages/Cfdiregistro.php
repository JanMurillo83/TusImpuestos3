<?php

namespace App\Filament\Resources\AlmacencfdisResource\Pages;

use App\Filament\Resources\AlmacencfdisResource;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AlmacencfdisResource\Pages;
use App\Filament\Resources\AlmacencfdisResource\RelationManagers;
use App\Models\Almacencfdis;
use App\Models\CatCuentas;
use App\Models\Terceros;
use App\Models\CatPolizas;
use Filament\Actions\Action as ActionsAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Enums\MaxWidth;
use App\Http\Controllers\Funciones;
use App\Models\Auxiliares;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Table;

class Cfdiregistro extends Page
{
    protected static string $resource = AlmacencfdisResource::class;
    protected static ?string $model = Almacencfdis::class;
    protected static ?string $navigationGroup = 'CFDI';
    protected static ?string $pluralLabel = 'Registro CFDI';
    protected static ?string $label = 'CFDI';

    protected static string $view = 'filament.resources.almacencfdis-resource.pages.cfdiregistro';
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Serie')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Folio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Fecha')
                    ->searchable()
                    ->sortable()
                    ->date('d-m-Y'),
                Tables\Columns\TextColumn::make('Moneda')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('TipoDeComprobante')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Emisor_Rfc')
                    ->label('RFC Emisor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Emisor_Nombre')
                    ->label('Nombre Emisor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Receptor_Rfc')
                    ->label('RFC Receptor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Receptor_Nombre')
                    ->label('Nombre Receptor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('UUID')
                    ->label('UUID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Total')
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('used')
                    ->label('Utilizado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('xml_type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ejercicio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->numeric()
                    ->sortable()
            ])
            ->filters([
                SelectFilter::make('ejercicio')
                ->options(['2020'=>'2020','2021'=>'2021','2022'=>'2022','2023'=>'2023','2024'=>'2024','2025'=>'2025','2026'=>'2026'])
                ->attribute('ejercicio'),
                SelectFilter::make('periodo')
                ->options(['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio','7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'])
                ->attribute('periodo'),
                Filter::make('xml_type_1')
                ->label('Emitidos')
                ->query(fn (Builder $query): Builder => $query->where('xml_type', 'Emitidos')),
                Filter::make('xml_type_2')
                ->label('Recibidos')
                ->query(fn (Builder $query): Builder => $query->where('xml_type', 'Recibidos')),
                /*Filter::make('TipoDeComprobante_1')
                ->label('Ingresos')
                ->query(fn (Builder $query): Builder => $query->where('TipoDeComprobante', 'I')),
                Filter::make('TipoDeComprobante_2')
                ->label('Nomina')
                ->query(fn (Builder $query): Builder => $query->where('TipoDeComprobante', 'N')),
                Filter::make('TipoDeComprobante_3')
                ->label('Pagos')
                ->query(fn (Builder $query): Builder => $query->where('TipoDeComprobante', 'P'))*/
            ])
            ->actions([
                Action::make('ContabilizarE')
                    ->label('')
                    ->visible(fn ($record) => $record->xml_type == 'Emitidos')
                    ->tooltip('Contabilizar')
                    ->icon('fas-scale-balanced')
                    ->modalWidth(MaxWidth::ExtraSmall)
                    ->form([
                        Forms\Components\Select::make('forma')
                            ->label('Forma de Pago')
                            ->options([
                                'Bancario'=>'Cuentas por Cobrar',
                                'Efectivo'=>'Efectivo'
                            ])
                            ->default('Bancario')
                            ->disabled()
                            ->required()
                    ])
                    ->action(function(Model $record,$data){
                        Self::contabiliza_e($record,$data);
                    }),
                    Action::make('ContabilizarR')
                    ->label('')
                    ->visible(fn ($record) => $record->xml_type == 'Recibidos')
                    ->tooltip('Contabilizar')
                    ->icon('fas-scale-balanced')
                    ->modalWidth(MaxWidth::ExtraSmall)
                    ->form([
                        Forms\Components\Select::make('rubrogas')
                            ->label('Rubro del Gasto')
                            ->required()
                            ->live()
                            ->options([
                               '50100000' => 'Costo de Ventas',
                               '60200000' => 'Gastos de Venta',
                               '60300000' => 'Gastos de Administracion',
                               '70100000' => 'Gastos Financieros',
                               '70200000' => 'Productos Financieros'
                            ]),
                        Forms\Components\Select::make('detallegas')
                            ->label('Rubro del Gasto')
                            ->required()
                            ->options(function(Get $get) {
                                return
                                CatCuentas::where('acumula',$get('rubrogas'))->pluck('nombre','codigo');
                            }),
                        Forms\Components\Select::make('forma')
                            ->label('Forma de Pago')
                            ->options([
                                'Bancario'=>'Movimiento Bancario',
                                'Efectivo'=>'Efectivo'
                            ])
                            ->required()
                    ])
                    ->action(function(Model $record,$data){
                        Self::contabiliza_r($record,$data);
                    }),
            ])
            ->actionsPosition(ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Action::make('Contabilizar')
                    ->label('Contabilizar')
                    ->tooltip('Contabilizar')
                    ->icon('fas-scale-balanced')
                ]),
            ]);
    }
}
