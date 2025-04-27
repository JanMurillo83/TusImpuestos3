<?php

namespace App\Livewire;

use App\Filament\Resources\MovbancosResource;
use App\Models\IngresosEgresos;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class IngEgWidget extends BaseWidget
{
    public ?array $selected_records = [];
    public function mount(array $arreglo): void
    {
        $this->selected_records = $arreglo;
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                IngresosEgresos::query()->where('team_id',Filament::getTenant()->id)
                ->where('tipo',0)
            )
            ->columns([
                Tables\Columns\TextColumn::make('referencia'),
                Tables\Columns\TextColumn::make('totalmxn')
                    ->label('Importe del Comprobante')
                    ->prefix('$')->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('pendientemxn')
                    ->label('Saldo Pendiente')
                    ->prefix('$')->numeric(decimalPlaces: 2),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('Seleccionar')
                ->color(Color::rgb('rgb(255, 255, 255)'))->label('')
                ->action(function(Collection $records){
                    //$this->selected_records = $records->toArray();
                    //dd($this->selected_records);
                    dd($this->selected_records);
                })
            ]);
    }
}
