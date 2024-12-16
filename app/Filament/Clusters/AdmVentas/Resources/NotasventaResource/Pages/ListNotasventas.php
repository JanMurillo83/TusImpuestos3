<?php

namespace App\Filament\Clusters\AdmVentas\Resources\NotasventaResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\NotasventaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotasventas extends ListRecords
{
    protected static string $resource = NotasventaResource::class;

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
