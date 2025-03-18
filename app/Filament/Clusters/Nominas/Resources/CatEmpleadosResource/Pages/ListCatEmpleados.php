<?php

namespace App\Filament\Clusters\Nominas\Resources\CatEmpleadosResource\Pages;

use App\Filament\Clusters\Nominas\Resources\CatEmpleadosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCatEmpleados extends ListRecords
{
    protected static string $resource = CatEmpleadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
