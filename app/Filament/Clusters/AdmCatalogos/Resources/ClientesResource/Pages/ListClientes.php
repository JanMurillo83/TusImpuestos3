<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ClientesResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = ClientesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
