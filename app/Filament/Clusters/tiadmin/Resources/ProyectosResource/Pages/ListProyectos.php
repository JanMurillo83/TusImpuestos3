<?php

namespace App\Filament\Clusters\tiadmin\Resources\ProyectosResource\Pages;

use App\Filament\Clusters\tiadmin\Resources\ProyectosResource;
use App\Filament\Resources\Pages\ListRecords;

class ListProyectos extends ListRecords
{
    protected static string $resource = ProyectosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
