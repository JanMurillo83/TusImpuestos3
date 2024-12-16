<?php

namespace App\Filament\Clusters\AdmVentas\Resources\FacturasResource\Pages;

use App\Filament\Clusters\AdmVentas\Resources\FacturasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacturas extends EditRecord
{
    protected static string $resource = FacturasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
