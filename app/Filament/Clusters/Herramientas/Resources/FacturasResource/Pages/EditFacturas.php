<?php

namespace App\Filament\Clusters\Herramientas\Resources\FacturasResource\Pages;

use App\Filament\Clusters\Herramientas\Resources\FacturasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacturas extends EditRecord
{
    protected static string $resource = FacturasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
