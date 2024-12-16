<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProveedores extends ListRecords
{
    protected static string $resource = ProveedoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
