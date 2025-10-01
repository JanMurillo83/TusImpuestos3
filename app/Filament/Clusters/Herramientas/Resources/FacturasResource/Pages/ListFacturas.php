<?php

namespace App\Filament\Clusters\Herramientas\Resources\FacturasResource\Pages;

use App\Filament\Clusters\Herramientas\Resources\FacturasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacturas extends ListRecords
{
    protected static string $resource = FacturasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
