<?php

namespace App\Filament\Clusters\AdmVentas\Resources\CotizacionesResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\CotizacionesResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCotizaciones extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = CotizacionesResource::class;

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
