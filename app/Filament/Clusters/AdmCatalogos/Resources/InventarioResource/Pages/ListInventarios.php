<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\InventarioResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
