<?php

namespace App\Filament\Clusters\AdmCatalogos\Resources\ProyectosResource\Pages;

use App\Filament\Clusters\AdmCatalogos\Resources\ProyectosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProyectos extends ListRecords
{
    protected static string $resource = ProyectosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }
}
