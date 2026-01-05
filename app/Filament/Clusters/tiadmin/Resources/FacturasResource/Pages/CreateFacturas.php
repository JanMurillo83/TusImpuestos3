<?php

namespace App\Filament\Clusters\tiadmin\Resources\FacturasResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\FacturasResource;
use App\Models\Cotizaciones;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFacturas extends CreateRecord
{
    protected static string $resource = FacturasResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Actualizar estado de cotización si existe
        if ($record->cotizacion_id) {
            \App\Models\Cotizaciones::where('id', $record->cotizacion_id)->update(['estado' => 'Facturada']);
        }
        // Actualizar estado de pedido si existe
        if ($record->pedido_id) {
            \App\Models\Pedidos::where('id', $record->pedido_id)->update(['estado' => 'Facturado']);
        }
        // Actualizar estado de remisión si existe
        if ($record->remision_id) {
            \App\Models\Remisiones::where('id', $record->remision_id)->update(['estado' => 'Facturada']);
        }

        // Manejo de inventario: Solo si NO viene de una remisión (la remisión ya descuenta)
        if (!$record->remision_id) {
            foreach ($record->partidas as $partida) {
                $prod = \App\Models\Inventario::find($partida->item);
                if ($prod) {
                    $prod->exist -= $partida->cant;
                    $prod->save();
                }
            }
        }
    }
}
