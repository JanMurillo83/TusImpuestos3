<?php

namespace App\Livewire;

use App\Filament\Resources\MovbancosResource;
use App\Models\Almacencfdis;
use App\Models\IngresosEgresos;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FacturasEgWidget extends BaseWidget
{
    public function table(Table $table): Table
    {
        $ing_ret = IngresosEgresos::where('team_id',
            Filament::getTenant()->id)
            ->where('tipo',0)->where('pendientemxn','>',0)->get();
        $ids = '';
        $las_ids = '';
        foreach ($ing_ret as $ing) {
            $ids.=strval($ing->xml_id).',';
            $las_ids=strval($ing->xml_id);
        }
        $ids.=strval($las_ids);
        $ids = explode(',',$ids);
        //dd($ids);
        return $table
            ->query(
                Almacencfdis::whereIn('id',$ids)->where('xml_type','Recibidos')
            )
            ->heading('Seleccionar Factura')
            ->columns([
                Tables\Columns\TextColumn::make('Emisor_Nombre')->label('Emisor')->searchable(),
                Tables\Columns\TextColumn::make('Emisor_Rfc')->label('RFC')->searchable(),
                Tables\Columns\TextColumn::make('Fecha')->label('Fecha')->searchable()->date('d-m-Y'),
                Tables\Columns\TextColumn::make('Total')->label('Importe')->searchable()->numeric(decimalPlaces: 2,decimalSeparator: '.')->prefix('$'),
                Tables\Columns\TextColumn::make('Moneda')->label('Moneda'),
                Tables\Columns\TextColumn::make('TipoCambio')->label('Tipo de Cambio')->numeric(decimalPlaces: 2,decimalSeparator: '.')->prefix('$'),
            ])
            ->actions([
                Tables\Actions\Action::make('Seleccionar')
                ->color('primary')->label('')->icon('fas-check')
                ->button()
                ->action(function($record){
                    MovbancosResource::setFactData($record);
                    self::dispatch('closeModal');
                    RawJs::make('window.close');
                })
            ]);
    }
}
