<?php

namespace App\Filament\Clusters\tiadmin\Resources\PedidosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\PedidosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }

    public function callImprimir($record)
    {

        $tabla = $this->getTable();
        $tabla->getAction('Imprimir_Doc')->visible(true);
        $this->replaceMountedTableAction('Imprimir_Doc');
        $tabla->getAction('Imprimir_Doc')->visible(false);
    }
}
