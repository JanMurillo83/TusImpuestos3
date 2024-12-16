<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClientesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
