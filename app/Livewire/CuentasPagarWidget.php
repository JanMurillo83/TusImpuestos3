<?php

namespace App\Livewire;

use App\Models\CuentasPagar;
use App\Models\Proveedores;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CuentasPagarWidget extends BaseWidget
{
    protected static ?string $heading = 'Cuentas por pagar por proveedor';
    public ?int $proveedor = null;

    public function mount($proveedor): void
    {
        $this->proveedor = $proveedor;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CuentasPagar::query()
                    ->where('team_id', Filament::getTenant()->id)
            )->modifyQueryUsing(function ($query) {
                return $query->where('proveedor',$this->proveedor);
            })
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('documento')->label('Documento')->searchable(),
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('vencimiento')->label('Vencimiento')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('importe')->label('Importe')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->prefix('$')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total importe')->money('MXN', locale: 'es_MX')->numeric(2, '.', ',')),
                Tables\Columns\TextColumn::make('saldo')->label('Saldo')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.')
                    ->prefix('$')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total saldo')->money('MXN', locale: 'es_MX')->numeric(2, '.', ',')),
            ])
            ->filters([
                SelectFilter::make('proveedor')
                    ->label('Proveedor')
                    ->options(
                        Proveedores::where('team_id', Filament::getTenant()->id)
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                    )
                    ->preload()
                    ->searchable(),
                Filter::make('rango_fechas')
                    ->label('Rango de fechas')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn ($q, $date) => $q->whereDate('fecha', '>=', Carbon::parse($date)))
                            ->when($data['hasta'] ?? null, fn ($q, $date) => $q->whereDate('fecha', '<=', Carbon::parse($date)));
                    }),
                Filter::make('solo_vencidas')
                    ->label('Solo vencidas')
                    ->toggle()
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? false) === true) {
                            return $query->whereDate('vencimiento', '<', Carbon::now());
                        }

                        return $query;
                    }),
            ])
            ->defaultSort('vencimiento', 'asc');
    }
}
