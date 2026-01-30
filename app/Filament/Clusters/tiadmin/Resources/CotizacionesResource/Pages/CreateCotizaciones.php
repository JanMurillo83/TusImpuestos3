<?php

namespace App\Filament\Clusters\tiadmin\Resources\CotizacionesResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\CotizacionesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCotizaciones extends CreateRecord
{
    protected static string $resource = CotizacionesResource::class;

    protected function afterCreate(): void
    {
        $this->record->refresh();
        $this->record->fixPartidasSubtotalFromCantidadPrecio();
        $this->record->recalculateTotalsFromPartidas();
    }
}
