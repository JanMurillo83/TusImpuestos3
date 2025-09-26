<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ProveedoresResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProveedores extends ListRecords
{
    use HasResizableColumn;
    protected static string $resource = ProveedoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
