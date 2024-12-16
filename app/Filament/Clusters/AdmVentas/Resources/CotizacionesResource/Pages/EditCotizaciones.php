<?php

namespace App\Filament\Clusters\AdmVentas\Resources\CotizacionesResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\CotizacionesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCotizaciones extends EditRecord
{
    protected static string $resource = CotizacionesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
