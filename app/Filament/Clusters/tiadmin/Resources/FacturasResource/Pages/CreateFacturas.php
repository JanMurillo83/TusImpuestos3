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
        $state = $this->form->getState();
        $cotId = $state['cotizacion_id'] ?? null;
        if ($cotId) {
            Cotizaciones::where('id', $cotId)->update(['estado' => 'Facturada']);
        }
    }
}
